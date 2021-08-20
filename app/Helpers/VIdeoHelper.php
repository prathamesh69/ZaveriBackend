<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class VideoHelper
{
    public static $TYPE_POST = 'post';

    public static function save(UploadedFile $file, $type = 'other')
    {
        $filename = $file->hashName();
        $dir = Storage::disk('public')->path("videos/$type/");

        if (!is_dir($dir)) mkdir($dir, 0755, true);

        // file
        $file->storeAs("videos/$type", $filename, 'public');

        return $filename;
    }

    public static function delete($type, $file)
    {
        Storage::disk('public')->delete("videos/$type/$file");
    }

    public static function getUrl($type, $file)
    {
        if ($file == null) return null;
        return Storage::disk('public')->url("videos/$type/$file");
    }
}
