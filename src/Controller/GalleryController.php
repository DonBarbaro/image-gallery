<?php

namespace App\Controller;

use App\Entity\Gallery;
use App\Entity\Image;
use App\Entity\Item;
use App\Service\GalleryService;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraint as Assert;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

const FILE_PATH = '/files/gallery/';
const ITEMS = '/items.json';
define('GALLERY_DIR_PATH', getcwd() . FILE_PATH);

class GalleryController extends AbstractController
{
    public function __construct(private GalleryService $galleryService)
    {}

    /*
     * GET GALLERY
     */

    #[Route(path: '/gallery', name: 'galleryGet', methods: 'GET')]
    public function getGallery(): JsonResponse
    {
        $finder = new Finder();

        foreach ($finder->directories()->in(GALLERY_DIR_PATH) as $file)
        {
            $gallery_path = rawurlencode($file->getFilename());
            $gallery_name = $file->getFilename();
            $data[] = ['path' => $gallery_path, 'name' => $gallery_name];
        }
        return $this->json(['galleries' => $data],200);
    }

    /*
     * POST GALLERY
     */

    #[Route(path: '/gallery', name: 'galleryPost', methods: 'POST')]
    public function createGallery(Request $request): JsonResponse
    {

        $data = json_decode($request->getContent(), true);

        if (!isset($data) || !in_array('name', $data))
        {
            //TODO vyriesit podmineku bo nefunguje
            return new JsonResponse([
                'code' => 400,
                'payload' => [
                    'paths' => ["name"],
                    'validator' => 'required',
                    'example' => null,
                ],
                'name' => 'INVALID_SCHEMA',
                'description' => "Bad JSON object: u'name' is a required property"
            ], 400);
        }else{
            if (strpos($data['name'], '/')) {
                throw new HttpException(400, 'Gallery name can not contain "/"');
            }
            $item = new Item();
            $item->setPath(rawurlencode($data['name']));
            $item->setName($data['name']);

            $name = rawurldecode($item->getPath());

            //vytvori novy priecinok files s gallery a gallery.json ak neexistuje
            $this->galleryService->createGalleryService($name);
        }
        return $this->json(['path' => rawurlencode($name), 'name' => $name], 201);
    }

    /*
     * GALLERY UPLOAD IMAGE
     */

    #[Route(path: '/gallery/{path}', name: 'uploadImage', methods: 'POST')]
    public function uploadImage(Request $request, SerializerInterface $serializer, string $path): JsonResponse
    {
        $file = new Filesystem();

        //ak neexistuje gallery s nazvom, vyhodí error
        if(!$file->exists(GALLERY_DIR_PATH . $path))
        {
            throw new HttpException(404, 'Gallery not found');
        }

        try
        {
            $new_dir_path = GALLERY_DIR_PATH . $path;
            $new_file = GALLERY_DIR_PATH . $path . ITEMS;
            if($file->exists($new_dir_path))
            {
//                $file->mkdir($new_dir_path, 0777);
                $file->touch($new_file);
            }
        }catch (IOExceptionInterface $exception) {
            throw new Exception($exception->getPath(), 400);
        }
//        $this->galleryService->uploadImage($path);

        $uploaded_file= $request->files;

        if (!$uploaded_file->get('file'))
        {
            throw new HttpException(400, 'File not found');
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
            $info->move($new_dir_path, $info->getClientOriginalName());
        }else{ //ked json nie je prazdny zoberie data a prida do pola novy item a prida novy obrazok
            $data_to_array = $serializer->decode($get_data, 'json');
            array_push($data_to_array, $json_content_array);
            $json = $serializer->serialize($data_to_array, 'json');
            $file->dumpFile($new_file, $json);
            $info->move($new_dir_path, $info->getClientOriginalName());
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
        // $path automaticky decoduje
        try {
            $gallery_dir = GALLERY_DIR_PATH . $path;
            $items_json = GALLERY_DIR_PATH . $path . ITEMS ;
            $img = GALLERY_DIR_PATH . $path . '/'. $name;
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

                foreach ($finder->files()->in(GALLERY_DIR_PATH . $path) as $item)
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