<?php

namespace app\Services\Web;


class FileService
{
    public function uploadFile($file, $destinationPath)
    {
        $fileName = $file->getClientOriginalName();
        $file->move(public_path($destinationPath) , $fileName);
        $newFilePath =  $destinationPath . '/' . $fileName;
        return $newFilePath;
    }

}