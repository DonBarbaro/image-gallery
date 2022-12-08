<?php

namespace App\Controller;

use App\Entity\Gallery;
use App\Entity\Image;
use App\Entity\Item;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class GalleryController extends AbstractController
{
    /*
     * GET GALLERY
     */

    #[Route(path: '/gallery', name: 'galleryGet', methods: 'GET')]
    public function getGallery(): JsonResponse
    {
        $finder = new Finder();
        $current_dir_path = getcwd();
        $data = [];
        foreach ($finder->directories()->in($current_dir_path.'/files/gallery') as $file)
        {
            $file_name = $file->getRealPath().'/gallery.json';
            $gallery = file_get_contents($file_name);
            $json_data = json_decode($gallery, true);
            $data[] = $json_data;

        }
        return $this->json(['galleries' => $data],200, ['header' => 'application/json']);
    }

    /*
     * POST GALLERY
     */

    #[Route(path: '/gallery', name: 'galleryPost', methods: 'POST')]
    public function createGallery(Request $request ,SerializerInterface $serializer): JsonResponse
    {
        $file = new Filesystem();
        $current_dir_path = getcwd();

        $data = $request->get('name');

        if (strpos($data, '/')) {
            throw new \Exception('Gallery name can not contain "/"', 400);
        }

        $item = new Item();
        $item->setPath(rawurlencode($data));
        $item->setName($data);

        $path = rawurldecode($item->getPath());

        //vytvori novy priecinok files s gallery a gallery.json ak neexistuje
        try {
            $new_dir_path = $current_dir_path . "/files/gallery";
            $new_file = $current_dir_path . '/files/gallery/'.$path.'/gallery.json';
            $new_gallery = $current_dir_path.'/files/gallery/'.$path;
            if(!$file->exists($new_dir_path))
            {
                $file->mkdir($new_dir_path, 0777);
                $file->touch($new_file);
            }
            if(!$file->exists($new_gallery))
            {
                $file->mkdir($new_gallery, 0777);
            }
        }catch (IOExceptionInterface $exception) {
            echo "Error creating directory at" . $exception->getPath();
        }

        $jsonContentArray = $serializer->normalize($item, 'array', ['groups' => 'item']);
        $jsonContentFile = $serializer->serialize($item, 'json');

        $file->dumpFile($new_file, $jsonContentFile);

        return $this->json($jsonContentArray, 201, ['header' => 'application/json']);
    }

    /*
     * GALLERY UPLOAD IMAGE
     */

    #[Route(path: '/gallery/{path}', name: 'uploadImage', methods: 'POST')]
    public function uploadImage(Request $request, SerializerInterface $serializer, string $path): JsonResponse
    {
        $file = new Filesystem();
        $current_dir_path = getcwd();
        //ak neexistuje gallery s nazvom, vyhodí error
        if(!$file->exists($current_dir_path.'/files/gallery/'.$path))
        {
            throw new \Exception('Gallery not found', 404);
        }
        try {
            $new_dir_path = $current_dir_path . "/files/gallery/".$path;
            $new_file = $current_dir_path . "/files/gallery/".$path.'/items.json';
            if($file->exists($new_dir_path))
            {
//                $file->mkdir($new_dir_path, 0777);
                $file->touch($new_file);
            }
        }catch (IOExceptionInterface $exception) {
            throw new Exception($exception->getPath(), 400);
        }

        $uploaded_file= $request->files;
        if (!$uploaded_file->get('file'))
        {
            throw new \Exception('File not found', 400);
        }

        $info = $uploaded_file->get('file');

        $img = new Image();
        $img->setPath($info->getClientOriginalName());
        $img->setFullPath(rawurlencode($path).'/'.$img->getPath());
        $img->setName(pathinfo($info->getClientOriginalName(), PATHINFO_FILENAME));
        $img->setModified((date("Y-m-d H:i:s")));

        // serializujem objekt -> json
        $json_content_file = $serializer->serialize(array($img), 'json');

        $json_content_array = $serializer->normalize($img, 'json');

        //prida novy img do {path}.json
        $get_data = file_get_contents($new_file);
        // ked je json prazdny prida text a obrazok
        if ($get_data == ''){
            $file->dumpFile($new_file, $json_content_file);
            $info->move($new_dir_path, $img->getName().'.jpg');
        }

        //ked json nie je prazdny zoberie data a prida do pola novy obrazok a prida novy obrazok
        if(!$get_data == ''){
            $data_to_array = $serializer->decode($get_data, 'json');
            array_push($data_to_array, $json_content_array);
            $json = $serializer->serialize($data_to_array, 'json');
            $file->dumpFile($new_file, $json);

            // iba jpg obrazky
            $info->move($new_dir_path, $img->getName().'.jpg');
        }

        return $this->json(['uploaded' => [$json_content_array]], 201, ['header' => 'multipart/form-data']);
    }

     /*
      * DELETE GALLERY
      */

    #[Route(path: '/gallery/{path}/{name}', name: 'delete', methods: 'DELETE')]
    public function delete(string $path, string $name = '', SerializerInterface $serializer): JsonResponse
    {
        $file = new Filesystem();
        $finder = new Finder();
        $current_dir_path = getcwd();
        // $path automaticky decoduje
        try {
            $gallery_dir = $current_dir_path . '/files/gallery/'.$path;
            $items_json = $current_dir_path . '/files/gallery/'.$path.'/items.json';
            $img = $current_dir_path.'/files/gallery/'.$path.'/'.$name;
            if ($name == '')
            {
                if ($file->exists($gallery_dir))
                {
                    $file->remove($gallery_dir);
                    return $this->json('Gallery was deleted', 200) ;
                }else{
                    throw new \Exception('Gallery does not exist', 404);
                }
            }else{
                if (!$file->exists($img))
                {
                    throw new \Exception("Photo not found", 404);
                }

                $items_data = file_get_contents($items_json);
                $items_data_array = $serializer->decode($items_data, 'json');

                foreach ($items_data_array as $image_data_index => $value)
                {
                    $value = $serializer->normalize($value, 'array');

                    if ($name == $value['path'])
                    {
                        unset($items_data_array[$image_data_index]);
                        $json = $serializer->serialize($items_data_array, 'json');
                        $file->dumpFile($items_json, $json);
                    }
                }

                foreach ($finder->files()->in($current_dir_path.'/files/gallery/'.$path) as $item)
                {
                    if ($file->exists($item->getRealPath()) && $name == $item->getFilename())
                    {
                        $file->remove($item->getRealPath());
                        return $this->json('Photo was deleted', 200) ;
                    }
                }

            }
        } catch (IOExceptionInterface $exception) {
            throw new  \Exception('Unknown error', 500);
        }

        return $this->json('Gallery/photo was deleted', 200) ;
    }
}