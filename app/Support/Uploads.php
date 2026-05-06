<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class Uploads
{
    public static function storePublic(UploadedFile $file, string $directory, ?string $prefix = null): string
    {
        $normalizedDirectory = trim($directory, '/');
        $targetDirectory = public_path('uploads/' . $normalizedDirectory);

        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = strtolower($file->getClientOriginalExtension());
        $safeBaseName = Str::slug($originalName);

        if ($safeBaseName === '') {
            $safeBaseName = 'file';
        }

        $fileName = trim(($prefix ? $prefix . '_' : '') . time() . '_' . $safeBaseName, '_');
        if ($extension !== '') {
            $fileName .= '.' . $extension;
        }

        $file->move($targetDirectory, $fileName);

        return $fileName;
    }

    public static function publicRelativePath(string $directory, string $fileName): string
    {
        return 'uploads/' . trim($directory, '/') . '/' . ltrim($fileName, '/');
    }

    public static function publicPath(string $directory, string $fileName): string
    {
        return public_path(self::publicRelativePath($directory, $fileName));
    }

    public static function publicUrl(string $directory, string $fileName): string
    {
        return asset(self::publicRelativePath($directory, $fileName));
    }

    public static function exists(string $directory, ?string $fileName): bool
    {
        if (empty($fileName)) {
            return false;
        }

        return is_file(self::publicPath($directory, $fileName));
    }

    public static function movePublic(string $fromDirectory, string $toDirectory, string $fileName): ?string
    {
        if (!self::exists($fromDirectory, $fileName)) {
            return null;
        }

        $sourcePath = self::publicPath($fromDirectory, $fileName);
        $targetDirectory = public_path('uploads/' . trim($toDirectory, '/'));

        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $fileName;

        if (@rename($sourcePath, $targetPath)) {
            return $fileName;
        }

        if (@copy($sourcePath, $targetPath)) {
            @unlink($sourcePath);

            return $fileName;
        }

        return null;
    }

    public static function deletePublic(string $directory, ?string $fileName): void
    {
        if (!self::exists($directory, $fileName)) {
            return;
        }

        @unlink(self::publicPath($directory, $fileName));
    }
}
