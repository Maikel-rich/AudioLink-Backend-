<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/cloudinary')]
class CloudinaryController extends AbstractController
{
    #[Route('/signature', name: 'api_cloudinary_signature', methods: ['GET'])]
    public function getSignature(): JsonResponse
    {
        $timestamp = time();
        $apiSecret = $_ENV['CLOUDINARY_API_SECRET'];

        $params = [
            'folder' => 'audiolink_tracks',
            'timestamp' => $timestamp,
        ];

        ksort($params);

        $parameterString = http_build_query($params) . $apiSecret;

        $signature = sha1($parameterString);

        return new JsonResponse([
            'signature' => $signature,
            'timestamp' => $timestamp,
            'api_key' => $_ENV['CLOUDINARY_API_KEY'],
            'cloud_name' => $_ENV['CLOUDINARY_CLOUD_NAME'],
            'folder' => 'audiolink_tracks'
        ]);
    }
}
