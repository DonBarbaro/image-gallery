<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{
    /*
     * GET GALLERIES AND IMAGES
     */

    #[Route(path: '/gallery/{path}', name: 'getPhotos', methods: 'GET')]
    public function getPhotos(string $path): JsonResponse
    {
        $file = new Filesystem();
        $current_dir = getcwd();
        // $path automaticky decoduje
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
    #[Route(path: '/images/{w}x{h}/{path}', methods: 'GET')]
    public function generateImg(int $w, int $h, string $path): Response
    {
        $file = new Filesystem();
        $current_dir = getcwd();
        $finder = new Finder();
//        $finder->directories()->in()
        $photo = $current_dir.'/files/gallery/'.$path.'/'.$path.'.jpg';


        //response mam uz len dokoncit logiku ku nacitaniu
        $response = new Response();
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, 'miro');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'image/png');
        $response->setContent(file_get_contents($photo));

        return $response;
//        return new Response($photo, 200, ['Content-type' => 'image/jpeg']);
    }
}