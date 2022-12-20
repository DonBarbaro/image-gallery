<?php

namespace App\Controller;

use App\Service\ImageService;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
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

    public function __construct(private ImageService $imageService)
    {}

    /* GET GALLERIES AND IMAGES */

    #[Route(path: '/gallery/{path}', name: 'getPhotos', methods: 'GET')]
    public function getPhotos(string $path): JsonResponse
    {
        $image_json = $this->imageService->getPhotos($path);
        return $this->json(['gallery' => ['path' => rawurlencode($path), 'name' => $path], 'images' => $image_json] , 200);
    }

    /* GENERATE IMAGE */

    #[Route(path: '/images/{w}x{h}/{path}/{name}', methods: 'GET')]
    public function generateImg(int $w, int $h, string $path, string $name): Response
    {
        $image = $this->imageService->resize($w, $h, $path, $name);
        return new Response($image, 200, ['Content-type' => 'image/jpeg']);
    }

}