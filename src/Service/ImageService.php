<?php

namespace App\Service;

use App\Api\ApiError;
use App\Exception\ErrorException;
use Imagine\Gd\Image;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Exception\HttpException;
use const App\Controller\FILE_PATH;
use const App\Controller\ITEMS;

//define('GALLERY_DIR_PATH', getcwd() . FILE_PATH);

class ImageService
{
    /* GET GALLERY AND ALL IMAGES INCLUDED SERVICE */

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
                $apiError = new ApiError(404, ApiError::TYPE_GALLERY_DOES_NOT_EXISTS);
                throw new ErrorException($apiError);
            }
        }catch(IOExceptionInterface $exception) {
            throw new \Exception('Unknown error', 500);
        }
        return $image_json;
    }

    /* RESIZE IMAGE SERVICE */

    public function resize($w, $h, $path, $name)
    {
        $file = new Filesystem();
        $finder = new Finder();

        if (!$file->exists(GALLERY_DIR_PATH .  $path . '/' . $name))
        {
            $apiError = new ApiError(404, ApiError::TYPE_PHOTO_NOT_FOUND);
            throw new ErrorException($apiError);
        }

        foreach ($finder->files()->in(GALLERY_DIR_PATH . $path) as $item) {

            $extensions = ['jpg', 'png', 'jpeg'];
            if (in_array($item->getExtension(), $extensions) && $name == $item->getFilename()) {
                if ($w < 0 || $w > 9000 || $h < 0 || $h > 9000 || ($w == 0 && $h == 0)) {
                    $apiError = new ApiError(500, ApiError::TYPE_PHOTO_PREVIEW_CANT_BE_GENERATED);
                    throw new ErrorException($apiError);
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
