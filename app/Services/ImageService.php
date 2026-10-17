<?php
/**
 * Image Service
 *
 * Thumbnail generation with GD. Failures are non-fatal (the original image
 * is used as a fallback).
 */

class ImageService
{
    /**
     * Create a JPEG thumbnail next to $sourcePath. Returns the thumbnail file
     * name or null.
     */
    public static function makeThumbnail($sourcePath, $maxWidth = 480, $maxHeight = 240)
    {
        if (!function_exists('imagecreatetruecolor') || !is_file($sourcePath)) {
            return null;
        }

        $info = @getimagesize($sourcePath);
        if (!$info) {
            return null;
        }

        list($width, $height, $type) = $info;

        // Avoid exhausting memory on gigantic panoramas (~4 bytes per pixel)
        if ($width * $height > 64000000) {
            return null;
        }

        switch ($type) {
            case IMAGETYPE_JPEG:
                $src = @imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $src = @imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false;
                break;
            default:
                $src = false;
        }

        if (!$src) {
            return null;
        }

        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
        $thumbW = max(1, (int) round($width * $ratio));
        $thumbH = max(1, (int) round($height * $ratio));

        $thumb = imagecreatetruecolor($thumbW, $thumbH);
        imagecopyresampled($thumb, $src, 0, 0, 0, 0, $thumbW, $thumbH, $width, $height);

        $thumbName = pathinfo($sourcePath, PATHINFO_FILENAME) . '_thumb.jpg';
        $thumbPath = dirname($sourcePath) . '/' . $thumbName;
        $ok = imagejpeg($thumb, $thumbPath, 80);

        imagedestroy($src);
        imagedestroy($thumb);

        return $ok ? $thumbName : null;
    }
}
