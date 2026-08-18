<?php

namespace App\Services\Web;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    public function moveFile(string $tempPath, string $destination): string
    {
        $fileName = Str::random(40) . '.' . pathinfo($tempPath, PATHINFO_EXTENSION);
        $newPath = $destination . '/' . $fileName;

        Storage::disk('public')->move($tempPath, $newPath);

        return $newPath;
    }

}