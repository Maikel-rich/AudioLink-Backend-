<?php

namespace App\Controller\Api;

use App\Repository\TrackRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tracks')]
class TrackController extends AbstractController
{
    #[Route('', name: 'api_tracks_list', methods: ['GET'])]
    public function list(TrackRepository $trackRepository): JsonResponse
    {
        $tracks = $trackRepository->findAll();
        return $this->json($tracks, 200, [], ['groups' => 'track:read']);
    }
}
