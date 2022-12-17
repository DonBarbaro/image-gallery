<?php

namespace App\Service;

use Imagine\Gd\Image;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use const App\Controller\FILE_PATH;
use const App\Controller\ITEMS;


class ImageService
{

    public function getPhotos(string $path)
    {
        $file = new Filesystem();
        // $path automaticky decoduje
        try {
            $gallery_file = GALLERY_DIR_PATH .  $path;
            $image_file = GALLERY_DIR_PATH . $path . ITEMS;

            if ($file->exists([$gallery_file, $image_file]))
            {
                $image = file_get_contents($image_file);
                $image_json = json_decode($image);
            }else{
                throw new \Exception('Gallery does not exists', 404);
            }
        }catch(IOExceptionInterface $exception) {
            throw new \Exception('WRONG', 500);
        }
        return $image_json;
    }

    public function resize($w, $h, $path, $name)
    {
        $file = new Filesystem();
        $finder = new Finder();

        if (!$file->exists(GALLERY_DIR_PATH .  $path . '/' . $name))
        {
            throw new \Exception("Photo not found", 404);
        }

        foreach ($finder->files()->in(GALLERY_DIR_PATH . $path) as $item) {

            $extensions = ['jpg', 'png', 'jpeg'];
            if (in_array($item->getExtension(), $extensions) && $name == $item->getFilename()) {
                if ($w < 0 || $w > 9000 || $h < 0 || $h > 9000 || ($w == 0 && $h == 0)) {
                    throw new \Exception("The photo preview can't be generated", 500);
                }
                list($original_w, $original_h) = getimagesize($item);

                if ($w == 0 && $h != 0) {
                    $w = $original_w;
                } elseif ($w != 0 && $h == 0) {
                    $h = $original_h;
                }

                $photo = $item->getRealPath();
                $imagine = new Imagine();
                $image = $imagine->open($photo);
                $image->resize(new Box($w, $h));
            }
        }
        return $image;
    }
}
