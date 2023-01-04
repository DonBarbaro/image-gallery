<?php

namespace App\Controller;

use App\Api\ApiError;
use App\Entity\Gallery;
use App\Entity\Image;
use App\Entity\Item;
use App\Exception\ErrorException;
use App\Service\GalleryService;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\GALLERY_DIR_PATH;
use const App\Service\ITEMS;

class GalleryController extends AbstractController
{
    public function __construct(private GalleryService $galleryService)
    {}

    /*  GET GALLERY  */

    #[Route(path: '/gallery', name: 'galleryGet', methods: 'GET')]
    public function getGallery(): JsonResponse
    {
        $finder = new Finder();
        $file = new Filesystem();

        if(!$file->exists(GALLERY_DIR_PATH))
        {
            $file->mkdir(GALLERY_DIR_PATH);
        }

        foreach ($finder->directories()->in(GALLERY_DIR_PATH) as $file)
        {
            $gallery_path = rawurlencode($file->getFilename());
            $gallery_name = $file->getFilename();
            $data[] = ['path' => $gallery_path, 'name' => $gallery_name];
        }

        if (empty($data))
        {
            $api_error = new ApiError(400, ApiError::TYPE_GALLERY_IS_EMPTY);
            throw new ErrorException($api_error);
        }

        return $this->json(['galleries' => $data],200);
    }

    /*  POST GALLERY  */

    #[Route(path: '/gallery', name: 'galleryPost', methods: 'POST')]
    public function createGallery(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data) || !array_key_exists('name', $data))
        {
            //error len zo zadania
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
                $api_error = new ApiError(400, ApiError::TYPE_GALLERY_NAME_CAN_NOT_CONTAIN);
                throw new ErrorException($api_error);
            }
            $item = new Item();
            $item->setPath(rawurlencode($data['name']));
            $item->setName($data['name']);

            $name = rawurldecode($item->getPath());

            //vytvori novy priecinok files s gallery a gallery.json ak neexistuje
            $this->galleryService->createGallery($name);
        }
        return $this->json(['path' => rawurlencode($name), 'name' => $name], 201);
    }

    /* GALLERY UPLOAD IMAGE */

    #[Route(path: '/gallery/{path}', name: 'uploadImage', methods: 'POST')]
    public function uploadImage(Request $request, SerializerInterface $serializer, string $path, ValidatorInterface $validator): JsonResponse
    {
        $file = new Filesystem();
        $new_dir_path = GALLERY_DIR_PATH . $path;
        $new_file = GALLERY_DIR_PATH . $path . ITEMS;
        //ak neexistuje gallery s nazvom, vyhodí error
        if(!$file->exists(GALLERY_DIR_PATH . $path))
        {
            $api_error = new ApiError(404, ApiError::TYPE_GALLERY_NOT_FOUND);
            throw new ErrorException($api_error);
        }
        try
        {
            if($file->exists($new_dir_path))
            {
                $file->touch($new_file);
            }
        }catch (IOExceptionInterface $exception) {
            throw new Exception($exception->getPath(), 400);
        }

        $uploaded_file= $request->files;
        if (!$uploaded_file->get('file'))
        {
            $apiError = new ApiError(400, ApiError::TYPE_FILE_NOT_FOUND);
            throw new ErrorException($apiError);
        }

        $info = $uploaded_file->get('file');

        $img = new Image();
        $img->setFile($info);
        $img->setPath($info->getClientOriginalName());
        $img->setFullPath(rawurlencode($path).'/'.$img->getPath());
        $img->setName(pathinfo($info->getClientOriginalName(), PATHINFO_FILENAME));
        $img->setModified((date("Y-m-d H:i:s")));

        $errors = $validator->validate($img); //validacia image
        if (count($errors) > 0)
        {
            $errorsString = (string) $errors;
            return $this->json([
                'error' => $errorsString,
                'status' => 400
            ], 400);
        }

        $data = $this->galleryService->addToItems($path, $img, $info);

        return $this->json(['uploaded' => [$data]], 201, ['header' => 'multipart/form-data']);
    }

    /* DELETE GALLERY */

    #[Route(path: '/gallery/{path}', name: 'deleteGallery', methods: 'DELETE')]
    public function deleteGallery(string $path): JsonResponse
    {
        $file = new Filesystem();
        $gallery_dir = GALLERY_DIR_PATH . $path;  // $path automaticky decoduje

        try {
            if ($file->exists($gallery_dir))
            {
                $file->remove($gallery_dir);
                return $this->json('Gallery was deleted', 200) ;
            }else{
                $api_error = new ApiError(404, ApiError::TYPE_GALLERY_DOES_NOT_EXISTS);
                throw new ErrorException($api_error);
            }
        }catch(IOExceptionInterface $exception) {
            throw new \Exception('Unknown error', 500);
        }
    }

     /* DELETE PHOTO */

    #[Route(path: '/gallery/{path}/{name}', name: 'deletePhoto', methods: 'DELETE')]
    public function deletePhoto(string $path, string $name, SerializerInterface $serializer): JsonResponse
    {
            $this->galleryService->delete($path, $name);
            return $this->json('Photo was deleted', 200);
    }

        /* MOVE PHOTO - vlastna featurea */

        #[Route(path: '/gallery/{path}/{name}', name: 'movePhoto', methods: 'POST')]
        public function move(Request $request, string $path, string $name, SerializerInterface $serializer): JsonResponse
        {
            $file = new Filesystem();
            $finder = new Finder();
            $gallery_name = $request->toArray();
            $gallery_path = GALLERY_DIR_PATH . rawurldecode($gallery_name['gallery']);

            try {
                if (!$file->exists($gallery_path))
                {
                    $apiError = new ApiError(400, ApiError::TYPE_DIRECT_GALLERY_DOES_NOT_EXIST);
                    throw new ErrorException($apiError);
                }

                $get_new_items = file_get_contents($gallery_path . ITEMS);
                $new_items_to_array = json_decode($get_new_items);  //pole itemov z novej galerie
                $get_current_items = file_get_contents(GALLERY_DIR_PATH . $path . ITEMS);
                $current_items_to_array = json_decode($get_current_items); //pole itemov zo starej galerie

                foreach ($current_items_to_array as $image_data_index => $value)
                {
                    $value = $serializer->normalize($value, 'array');
                    if ($name == $value['path'])
                    {
                        array_push($new_items_to_array, $current_items_to_array[$image_data_index]);
                        $json = $serializer->serialize($new_items_to_array, 'json');
                        $file->dumpFile($gallery_path . ITEMS, $json);
                    }
                }

                foreach ($finder->files()->in(GALLERY_DIR_PATH . $path) as $item)
                {
                    if ($file->exists($item->getRealPath()) && $name == $item->getFilename()) {
                        $file->copy(GALLERY_DIR_PATH . $path . '/' . $name, $gallery_path . '/' . $name);
                    }
                }
                $this->galleryService->delete($path, $name);  //vymaze obrazk v items

            }catch (IOExceptionInterface $exception){
                throw new HttpException( 500, 'Unknown error' . $exception->getPath());
            }
            return $this->json('Photo from gallery '. $path .' was moved to gallery '. rawurldecode($gallery_name['gallery']), 200);
        }
}