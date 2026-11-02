<?php

namespace App\Actions\Products;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StoreProductImage
{
    /**
     * The longest edge, in pixels, of a stored product photo.
     */
    private const MAX_EDGE = 1024;

    /**
     * Quality used when re-encoding the photo (0-100).
     */
    private const QUALITY = 82;

    /**
     * Optimise an uploaded photo, store it on the public disk and return its path.
     *
     * The client filename is never reused: the stored name is random, the
     * extension is chosen by the encoder, and metadata (EXIF/GPS) is dropped
     * because the bytes are rebuilt from the decoded pixels.
     */
    public function handle(UploadedFile $file): string
    {
        $image = $this->decode($file);
        $image = $this->applyOrientation($image, $file);
        $image = $this->downscale($image);

        [$extension, $contents] = $this->encode($image);

        imagedestroy($image);

        $path = sprintf(
            'products/%s/%s%s',
            now()->format('Y/m'),
            Str::random(40),
            $extension,
        );

        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    /**
     * Delete a stored product photo.
     */
    public function delete(?string $path): void
    {
        // Never let a stray database value reach files outside the products folder.
        if (filled($path) && str_starts_with($path, 'products/')) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Decode the uploaded bytes into a GD image.
     */
    private function decode(UploadedFile $file): GdImage
    {
        $image = @imagecreatefromstring($file->getContent());

        if ($image === false) {
            throw ValidationException::withMessages([
                'image' => __('The photo could not be read as an image.'),
            ]);
        }

        return $image;
    }

    /**
     * Rotate phone photos according to their EXIF orientation.
     */
    private function applyOrientation(GdImage $image, UploadedFile $file): GdImage
    {
        if (! function_exists('exif_read_data') || $file->getMimeType() !== 'image/jpeg') {
            return $image;
        }

        $exif = @exif_read_data($file->getRealPath());
        $orientation = is_array($exif) ? ($exif['Orientation'] ?? null) : null;

        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        if ($rotated === false) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }

    /**
     * Scale the image down so its longest edge fits MAX_EDGE (never upscale).
     */
    private function downscale(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $longest = max($width, $height);

        if ($longest <= self::MAX_EDGE) {
            return $image;
        }

        $scale = self::MAX_EDGE / $longest;

        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $resized = imagescale($image, $targetWidth, $targetHeight, IMG_BICUBIC);

        if ($resized === false) {
            throw new RuntimeException('Unable to resize the product image.');
        }

        imagedestroy($image);

        return $resized;
    }

    /**
     * Encode the image, preferring WebP and falling back to JPEG.
     *
     * @return array{0: string, 1: string} The file extension and encoded bytes.
     */
    private function encode(GdImage $image): array
    {
        if (function_exists('imagewebp')) {
            imagealphablending($image, false);
            imagesavealpha($image, true);

            ob_start();
            $encoded = imagewebp($image, null, self::QUALITY);
            $contents = (string) ob_get_clean();

            if ($encoded && $contents !== '') {
                return ['.webp', $contents];
            }
        }

        return $this->encodeAsJpeg($image);
    }

    /**
     * Encode as JPEG, compositing any transparency onto white.
     *
     * @return array{0: string, 1: string} The file extension and encoded bytes.
     */
    private function encodeAsJpeg(GdImage $image): array
    {
        $canvas = $this->flatten($image);

        ob_start();
        $encoded = imagejpeg($canvas, null, self::QUALITY);
        $contents = (string) ob_get_clean();

        imagedestroy($canvas);

        if (! $encoded || $contents === '') {
            throw new RuntimeException('Unable to encode the product image.');
        }

        return ['.jpg', $contents];
    }

    /**
     * Composite a translucent image onto a white canvas.
     */
    private function flatten(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);

        if ($white === false) {
            throw new RuntimeException('Unable to prepare the image canvas.');
        }

        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);

        return $canvas;
    }
}
