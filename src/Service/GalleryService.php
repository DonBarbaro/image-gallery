<?php

namespace App\Service;

use App\Api\ApiError;
use App\Exception\ErrorException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\SerializerInterface;

const FILE_PATH = '/files/gallery/';
const ITEMS = '/items.json';
define('GALLERY_DIR_PATH', getcwd() . FILE_PATH);

class GalleryService
{
    public function __construct(private SerializerInterface $serializer)
    {}

    public function createGallery(string $name):void
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
                $apiError = new ApiError(400, ApiError::TYPE_BAD_REQUEST);
                throw new ErrorException($apiError);
            }
            if (!$file->exists($new_gallery))
            {
                $file->mkdir($new_gallery, 0777);
            }else{
                $apiError = new ApiError(409, ApiError::TYPE_GALLERY_WITH_SAME_NAME_ALREADY_EXISTS);
                throw new ErrorException($apiError);
            }
        } catch (IOException $exception) {
            echo "Error creating directory at" . $exception->getPath();
        }
    }

    public function addToItems(string $path, $img, $info)
    {
        // TODO CURRENT_DIR_PATH . $path sa da napisat jednoduchsie a pre vsetko
        $file = new Filesystem();
        $new_dir_path = GALLERY_DIR_PATH . $path;
        $new_file = GALLERY_DIR_PATH . $path . ITEMS;
        $keys = ['path', 'fullPath', 'name', 'modified'];

        $json_content_array = $this->serializer->normalize($img, 'json');

        $filtered_array = array_filter($json_content_array, function ($filtered) use ($keys){ //filtrujem pole aby tam nebol file
            return in_array($filtered, $keys);
        }, ARRAY_FILTER_USE_KEY);

        foreach ($this->serializer->decode(file_get_contents($new_file), 'json') as $value)
        {
            if ($value['path'] == $info->getClientOriginalName())
            {
                $apiError = new ApiError(400, ApiError::TYPE_PHOTO_WITH_SAME_NAME_ALREADY_EXISTS);
                throw new ErrorException($apiError);
            }
        }

        $filtered_content = json_encode(array($filtered_array));

        //prida novy img do {path}.json
        $get_data = file_get_contents($new_file);
        // ked je json prazdny prida text a obrazok
        if ($get_data == '')
        {
            $file->dumpFile($new_file, $filtered_content);
            $info->move($new_dir_path, $info->getClientOriginalName());
        }else{
            //ked json nie je prazdny zoberie data a prida do pola novy item a prida novy obrazok
            $data_to_array = $this->serializer->decode($get_data, 'json');
            array_push($data_to_array, $filtered_array);
            $json = $this->serializer->serialize($data_to_array, 'json');
            $file->dumpFile($new_file, $json);

            $info->move($new_dir_path, $info->getClientOriginalName());
        }
        return $filtered_array;
    }

    public function delete($path, $name): void
    {
        $finder = new Finder();
        $file = new Filesystem();
        $items_json = GALLERY_DIR_PATH . $path . ITEMS;
        $img = GALLERY_DIR_PATH . $path . '/' . $name;

        try {
            if (!$file->exists($img)) {
                $apiError = new ApiError(400, ApiError::TYPE_PHOTO_NOT_FOUND);
                throw new ErrorException($apiError);
            }

            $items_data = file_get_contents($items_json);
            $items_data_array = $this->serializer->decode($items_data, 'json');

            foreach ($items_data_array as $image_data_index => $value)
            {
                $value = $this->serializer->normalize($value, 'array');

                if ($name == $value['path'])
                {
                    unset($items_data_array[$image_data_index]);
                    $reindex_items_data_array = array_values($items_data_array);
                    $json = $this->serializer->serialize($reindex_items_data_array, 'json');
                    $file->dumpFile($items_json, $json);
                }
            }

            foreach ($finder->files()->in(GALLERY_DIR_PATH . $path) as $item)
            {
                if ($file->exists($item->getRealPath()) && $name == $item->getFilename())
                {
                    $file->remove($item->getRealPath());
                }
            }
        } catch (IOExceptionInterface $exception) {
            throw new  \Exception('Unknown error', 500);
        }
    }


}