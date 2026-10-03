<?php

namespace App\Http\Controllers;

use App\Models\GeneratedImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GeneratedImageDownloadController
{
    public function __invoke(Request $request, GeneratedImage $generatedImage)
    {
        $generation = $generatedImage->generation;

        abort_unless(
            $generation && (int) $generation->user_id === (int) $request->user()->id,
            403
        );

        $sourcePath = $generatedImage->image_path;

        abort_unless(
            $sourcePath && Storage::disk('public')->exists($sourcePath),
            404
        );

        $maxMb = min(10, max(0.25, (float) $request->query('max_mb', 2)));
        $maxBytes = (int) round($maxMb * 1024 * 1024);

        $quality = min(100, max(35, (int) $request->query('quality', 85)));
        $format = strtolower((string) $request->query('format', 'jpg'));

        if (! in_array($format, ['jpg', 'jpeg', 'webp'], true)) {
            $format = 'jpg';
        }

        $source = Storage::disk('public')->path($sourcePath);
        $binary = file_get_contents($source);

        if ($binary === false) {
            throw new RuntimeException('File generated image tidak dapat dibaca.');
        }

        $image = @imagecreatefromstring($binary);

        if ($image === false) {
            throw new RuntimeException('Format image tidak dapat diproses oleh server.');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // Shopee-oriented download: default JPG, target <= 2 MB.
        // We keep the original generated file untouched.
        $result = $this->encodeWithinLimit(
            $image,
            $width,
            $height,
            $format,
            $quality,
            $maxBytes
        );

        imagedestroy($image);

        $filenameBase = pathinfo($sourcePath, PATHINFO_FILENAME);
        $extension = $format === 'webp' ? 'webp' : 'jpg';
        $downloadName = $filenameBase . '-download.' . $extension;

        return response($result['binary'], 200, [
            'Content-Type' => $result['mime'],
            'Content-Length' => strlen($result['binary']),
            'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-RMS-Max-Bytes' => (string) $maxBytes,
            'X-RMS-Output-Bytes' => (string) strlen($result['binary']),
        ]);
    }

    private function encodeWithinLimit(
        \GdImage $source,
        int $width,
        int $height,
        string $format,
        int $quality,
        int $maxBytes
    ): array {
        $currentWidth = $width;
        $currentHeight = $height;
        $currentQuality = $quality;

        for ($attempt = 0; $attempt < 18; $attempt++) {
            $canvas = imagecreatetruecolor($currentWidth, $currentHeight);

            // White background is safer for JPG when the generated PNG has alpha.
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $white);

            imagecopyresampled(
                $canvas,
                $source,
                0,
                0,
                0,
                0,
                $currentWidth,
                $currentHeight,
                imagesx($source),
                imagesy($source)
            );

            ob_start();

            if ($format === 'webp' && function_exists('imagewebp')) {
                imagewebp($canvas, null, $currentQuality);
                $mime = 'image/webp';
            } else {
                imagejpeg($canvas, null, $currentQuality);
                $format = 'jpg';
                $mime = 'image/jpeg';
            }

            $binary = ob_get_clean();
            imagedestroy($canvas);

            if ($binary !== false && strlen($binary) <= $maxBytes) {
                return [
                    'binary' => $binary,
                    'mime' => $mime,
                ];
            }

            // First lower compression quality, then reduce dimensions.
            if ($currentQuality > 42) {
                $currentQuality = max(40, $currentQuality - 7);
                continue;
            }

            $currentWidth = max(320, (int) floor($currentWidth * 0.88));
            $currentHeight = max(320, (int) floor($currentHeight * 0.88));
            $currentQuality = min($quality, 78);
        }

        throw new RuntimeException('Server tidak dapat membuat file download di bawah batas ukuran yang dipilih.');
    }
}
