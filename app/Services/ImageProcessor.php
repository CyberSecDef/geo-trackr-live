<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Re-encodes an uploaded image with GD. Re-encoding inherently strips ALL EXIF
 * metadata — including any GPS EXIF that would leak the treasure's real location
 * (spec §10 PRIV-3) — and lets us enforce dimension and byte caps before the
 * result is stored as a BLOB.
 *
 * @phpstan-type Processed array{data:string, mime:string, bytes:int}
 */
class ImageProcessor
{
    /**
     * @return array{data:string, mime:string, bytes:int}
     */
    public function process(UploadedFile $file): array
    {
        $cfg = config('geocache.image');

        if (! in_array($file->getMimeType(), $cfg['allowed_mime'], true)) {
            throw new RuntimeException('Unsupported image type.');
        }

        $source = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        if ($source === false) {
            throw new RuntimeException('Could not read the image.');
        }

        $resized = $this->resize($source, (int) $cfg['max_dimension']);
        if ($resized !== $source) {
            imagedestroy($source);
        }

        // Always output JPEG (small, universally supported) unless the source
        // was PNG/WebP with transparency, in which case keep PNG.
        [$data, $mime] = $this->encode($resized, $file->getMimeType());
        imagedestroy($resized);

        if (strlen($data) > $cfg['max_bytes']) {
            throw new RuntimeException('Image is too large after processing.');
        }

        return ['data' => $data, 'mime' => $mime, 'bytes' => strlen($data)];
    }

    /**
     * @param  \GdImage  $img
     * @return \GdImage
     */
    private function resize($img, int $maxDim)
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $longest = max($w, $h);

        if ($longest <= $maxDim) {
            return $img;
        }

        $scale = $maxDim / $longest;
        $nw = (int) round($w * $scale);
        $nh = (int) round($h * $scale);

        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $dst;
    }

    /**
     * @param  \GdImage  $img
     * @return array{0:string, 1:string}
     */
    private function encode($img, string $sourceMime): array
    {
        $keepPng = in_array($sourceMime, ['image/png', 'image/webp', 'image/gif'], true)
            && $this->hasAlpha($img);

        ob_start();
        if ($keepPng) {
            imagesavealpha($img, true);
            imagepng($img, null, 6);
            $mime = 'image/png';
        } else {
            imagejpeg($img, null, 82);
            $mime = 'image/jpeg';
        }
        $data = (string) ob_get_clean();

        return [$data, $mime];
    }

    /**
     * @param  \GdImage  $img
     */
    private function hasAlpha($img): bool
    {
        // Cheap heuristic: sample a grid of pixels for any transparency.
        $w = imagesx($img);
        $h = imagesy($img);
        $step = max(1, (int) (min($w, $h) / 20));

        for ($x = 0; $x < $w; $x += $step) {
            for ($y = 0; $y < $h; $y += $step) {
                if (((imagecolorat($img, $x, $y) >> 24) & 0x7F) > 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
