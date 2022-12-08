<?php

namespace App\Service;

use Imagine\Gd\Image;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Symfony\Component\Finder\Finder;

class ImageResizeService
{
    public function resize($w, $h, $path, $name)
    {
        $finder = new Finder();
        $current_dir = getcwd();

        foreach ($finder->files()->in($current_dir . '/files/gallery/' . $path) as $item) {
            if (strpos($item->getFilename(), 'jpg') && $name == $item->getFilename()) {
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
