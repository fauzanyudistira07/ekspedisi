<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

class Uploads
{
    public static function storePublic(UploadedFile $file, string $directory, ?string $prefix = null): string
    {
        $targetDirectory = public_path('uploads/' . trim($directory, '/'));

        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        $fileName = trim(($prefix ? $prefix . '_' : '') . time() . '_' . $file->getClientOriginalName(), '_');
        $file->move($targetDirectory, $fileName);

        return $fileName;
    }
}
