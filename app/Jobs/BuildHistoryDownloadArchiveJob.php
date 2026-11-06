<?php

namespace App\Jobs;

use App\Models\GeneratedImage;
use App\Models\Generation;
use App\Models\HistoryDownloadArchive;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BuildHistoryDownloadArchiveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 1;

    private const MAX_IMAGE_BYTES = 2 * 1024 * 1024;

    public function __construct(public int $archiveId)
    {
    }

    public function handle(): void
    {
        // The archive runs in a queue worker, not in the Livewire/FPM request.
        // Give GD enough headroom for one large source image while keeping the
        // job bounded; images are processed sequentially and never accumulated.
        $currentLimit = ini_get('memory_limit');
        if ($currentLimit !== '-1' && $this->memoryLimitToBytes((string) $currentLimit) < 256 * 1024 * 1024) {
            @ini_set('memory_limit', '256M');
        }

        $archive = HistoryDownloadArchive::query()->find($this->archiveId);
        if (! $archive || $archive->status !== 'queued') {
            return;
        }

        $archive->update(['status' => 'processing', 'processed_images' => 0]);

        $localDisk = Storage::disk('local');
        $publicDisk = Storage::disk('public');
        $zipDirectory = 'history-downloads';
        $tempDirectory = $localDisk->path($zipDirectory);
        $localDisk->makeDirectory($zipDirectory);

        $relativePath = $zipDirectory . '/history-' . $archive->id . '-' . uniqid('', true) . '.zip';
        $absolutePath = $localDisk->path($relativePath);
        $zip = new ZipArchive();
        $temporaryFiles = [];

        try {
            if ($zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('ZIP tidak dapat dibuat.');
            }

            $added = 0;
            $processed = 0;
            $generationIds = array_values(array_filter(array_map('intval', $archive->generation_ids ?? [])));

            if ($generationIds === []) {
                throw new \RuntimeException('Daftar generation untuk ZIP kosong.');
            }

            $generations = Generation::query()
                ->where('user_id', $archive->user_id)
                ->whereIn('id', $generationIds)
                ->with('store')
                ->orderByRaw('FIELD(id, ' . implode(',', $generationIds) . ')')
                ->get();

            foreach ($generations as $generation) {
                $safeStore = $this->safeSegment((string) ($generation->store?->name ?: 'Store'), 'Store');
                $metadata = is_array($generation->metadata) ? $generation->metadata : [];
                $title = (string) (
                    data_get($metadata, 'requested_custom_title')
                    ?: data_get($metadata, 'custom_title')
                    ?: data_get($metadata, 'final_title')
                    ?: data_get($metadata, 'product_title')
                    ?: data_get($metadata, 'title')
                    ?: ('Hasil Generation ' . $generation->id)
                );
                $safeTitle = $this->safeSegment($title, 'Hasil Generation ' . $generation->id);
                $index = 0;

                // Chunk the relation instead of eager-loading every image record.
                $generation->generatedImages()
                    ->whereNotNull('image_path')
                    ->where('image_path', '<>', '')
                    ->orderBy('id')
                    ->chunkById(25, function ($images) use (
                        $publicDisk,
                        $zip,
                        $safeStore,
                        $safeTitle,
                        &$index,
                        &$added,
                        &$processed,
                        &$temporaryFiles,
                        $archive,
                        $tempDirectory
                    ): void {
                        foreach ($images as $image) {
                            $sourcePath = $image->image_path;
                            if (! is_string($sourcePath) || $sourcePath === '' || ! $publicDisk->exists($sourcePath)) {
                                continue;
                            }

                            $sourceAbsolutePath = $publicDisk->path($sourcePath);
                            if (! is_file($sourceAbsolutePath) || ! is_readable($sourceAbsolutePath)) {
                                continue;
                            }

                            $index++;
                            $extension = strtolower(pathinfo($sourceAbsolutePath, PATHINFO_EXTENSION) ?: 'jpg');

                            if ((int) filesize($sourceAbsolutePath) <= self::MAX_IMAGE_BYTES) {
                                // ZipArchive adds the file from disk; it does not copy
                                // the complete image into the PHP request/worker memory.
                                $zipPath = $sourceAbsolutePath;
                                $filename = $safeStore . '/' . $safeTitle
                                    . ($index > 1 ? ' (' . $index . ')' : '')
                                    . '.' . $this->safeExtension($extension);
                            } else {
                                $compressedPath = tempnam($tempDirectory, 'img-');
                                if ($compressedPath === false) {
                                    throw new \RuntimeException('Tidak dapat membuat file sementara untuk kompresi.');
                                }

                                $temporaryFiles[] = $compressedPath;
                                $this->encodeUnderLimitToFile($sourceAbsolutePath, $compressedPath, self::MAX_IMAGE_BYTES);
                                $zipPath = $compressedPath;
                                $filename = $safeStore . '/' . $safeTitle
                                    . ($index > 1 ? ' (' . $index . ')' : '') . '.jpg';
                            }

                            if (! $zip->addFile($zipPath, $filename)) {
                                throw new \RuntimeException('Gagal menambahkan gambar ke ZIP: ' . basename($sourceAbsolutePath));
                            }

                            $added++;
                            $processed++;
                            if ($processed % 5 === 0) {
                                $archive->update(['processed_images' => $processed]);
                            }

                            unset($zipPath, $filename, $sourceAbsolutePath);
                            gc_collect_cycles();
                        }
                    }, 'id');

                unset($generation);
                gc_collect_cycles();
            }

            if ($added === 0) {
                throw new \RuntimeException('Tidak ada gambar yang dapat dimasukkan ke ZIP.');
            }

            if (! $zip->close()) {
                throw new \RuntimeException('ZIP gagal diselesaikan.');
            }

            $this->deleteTemporaryFiles($temporaryFiles);
            $archive->update([
                'status' => 'completed',
                'processed_images' => $processed,
                'file_path' => $relativePath,
                'error_message' => null,
            ]);
        } catch (\Throwable $exception) {
            try {
                $zip->close();
            } catch (\Throwable) {
                // Preserve the original exception.
            }

            $this->deleteTemporaryFiles($temporaryFiles);
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }

            report($exception);
            $archive->update([
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 2000),
            ]);
        }
    }

    private function encodeUnderLimitToFile(string $sourcePath, string $destinationPath, int $maxBytes): void
    {
        $source = file_get_contents($sourcePath);
        if ($source === false) {
            throw new \RuntimeException('Gambar tidak dapat dibaca.');
        }

        $image = @imagecreatefromstring($source);
        unset($source);

        if (! $image instanceof \GdImage) {
            throw new \RuntimeException('Format gambar tidak didukung oleh GD: ' . basename($sourcePath));
        }

        try {
            $sourceWidth = imagesx($image);
            $sourceHeight = imagesy($image);
            $scale = min(1, 2560 / max($sourceWidth, $sourceHeight));
            $width = max(320, (int) floor($sourceWidth * $scale));
            $height = max(320, (int) floor($sourceHeight * $scale));

            for ($attempt = 0; $attempt < 20; $attempt++) {
                $canvas = imagecreatetruecolor($width, $height);
                if (! $canvas instanceof \GdImage) {
                    throw new \RuntimeException('Canvas kompresi gagal dibuat.');
                }

                try {
                    $white = imagecolorallocate($canvas, 255, 255, 255);
                    imagefill($canvas, 0, 0, $white);
                    imagecopyresampled(
                        $canvas,
                        $image,
                        0,
                        0,
                        0,
                        0,
                        $width,
                        $height,
                        $sourceWidth,
                        $sourceHeight
                    );

                    $quality = $attempt < 7 ? 85 - ($attempt * 7) : 45;
                    if (! imagejpeg($canvas, $destinationPath, max(40, $quality))) {
                        throw new \RuntimeException('Gagal mengompres gambar.');
                    }
                } finally {
                    imagedestroy($canvas);
                }

                clearstatcache(true, $destinationPath);
                if (is_file($destinationPath) && filesize($destinationPath) <= $maxBytes) {
                    return;
                }

                $width = max(320, (int) floor($width * 0.88));
                $height = max(320, (int) floor($height * 0.88));
            }
        } finally {
            imagedestroy($image);
        }

        @unlink($destinationPath);
        throw new \RuntimeException('Gambar tidak dapat dikompresi hingga maksimal 2 MB: ' . basename($sourcePath));
    }

    private function safeSegment(string $value, string $fallback): string
    {
        $value = preg_replace('/[\\\\\\/:*?"<>|]+/u', '', $value) ?? '';
        $value = trim($value, " .\t\n\r\0\x0B");

        return mb_substr($value !== '' ? $value : $fallback, 0, 120);
    }

    private function safeExtension(string $extension): string
    {
        return preg_match('/^[a-z0-9]{1,8}$/', $extension) ? $extension : 'jpg';
    }

    private function deleteTemporaryFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_string($path) && is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function memoryLimitToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
