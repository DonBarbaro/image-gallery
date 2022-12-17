<?php

namespace App\Service;

use Doctrine\DBAL\Exception;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use const App\Controller\FILE_PATH;
use const App\Controller\ITEMS;

class GalleryService
{
    public function createGalleryService(string $name):void
    {
        $file = new Filesystem();
        try {
            $new_dir_path = GALLERY_DIR_PATH ;
            $new_gallery = GALLERY_DIR_PATH . $name;
            if (!$file->exists($new_dir_path)) {
                $file->mkdir($new_dir_path, 0777);
            }
            if (!$file->exists($new_gallery)) {
                $file->mkdir($new_gallery, 0777);
            }else{
                throw new \Exception('Gallery with this name already exists', 409);
            }
        } catch (IOExceptionInterface $exception) {
            echo "Error creating directory at" . $exception->getPath();
        }
    }

    public function uploadImage(string $path)
    {
        // TODO CURRENT_DIR_PATH . $path sa da napisat jednoduchsie a pre vsetko
//        $file = new Filesystem();
//
//        //ak neexistuje gallery s nazvom, vyhodí error
//        if(!$file->exists(CURRENT_DIR_PATH . $path))
//        {
//            throw new \Exception('Gallery not found', 404);
//        }
//
//        try
//        {
//            $new_dir_path = CURRENT_DIR_PATH . $path;
//            $new_file = CURRENT_DIR_PATH . $path . ITEMS;
//            if($file->exists($new_dir_path))
//            {
////                $file->mkdir($new_dir_path, 0777);
//                $file->touch($new_file);
//            }
//        }catch (IOExceptionInterface $exception) {
//            throw new Exception($exception->getPath(), 400);
//        }
    }

}