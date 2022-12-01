<?php

namespace App\Controller;

//require_once '../vendor/autoload.php';

use App\Entity\Gallery;
use App\Entity\Image;
use App\Entity\Item;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
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
        $file = new Filesystem();
        $current_dir_path = getcwd();
        $new_file = $current_dir_path . "/files/gallery/gallery.json";
            if($file->exists($new_file))
            {
                $gallery = file_get_contents($new_file);
                $json_data = json_decode($gallery, true);
            }else{
                throw new \Exception('Unknown error', 500);
            }
        return $this->json($json_data,200, ['header' => 'application/json']);
    }

    /*
     * POST GALLERY
     */

    #[Route(path: '/gallery', name: 'galleryPost', methods: 'POST')]
    public function createGallery(Request $request ,SerializerInterface $serializer): JsonResponse
    {
        $file = new Filesystem();
        $current_dir_path = getcwd();
        //vytvori novy priecinok files s gallery a gallery.json ak neexistuje
        try {
            $new_dir_path = $current_dir_path . "/files/gallery";
            $new_file = $current_dir_path . "/files/gallery/gallery.json";
            if(!$file->exists($new_dir_path))
            {
                $file->mkdir($new_dir_path, 0777);
                $file->touch($new_file);
            }
        }catch (IOExceptionInterface $exception) {
            echo "Error creating directory at" . $exception->getPath();
        }

        $item = $this->fillUp($request);
        $item_data = $item->getGalleries();

        $jsonContent = $serializer->normalize($item_data, 'json', ['groups' => 'item']);

        $jsonContentFile = $serializer->serialize($item, 'json');
        $file->dumpFile($new_file, $jsonContentFile);

        return $this->json($jsonContent, 201, ['header' => 'application/json']);
    }

    public function fillUp(Request $request): Gallery
    {
        $data = $request->get('name');

        if (strpos($data, '/'))
        {
            throw new \Exception('Gallery name can not contain "/"', 400);
        }

        $item = new Item();
        $item->setPath($data);
        $item->setName($data);

        $gallery = new Gallery();
        $gallery->addItem($item);

        return $gallery;
    }

    /*
     * GALLERY UPLOAD IMAGE
     */

    #[Route(path: '/gallery/{path}', name: 'uploadImage', methods: 'POST')]
    public function uploadImage(Request $request, SerializerInterface $serializer, string $path): JsonResponse
    {
        $file = new Filesystem();
        $current_dir_path = getcwd();
        //vytvori novy priecinok files s gallery a gallery.json ak neexistuje
        if($file->exists($current_dir_path.'/files/gallery'.$path))
        {
            throw new \Exception('Gallery not found', 404);
        }

        try {
            $new_dir_path = $current_dir_path . "/files/gallery/".$path;
            $new_file = $current_dir_path . "/files/gallery/".$path."/image.json";
            if(!$file->exists($new_dir_path))
            {
                $file->mkdir($new_dir_path, 0777);
                $file->touch($new_file);
            }
        }catch (IOExceptionInterface $exception) {
            throw new Exception($exception->getPath(), 400);
        }

        $uploaded_file= $request->files;
        if (!$uploaded_file)
        {
            throw new \Exception('File not found', 400);
        }


        $info = $uploaded_file->get('file');

        $img = new Image();
        $img->setPath($info->getClientOriginalName());
        $img->setFullPath($path.'/'.$img->getPath());
        $img->setName(pathinfo($info->getClientOriginalName(), PATHINFO_FILENAME));
        $img->setModified((date("Y-m-d H:i:s")));

        $jsonContentFile = $serializer->serialize($img, 'json');
        $file->dumpFile($new_file, $jsonContentFile);

        return $this->json(['uploaded' => [$img]], 201, ['header' => 'multipart/form-data']);
    }


}