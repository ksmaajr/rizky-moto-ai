<?php

namespace App\Console\Commands;

use App\Models\GeneratedImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class BackfillGenerationThumbnails extends Command
{
    protected $signature = 'generations:thumbnails
        {--chunk=20 : Number of records read per database chunk}
        {--sleep=100 : Milliseconds to pause between images}';

    protected $description = 'Create lightweight WebP thumbnails for existing generation history images.';

    public function handle(): int
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            $this->error('PHP GD with WebP support is required. Check imagecreatefromstring() and imagewebp().');

            return self::FAILURE;
        }

        $chunkSize = max(1, min(200, (int) $this->option('chunk')));
        $sleepMs = max(0, min(5000, (int) $this->option('sleep')));
        $disk = Storage::disk('public');

        $scanned = 0;
        $created = 0;
        $skipped = 0;
        $failed = 0;

        GeneratedImage::query()
            ->whereNotNull('image_path')
            ->where('image_path', '<>', '')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($images) use (
                $disk,
                $sleepMs,
                &$scanned,
                &$created,
                &$skipped,
                &$failed
            ): void {
                foreach ($images as $image) {
                    $scanned++;

                    try {
                        $metadata = is_array($image->metadata) ? $image->metadata : [];
                        $existingPath = data_get($metadata, 'thumbnail_path');

                        if (is_string($existingPath) && $existingPath !== '' && $disk->exists($existingPath)) {
                            $skipped++;
                        } else {
                            $thumbnailPath = $this->createThumbnail($disk, (string) $image->image_path);

                            $metadata['thumbnail_path'] = $thumbnailPath;
                            $metadata['thumbnail_generated_at'] = now()->toIso8601String();

                            $image->forceFill(['metadata' => $metadata])->save();
                            $created++;
                        }
                    } catch (Throwable $exception) {
                        $failed++;

                        Log::warning('Generation history thumbnail backfill failed.', [
                            'generated_image_id' => $image->id,
                            'image_path' => $image->image_path,
                            'exception' => $exception->getMessage(),
                        ]);

                        $this->warn(sprintf(
                            'Gagal #%d (%s): %s',
                            $image->id,
                            $image->image_path,
                            $exception->getMessage()
                        ));
                    }

                    if ($sleepMs > 0) {
                        usleep($sleepMs * 1000);
                    }
                }

                $this->line(sprintf(
                    'Dipindai: %d | Thumbnail dibuat: %d | Dilewati: %d | Gagal: %d',
                    $scanned,
                    $created,
                    $skipped,
                    $failed
                ));
            });

        $this->newLine();
        $this->info(sprintf(
            'Selesai. Dipindai %d gambar; %d thumbnail dibuat; %d dilewati; %d gagal.',
            $scanned,
            $created,
            $skipped,
            $failed
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function createThumbnail($disk, string $originalPath): string
    {
        if (! $disk->exists($originalPath)) {
            throw new RuntimeException('File original tidak ditemukan pada public disk.');
        }

        $absolutePath = $disk->path($originalPath);

        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            throw new RuntimeException('File original tidak dapat dibaca oleh PHP.');
        }

        $binary = file_get_contents($absolutePath);

        if ($binary === false || $binary === '') {
            throw new RuntimeException('Konten gambar original kosong atau gagal dibaca.');
        }

        $source = @imagecreatefromstring($binary);
        unset($binary);

        if (! $source instanceof \GdImage) {
            throw new RuntimeException('GD tidak dapat mendekode format gambar original.');
        }

        $thumbnail = null;

        try {
            $sourceWidth = imagesx($source);
            $sourceHeight = imagesy($source);

            if ($sourceWidth < 1 || $sourceHeight < 1) {
                throw new RuntimeException('Dimensi gambar original tidak valid.');
            }

            $maxEdge = 480;
            $scale = min(1, $maxEdge / max($sourceWidth, $sourceHeight));
            $width = max(1, (int) round($sourceWidth * $scale));
            $height = max(1, (int) round($sourceHeight * $scale));

            $thumbnail = imagecreatetruecolor($width, $height);

            if (! $thumbnail instanceof \GdImage) {
                throw new RuntimeException('GD gagal membuat canvas thumbnail.');
            }

            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);

            $transparent = imagecolorallocatealpha($thumbnail, 0, 0, 0, 127);
            imagefilledrectangle($thumbnail, 0, 0, $width, $height, $transparent);

            if (! imagecopyresampled(
                $thumbnail,
                $source,
                0,
                0,
                0,
                0,
                $width,
                $height,
                $sourceWidth,
                $sourceHeight
            )) {
                throw new RuntimeException('GD gagal melakukan resize gambar.');
            }

            ob_start();
            $encoded = imagewebp($thumbnail, null, 78);
            $webp = ob_get_clean();

            if (! $encoded || ! is_string($webp) || $webp === '') {
                throw new RuntimeException('GD gagal meng-encode thumbnail WebP.');
            }

            $directory = trim(str_replace('\\', '/', dirname($originalPath)), './');
            $filename = pathinfo($originalPath, PATHINFO_FILENAME) . '.thumb.webp';
            $thumbnailPath = ($directory !== '' ? $directory . '/' : '') . $filename;

            if (! $disk->put($thumbnailPath, $webp)) {
                throw new RuntimeException('Thumbnail WebP gagal disimpan ke public disk.');
            }

            return $thumbnailPath;
        } finally {
            if ($thumbnail instanceof \GdImage) {
                imagedestroy($thumbnail);
            }

            imagedestroy($source);
        }
    }
}
