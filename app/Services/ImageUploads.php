<?php

namespace App\Services;

use App\Exceptions\ImageRejected;
use App\Jobs\WarmImageCache;
use App\Models\Contact;
use App\Models\UploadedImage;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Compresses and stores images members upload into rich text. Re-encoding
 * drops all metadata, including camera GPS location.
 */
class ImageUploads
{
    private const MAX_PIXELS = 40_000_000;

    /**
     * @throws ImageRejected
     */
    public function store(Contact $uploader, UploadedFile $file): UploadedImage
    {
        $contents = (string) file_get_contents($file->getRealPath());
        $info = @getimagesizefromstring($contents);

        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            throw new ImageRejected('That file could not be read as an image.');
        }

        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            throw new ImageRejected('That image is too large. Please use one under 40 megapixels.');
        }

        // Decoding needs about 4 bytes per pixel, more than PHP's default allows for big photos.
        ini_set('memory_limit', '512M');
        $image = @imagecreatefromstring($contents);

        if (! $image instanceof GdImage) {
            throw new ImageRejected('That file could not be read as an image.');
        }

        $image = $this->orient($image, $file);
        $image = $this->shrink($image, (int) config('uploads.images.max_dimension'));
        $transparent = $info[2] !== IMAGETYPE_JPEG && $this->hasTransparency($image);
        [$encoded, $extension, $mime] = $transparent ? $this->png($image) : $this->jpeg($image);

        $disk = config('uploads.disk');
        $path = "uploads/{$uploader->building_id}/".bin2hex(random_bytes(20)).".{$extension}";
        Storage::disk($disk)->put($path, $encoded);

        $upload = UploadedImage::create([
            'building_id' => $uploader->building_id,
            'uploader_contact_id' => $uploader->id,
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $mime,
            'width' => imagesx($image),
            'height' => imagesy($image),
            'size' => strlen($encoded),
            'original_size' => strlen($contents),
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
        ]);

        if (config('uploads.warm_cache')) {
            WarmImageCache::dispatch($upload);
        }

        return $upload;
    }

    /**
     * Apply the EXIF orientation, since the EXIF data itself is about to be dropped.
     */
    private function orient(GdImage $image, UploadedFile $file): GdImage
    {
        $exif = function_exists('exif_read_data') ? @exif_read_data($file->getRealPath()) : false;
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        $angle = match ($orientation) {
            3, 4 => 180,
            5, 6 => 270,
            7, 8 => 90,
            default => 0,
        };

        return $angle ? imagerotate($image, $angle, 0) : $image;
    }

    private function shrink(GdImage $image, int $maxDimension): GdImage
    {
        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxDimension / max($width, $height));

        if ($scale >= 1) {
            return $image;
        }

        return $this->resample($image, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
    }

    /**
     * A resized copy that keeps any transparency.
     */
    private function resample(GdImage $image, int $width, int $height): GdImage
    {
        $resized = imagecreatetruecolor($width, $height);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));

        return $resized;
    }

    /**
     * Whether any pixel is see-through, checked on a small copy for speed.
     */
    private function hasTransparency(GdImage $image): bool
    {
        $sample = imagesx($image) > 256
            ? $this->resample($image, 256, max(1, (int) round(imagesy($image) * 256 / imagesx($image))))
            : $image;

        for ($x = 0, $width = imagesx($sample); $x < $width; $x++) {
            for ($y = 0, $height = imagesy($sample); $y < $height; $y++) {
                if ((imagecolorat($sample, $x, $y) >> 24) & 0x7F) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function jpeg(GdImage $image): array
    {
        // Flatten onto white so any fully opaque PNG or GIF converts cleanly.
        $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imageinterlace($canvas, true);

        ob_start();
        imagejpeg($canvas, null, (int) config('uploads.images.jpeg_quality'));

        return [(string) ob_get_clean(), 'jpg', 'image/jpeg'];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function png(GdImage $image): array
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        imagepng($image, null, 9);

        return [(string) ob_get_clean(), 'png', 'image/png'];
    }
}
