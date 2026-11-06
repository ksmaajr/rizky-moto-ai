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

    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(public int $archiveId)
    {
    }

    public function handle(): void
    {
        $archive = HistoryDownloadArchive::query()->find($this->archiveId);
        if (! $archive || $archive->status !== 'queued') {
            return;
        }

        $archive->update(['status' => 'processing', 'processed_images' => 0]);
        $disk = Storage::disk('public');
        $zipDirectory = 'history-downloads';
        Storage::disk('local')->makeDirectory($zipDirectory);
        $relativePath = $zipDirectory . '/history-' . $archive->id . '-' . uniqid() . '.zip';
        $absolutePath = Storage::disk('local')->path($relativePath);
        $zip = new ZipArchive();

        try {
            if ($zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('ZIP tidak dapat dibuat.');
            }

            $added = 0;
            $processed = 0;

            $generations = Generation::query()
                ->where('user_id', $archive->user_id)
                ->whereIn('id', $archive->generation_ids ?? [])
                ->with(['store', 'generatedImages' => fn ($q) => $q->orderBy('id')])
                ->orderByRaw('FIELD(id, ' . implode(',', array_map('intval', $archive->generation_ids ?? [0])) . ')')
                ->get();

            foreach ($generations as $generation) {
                $storeName = trim((string) ($generation->store?->name ?: 'Store'));
                $safeStore = $this->safeSegment($storeName, 'Store');
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

                foreach ($generation->generatedImages as $image) {
                    $path = $image->image_path;
                    if (! is_string($path) || $path === '' || ! $disk->exists($path)) {
                        continue;
                    }

                    $absolute = $disk->path($path);
                    if (! is_file($absolute) || ! is_readable($absolute)) {
                        continue;
                    }

                    $binary = $this->encodeUnderLimit($absolute, 2 * 1024 * 1024);
                    $index++;
                    $filename = $safeStore . '/' . $safeTitle . ($index > 1 ? ' (' . $index . ')' : '') . '.jpg';
                    if (! $zip->addFromString($filename, $binary)) {
                        throw new \RuntimeException('Gagal menambahkan gambar ke ZIP.');
                    }

                    $added++;
                    $processed++;
                    if ($processed % 5 === 0) {
                        $archive->update(['processed_images' => $processed]);
                    }
                }
            }

            if ($added === 0) {
                throw new \RuntimeException('Tidak ada gambar yang dapat dimasukkan ke ZIP.');
            }

            if (! $zip->close()) {
                throw new \RuntimeException('ZIP gagal diselesaikan.');
            }

            $archive->update([
                'status' => 'completed',
                'processed_images' => $processed,
                'file_path' => $relativePath,
            ]);
        } catch (\Throwable $exception) {
            try { $zip->close(); } catch (\Throwable) {}
            if (is_file($absolutePath)) @unlink($absolutePath);
            report($exception);
            $archive->update([
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 2000),
            ]);
        }
    }

    private function safeSegment(string $value, string $fallback): string
    {
        $value = preg_replace('/[\\\\\\/:*?"<>|]+/u', '', $value) ?? '';
        $value = trim($value, " .\t\n\r\0\x0B");
        return mb_substr($value !== '' ? $value : $fallback, 0, 120);
    }

    private function encodeUnderLimit(string $path, int $maxBytes): string
    {
        $source = file_get_contents($path);
        if ($source === false) {
            throw new \RuntimeException('Gambar tidak dapat dibaca.');
        }

        $image = @imagecreatefromstring($source);
        unset($source);
        if (! $image instanceof \GdImage) {
            throw new \RuntimeException('Format gambar tidak didukung oleh GD.');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        try {
            $quality = 85;
            for ($attempt = 0; $attempt < 18; $attempt++) {
                $canvas = imagecreatetruecolor($width, $height);
                if (! $canvas instanceof \GdImage) {
                    throw new \RuntimeException('Canvas kompresi gagal dibuat.');
                }

                $white = imagecolorallocate($canvas, 255, 255, 255);
                imagefill($canvas, 0, 0, $white);
                imagecopyresampled($canvas, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
                ob_start();
                imagejpeg($canvas, null, $quality);
                $binary = ob_get_clean();
                imagedestroy($canvas);

                if (is_string($binary) && strlen($binary) <= $maxBytes) {
                    return $binary;
                }

                if ($quality > 42) {
                    $quality = max(40, $quality - 7);
                } else {
                    $width = max(320, (int) floor($width * 0.88));
                    $height = max(320, (int) floor($height * 0.88));
                    $quality = 78;
                }
            }
        } finally {
            imagedestroy($image);
        }

        throw new \RuntimeException('Gambar tidak dapat dikompresi hingga maksimal 2 MB.');
    }
}
