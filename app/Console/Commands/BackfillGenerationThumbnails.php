<?php

namespace App\Console\Commands;

use App\Models\GeneratedImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
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
            $this->error('PHP GD dengan dukungan WebP belum tersedia. Aktifkan extension GD/WebP terlebih dahulu.');
            return self::FAILURE;
        }

        $disk = Storage::disk('public');
        $chunk = max(1, min(100, (int) $this->option('chunk')));
        $sleep = max(0, min(5000, (int) $this->option('sleep')));
        $processed = 0;
        $created = 0;
        $skipped = 0;
        $failed = 0;

        GeneratedImage::query()
            ->whereNotNull('image_path')
            ->orderBy('id')
            ->chunkById($chunk, function ($images) use ($disk, $sleep, &$processed, &$created, &$skipped, &$failed): void {
                foreach ($images as $image) {
                    $processed++;
                    $metadata = $image->metadata ?? [];
                    $existing = $metadata['thumbnail_path'] ?? null;

                    if (is_string($existing) && $existing !== '' && $disk->exists($existing)) {
                        $skipped++;
                        continue;
                    }

                    try {
                        if (! $disk->exists($image->image_path)) {
                            $skipped++;
                            continue;
                        }

                        $binary = $disk->get($image->image_path);
                        $source = @imagecreatefromstring($binary);
                        if (! $source) {
                            $failed++;
                            continue;
                        }

                        $sourceWidth = imagesx($source);
                        $sourceHeight = imagesy($source);
                        if ($sourceWidth < 1 || $sourceHeight < 1) {
                            imagedestroy($source);
                            $failed++;
                            continue;
                        }

                        $maxEdge = 480;
                        $scale = min(1, $maxEdge / max($sourceWidth, $sourceHeight));
                        $width = max(1, (int) round($sourceWidth * $scale));
                        $height = max(1, (int) round($sourceHeight * $scale));
                        $thumbnail = imagecreatetruecolor($width, $height);
                        if (! $thumbnail) {
                            imagedestroy($source);
                            $failed++;
                            continue;
                        }

                        imagealphablending($thumbnail, false);
                        imagesavealpha($thumbnail, true);
                        $transparent = imagecolorallocatealpha($thumbnail, 0, 0, 0, 127);
                        imagefilledrectangle($thumbnail, 0, 0, $width, $height, $transparent);
                        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

                        ob_start();
                        $encoded = imagewebp($thumbnail, null, 78);
                        $webp = ob_get_clean();

                        imagedestroy($thumbnail);
                        imagedestroy($source);

                        if (! $encoded || ! is_string($webp) || $webp === '') {
                            $failed++;
                            continue;
                        }

                        $thumbnailPath = preg_replace('/\.(png|jpe?g)$/i', '.webp', $image->image_path);
                        if (! is_string($thumbnailPath) || $thumbnailPath === $image->image_path) {
                            $thumbnailPath = $image->image_path . '.webp';
                        }

                        if (! $disk->put($thumbnailPath, $webp)) {
                            $failed++;
                            continue;
                        }

                        $metadata['thumbnail_path'] = $thumbnailPath;
                        $image->forceFill(['metadata' => $metadata])->save();
                        $created++;
                    } catch (Throwable $exception) {
                        report($exception);
                        $failed++;
                    }

                    if ($sleep > 0) {
                        usleep($sleep * 1000);
                    }
                }

                $this->line(sprintf(
                    'Dipindai: %d | Thumbnail dibuat: %d | Dilewati: %d | Gagal: %d',
                    $processed,
                    $created,
                    $skipped,
                    $failed
                ));
            });

        $this->newLine();
        $this->info(sprintf(
            'Selesai. Dipindai %d gambar; %d thumbnail dibuat; %d dilewati; %d gagal.',
            $processed,
            $created,
            $skipped,
            $failed
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
