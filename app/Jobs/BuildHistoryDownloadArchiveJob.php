<?php

namespace App\Jobs;

use App\Models\Generation;
use App\Models\HistoryDownloadArchive;
use App\Services\ActivityLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Throwable;

class BuildHistoryDownloadArchiveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;
    public int $tries = 1;

    private const MAX_IMAGE_BYTES = 2 * 1024 * 1024;
    private const MAX_ARCHIVE_IMAGES = 2000;

    public function __construct(public int $archiveId)
    {
    }

    public function handle(): void
    {
        $limit = (string) ini_get('memory_limit');
        if ($limit !== '-1' && $this->memoryLimitToBytes($limit) < 256 * 1024 * 1024) {
            @ini_set('memory_limit', '256M');
        }

        $archive = HistoryDownloadArchive::query()->find($this->archiveId);
        if (! $archive || $archive->status !== 'queued') {
            return;
        }

        $archive->update(['status' => 'processing', 'processed_images' => 0]);
        app(ActivityLogService::class)->log('history_bulk_download', 'download', 'processing', 'Bulk download sedang diproses', $archive->file_name, ['user_id' => $archive->user_id, 'archive_id' => $archive->id, 'total_images' => $archive->total_images]);

        $localDisk = Storage::disk('local');
        $publicDisk = Storage::disk('public');
        $directory = 'history-downloads';
        $localDisk->makeDirectory($directory);
        $tempDirectory = $localDisk->path($directory);
        $relativePath = $directory . '/history-' . $archive->id . '-' . uniqid('', true) . '.zip';
        $absolutePath = $localDisk->path($relativePath);
        // Persist the in-progress path so Laravel's failed() hook can remove it
        // if the worker is terminated by timeout or an out-of-memory condition.
        $archive->update(['file_path' => $relativePath]);
        $zip = new ZipArchive();
        $temporaryFiles = [];

        try {
            if ($zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('ZIP tidak dapat dibuat.');
            }

            $added = 0;
            $processed = 0;
            $targetImages = min(self::MAX_ARCHIVE_IMAGES, max(0, (int) $archive->total_images));
            $ids = array_values(array_filter(array_map('intval', $archive->generation_ids ?? [])));
            if ($ids === []) {
                throw new \RuntimeException('Daftar generation untuk ZIP kosong.');
            }

            $generations = Generation::query()
                ->where('user_id', $archive->user_id)
                ->whereIn('id', $ids)
                ->with('store')
                ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')
                ->get();

            foreach ($generations as $generation) {
                $store = $this->safeSegment((string) ($generation->store?->name ?: 'Store'), 'Store');
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

                $generation->generatedImages()
                    ->whereNotNull('image_path')
                    ->where('image_path', '<>', '')
                    ->orderBy('id')
                    ->chunkById(25, function ($images) use (
                        $publicDisk,
                        $zip,
                        $store,
                        $safeTitle,
                        &$index,
                        &$added,
                        &$processed,
                        &$temporaryFiles,
                        $archive,
                        $tempDirectory,
                        $targetImages
                    ): bool {
                        foreach ($images as $image) {
                            if ($processed >= $targetImages) {
                                return false;
                            }

                            $source = $image->image_path;
                            if (! is_string($source) || $source === '' || ! $publicDisk->exists($source)) {
                                continue;
                            }

                            $sourcePath = $publicDisk->path($source);
                            if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
                                continue;
                            }

                            $index++;
                            $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg');
                            if ((int) filesize($sourcePath) <= self::MAX_IMAGE_BYTES) {
                                $zipPath = $sourcePath;
                                $entryName = $store . '/' . $safeTitle
                                    . ($index > 1 ? ' (' . $index . ')' : '')
                                    . '.' . $this->safeExtension($extension);
                            } else {
                                $compressedPath = tempnam($tempDirectory, 'img-');
                                if ($compressedPath === false) {
                                    throw new \RuntimeException('File sementara kompresi tidak dapat dibuat.');
                                }

                                $temporaryFiles[] = $compressedPath;
                                $this->encodeUnderLimitToFile($sourcePath, $compressedPath);
                                $zipPath = $compressedPath;
                                $entryName = $store . '/' . $safeTitle
                                    . ($index > 1 ? ' (' . $index . ')' : '') . '.jpg';
                            }

                            if (! $zip->addFile($zipPath, $entryName)) {
                                throw new \RuntimeException('Gagal menambahkan gambar ke ZIP: ' . basename($sourcePath));
                            }

                            $added++;
                            $processed++;
                            if ($processed % 5 === 0 || $processed === $targetImages) {
                                $archive->update(['processed_images' => $processed]);
                            }

                            unset($zipPath, $entryName, $sourcePath);
                            gc_collect_cycles();
                        }

                        return $processed < $targetImages;
                    }, 'id');

                unset($generation);
                gc_collect_cycles();

                if ($processed >= $targetImages) {
                    break;
                }
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
            app(ActivityLogService::class)->log(
                'history_bulk_download',
                'download',
                'success',
                'Bulk download selesai',
                $archive->file_name,
                ['user_id' => $archive->user_id, 'archive_id' => $archive->id, 'processed_images' => $processed, 'file_size' => is_file($absolutePath) ? filesize($absolutePath) : 0]
            );
        } catch (\Throwable $exception) {
            try {
                $zip->close();
            } catch (\Throwable) {
                // Keep the original exception.
            }

            $this->deleteTemporaryFiles($temporaryFiles);
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }

            report($exception);
            $archive->update([
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 2000),
                'file_path' => null,
            ]);
            app(ActivityLogService::class)->log(
                'history_bulk_download',
                'download',
                'error',
                'Bulk download gagal',
                $exception->getMessage(),
                ['user_id' => $archive->user_id, 'archive_id' => $archive->id, 'processed_images' => (int) $archive->fresh()->processed_images]
            );
        }
    }

    public function failed(?Throwable $exception): void
    {
        $archive = HistoryDownloadArchive::query()->find($this->archiveId);
        if (! $archive) {
            return;
        }

        if ($archive->file_path && Storage::disk('local')->exists($archive->file_path)) {
            Storage::disk('local')->delete($archive->file_path);
        }

        $archive->update([
            'status' => 'failed',
            'file_path' => null,
            'error_message' => mb_substr($exception?->getMessage() ?: 'Worker dihentikan sebelum ZIP selesai dibuat.', 0, 2000),
        ]);

        app(ActivityLogService::class)->log(
            'history_bulk_download',
            'download',
            'error',
            'Bulk download dihentikan oleh worker',
            $exception?->getMessage() ?: 'Worker dihentikan sebelum ZIP selesai dibuat.',
            ['user_id' => $archive->user_id, 'archive_id' => $archive->id, 'processed_images' => $archive->processed_images]
        );
    }

    private function encodeUnderLimitToFile(string $sourcePath, string $destinationPath): void
    {
        $binary = file_get_contents($sourcePath);
        if ($binary === false) {
            throw new \RuntimeException('Gambar tidak dapat dibaca.');
        }

        $image = @imagecreatefromstring($binary);
        unset($binary);
        if (! $image instanceof \GdImage) {
            throw new \RuntimeException('Format gambar tidak didukung GD: ' . basename($sourcePath));
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
                    imagecopyresampled($canvas, $image, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
                    $quality = max(40, 85 - ($attempt * 7));
                    if (! imagejpeg($canvas, $destinationPath, $quality)) {
                        throw new \RuntimeException('Gagal mengompres gambar.');
                    }
                } finally {
                    imagedestroy($canvas);
                }

                clearstatcache(true, $destinationPath);
                if (is_file($destinationPath) && filesize($destinationPath) <= self::MAX_IMAGE_BYTES) {
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
        $value = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '', $value);
        $value = trim($value, " .\t
\r\0\x0B");

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
