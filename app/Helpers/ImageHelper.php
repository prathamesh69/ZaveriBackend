<?php

namespace App\Helpers;

use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;


class ImageHelper
{
    public static $TYPE_CATEGORY = 'category';
    public static $TYPE_SUBCATEGORY = 'subcategory';
    public static $TYPE_BRAND = 'brand';
    public static $TYPE_PRODUCT = 'product';
    public static $TYPE_WHOLESALER_FIRM = 'wholesaler-firm';
    public static $TYPE_USER = 'user';
    public static $TYPE_POST = 'post';
    public static $TYPE_RETAILER_RATING = 'retailer-rating';
    public static $TYPE_WHOLESALER_RATING = 'wholesaler-rating';
    public static $TYPE_WHOLESALER_FIRM_IMAGE = 'wholesaler-firm-images';


    public static function saveImage(UploadedFile $file, $type = 'other')
    {
        $filename = $file->hashName();
        $dir = Storage::disk('public')->path("images/$type/");
        $thumbDir = Storage::disk('public')->path("images/$type/thumb/");

        if (!is_dir($dir)) mkdir($dir, 0755, true);
        if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);

        // image
        $img = Image::make($file);
        if ($img->getWidth() > 1536) {
            $img->resize(1536, null, function ($constraint) {
                $constraint->aspectRatio();
            });
        }
        $img->save($dir . $filename);
        unlink($file->path());

        // thumbnail
        $thumb = Image::make($dir . $filename);
        $thumb->resize(360, null, function ($constraint) {
            $constraint->aspectRatio();
        });
        $thumb->save($thumbDir . $filename);

        return $filename;
    }

    public static function delete($type, $image)
    {
        $imageFile = Storage::disk('public')->path("images/$type/") . $image;
        $thumbFile = Storage::disk('public')->path("images/$type/thumb/") . $image;

        if (file_exists($imageFile)) unlink($imageFile);
        if (file_exists($thumbFile)) unlink($thumbFile);
    }

    public static function getImageUrl($type, $image)
    {
        if ($image == null) return null;
        return Storage::disk('public')->url("images/$type/$image");
    }

    public static function getThumbUrl($type, $image)
    {
        if ($image == null) return null;
        return Storage::disk('public')->url("images/$type/thumb/$image");
    }

    public static function generateQR($text)
    {
        $type = ImageHelper::$TYPE_WHOLESALER_FIRM;
        $filename = uniqid('QR-' . rand(), true) . '.png';
        $dir = Storage::disk('public')->path("images/$type/");
        $thumbDir = Storage::disk('public')->path("images/$type/thumb/");

        if (!is_dir($dir)) mkdir($dir, 0755, true);
        if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);

        // image
        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new ImagickImageBackEnd()
        );
        $writer = new Writer($renderer);
        $writer->writeFile($text, $dir . $filename);

        // thumbnail
        $tRenderer = new ImageRenderer(
            new RendererStyle(100),
            new ImagickImageBackEnd()
        );
        $tWriter = new Writer($tRenderer);
        $tWriter->writeFile($text, $thumbDir . $filename);

        return $filename;
    }
}
