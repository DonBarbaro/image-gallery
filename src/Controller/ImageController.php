<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{
    #[Route(path: '/gallery/{path}', name: 'getPhotos', methods: 'GET')]
    public function getPhotos(string $path): JsonResponse
    {
        $file = new Filesystem();
        $current_dir = getcwd();

        try {
            $gallery_file = $current_dir.'/files/gallery/'.$path.'/'.'gallery.json';
            $image_file = $current_dir.'/files/gallery/'.$path.'/'.$path.'.json';

            if ($file->exists([$gallery_file, $image_file]))
            {
                $gallery = file_get_contents($gallery_file);
                $gallery_json = json_decode($gallery);
                $image = file_get_contents($image_file);
                $image_json = json_decode($image);
            }else{
                throw new \Exception('Gallery does not exists', 404);
            }
        }catch(IOExceptionInterface $exception) {
            throw new \Exception('WRONG', 500);
        }
        // TODO treba osetrit abz sa pri singel image zobrazovalo ako pole
        return $this->json(['gallery' => $gallery_json, 'images' => $image_json] , 200);
    }
//    #[Route(path: '/images/{w}x{h}/{path}')]
//    public function generateImg(int $w, int $h, string $path): Response
//    {
//
//    }
}