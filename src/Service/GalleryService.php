<?php

namespace App\Service;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class GalleryService
{
    public function createGalleryService(string $name):void
    {
        $file = new Filesystem();
        $current_dir_path = getcwd();
        try {
            $new_dir_path = $current_dir_path . "/files/gallery";
            $new_gallery = $current_dir_path . '/files/gallery/' . $name;
            if (!$file->exists($new_dir_path)) {
                $file->mkdir($new_dir_path, 0777);
            }
            if (!$file->exists($new_gallery)) {
                $file->mkdir($new_gallery, 0777);
            }
        } catch (IOExceptionInterface $exception) {
            echo "Error creating directory at" . $exception->getPath();
        }
    }

}