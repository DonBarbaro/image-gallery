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

        if (!isset($data) || !array_key_exists('name', $data))
        {
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
                $file->touch($new_file);
            }
        }catch (IOExceptionInterface $exception) {
            throw new Exception($exception->getPath(), 400);
        }

        //TODO tu treba zvalidovat obrazok
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

        $data = $this->galleryService->addToItems($path, $img, $info);

        return $this->json(['uploaded' => [$data]], 201, ['header' => 'multipart/form-data']);
    }

    /*
     * DELETE GALLERY
     */

    #[Route(path: '/gallery/{path}', name: 'deleteGallery', methods: 'DELETE')]
    public function deleteGallery(string $path):JsonResponse
    {
        $file = new Filesystem();
        // $path automaticky decoduje
        $gallery_dir = GALLERY_DIR_PATH . $path;
        if ($file->exists($gallery_dir))
        {
            $file->remove($gallery_dir);
            return $this->json('Gallery was deleted', 200) ;
        }else{
            throw new HttpException(404, 'Gallery does not exist');
        }
    }

     /*
      * DELETE PHOTO
      */

    #[Route(path: '/gallery/{path}/{name}', name: 'deletePhoto', methods: 'DELETE')]
    public function deletePhoto(string $path, string $name, SerializerInterface $serializer): JsonResponse
    {
        $file = new Filesystem();
        $finder = new Finder();
        // $path automaticky decoduje
            $this->galleryService->delete($path, $name);
            return $this->json('Photo was deleted', 200);
    }

        /**
         * MOVE PHOTO
         */

        #[Route(path: '/gallery/{path}/{name}', name: 'movePhoto', methods: 'PUT')]
        public function move(Request $request, string $path, string $name):JsonResponse
        {

            $file = new Filesystem();
            $finder = new Finder();

            try {
                $gallery_name = $request->toArray();
                $gallery_path = GALLERY_DIR_PATH . $gallery_name['gallery'];

                if (!$file->exists($gallery_path))
                {
                    throw new HttpException(404, 'Direct gallery does not exists');
                }

                $this->galleryService->delete($path, $name);


                try {
                    foreach ($finder->files()->in(GALLERY_DIR_PATH . $path) as $item) {
                        if ($file->exists($item->getRealPath()) && $name == $item->getFilename()) {
                            $file->copy(GALLERY_DIR_PATH . $path . '/' . $name, $gallery_path . '/' . $name);
                            $file->remove(GALLERY_DIR_PATH . $path . '/' . $name);
                        }
                    }
                }catch (IOExceptionInterface $exception){
                    throw new HttpException( 500, 'File was not removed'. $exception->getPath());
                }
            }catch (IOExceptionInterface $exception){
                throw new HttpException( 500, 'Unknown error in' . $exception->getPath());
            }
            return $this->json('Photo from gallery '. $path .' was moved to gallery '. $gallery_name['gallery'], 200);
        }
}