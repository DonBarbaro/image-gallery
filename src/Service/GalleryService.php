<?php

namespace App\Service;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\SerializerInterface;
use const App\Controller\ITEMS;
use const App\Controller\FILE_PATH;

define('GALLERY_DIR_PATH', getcwd() . FILE_PATH);


class GalleryService
{
    public function __construct(private SerializerInterface $serializer, Request $request)
    {
    }

    public function createGalleryService(string $name):void
    {
        $file = new Filesystem();
        try {
            $new_dir_path = GALLERY_DIR_PATH ;
            $new_gallery = GALLERY_DIR_PATH . $name;
            if (!$file->exists($new_dir_path))
            {
                $file->mkdir($new_dir_path, 0777);
            }
            if (!$name)
            {
                throw new HttpException(400, 'Bad request');
            }
            if (!$file->exists($new_gallery))
            {
                $file->mkdir($new_gallery, 0777);
            }else{
                throw new HttpException(409, 'Gallery with this name already exists');
            }
        } catch (IOExceptionInterface $exception) {
            echo "Error creating directory at" . $exception->getPath();
        }
    }

    public function addToItems(string $path, $img, $info)
    {
        // TODO CURRENT_DIR_PATH . $path sa da napisat jednoduchsie a pre vsetko
        $file = new Filesystem();
        $new_dir_path = GALLERY_DIR_PATH . $path;
        $new_file = GALLERY_DIR_PATH . $path . ITEMS;
        // serializujem objekt -> json
        $json_content_file = $this->serializer->serialize(array($img), 'json');
        $json_content_array = $this->serializer->normalize($img, 'json');

        //prida novy img do {path}.json
        $get_data = file_get_contents($new_file);
        // ked je json prazdny prida text a obrazok
        if ($get_data == ''){
            $file->dumpFile($new_file, $json_content_file);
            $info->move($new_dir_path, $info->getClientOriginalName());
        }else{ //ked json nie je prazdny zoberie data a prida do pola novy item a prida novy obrazok
            $data_to_array = $this->serializer->decode($get_data, 'json');
            array_push($data_to_array, $json_content_array);
            $json = $this->serializer->serialize($data_to_array, 'json');
            $file->dumpFile($new_file, $json);
            $info->move($new_dir_path, $info->getClientOriginalName());
        }
        return $json_content_array;
    }

    public function delete($path, $name):void
    {
        $finder = new Finder();
        $file = new Filesystem();

        try {
            $items_json = GALLERY_DIR_PATH . $path . ITEMS;
            $img = GALLERY_DIR_PATH . $path . '/' . $name;
            if (!$file->exists($img)) {
                throw new HttpException(404, 'Photo not found');
            }

            $items_data = file_get_contents($items_json);
            $items_data_array = $this->serializer->decode($items_data, 'json');

            foreach ($items_data_array as $image_data_index => $value) {
                $value = $this->serializer->normalize($value, 'array');

                if ($name == $value['path']) {
                    unset($items_data_array[$image_data_index]);
                    $json = $this->serializer->serialize($items_data_array, 'json');
                    $file->dumpFile($items_json, $json);
                }
            }

            foreach ($finder->files()->in(GALLERY_DIR_PATH . $path) as $item) {
                if ($file->exists($item->getRealPath()) && $name == $item->getFilename()) {
                    $file->remove($item->getRealPath());
                }
            }
        } catch (IOExceptionInterface $exception) {
            throw new  \Exception('Unknown error', 500);
        }
    }


}