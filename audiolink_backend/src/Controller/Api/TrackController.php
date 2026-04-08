<?php

namespace App\Controller\Api;

use App\Entity\Track;
use App\Repository\ProjectRepository;
use App\Repository\TrackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/tracks')]
class TrackController extends AbstractController
{
    #[Route('', name: 'api_tracks_list', methods: ['GET'])]
    public function list(TrackRepository $trackRepository): JsonResponse
    {
        $tracks = $trackRepository->findAll();
        return $this->json($tracks, 200, [], ['groups' => 'track:read']);
    }

    #[Route('/project/{projectId}', name: 'api_project_tracks', methods: ['GET'])]
    public function listByProject(int $projectId, TrackRepository $repository): JsonResponse
    {
        $tracks = $repository->findBy(['project' => $projectId], ['createdAt' => 'DESC']);
        return $this->json($tracks, 200, [], ['groups' => 'track:read']);
    }

    #[Route('/{id}', name: 'api_tracks_show', methods: ['GET'])]
    public function show(Track $track): JsonResponse
    {
        return $this->json($track, 200, [], ['groups' => 'track:read']);
    }

    #[Route('', name: 'api_tracks_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        ProjectRepository $projectRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['projectId'], $data['cloudinaryUrl'])) {
            return new JsonResponse(['error' => 'Faltan parámetros obligatorios'], 400);
        }

        $project = $projectRepository->find($data['projectId']);
        if (!$project) {
            return new JsonResponse(['error' => 'Proyecto no encontrado'], 404);
        }

        $track = new Track();
        $track->setProject($project);
        $track->setCloudinaryUrl($data['cloudinaryUrl']);
        $track->setVersionName($data['versionName'] ?? 'Nueva Versión');
        $track->setStatus($data['status'] ?? 'pendiente');
        $track->setIsFinal($data['isFinal'] ?? false);
        $track->setFileSizeMb($data['fileSizeMb'] ?? null);
        $track->setCreatedAt(new \DateTime());

        $entityManager->persist($track);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Track subido correctamente', 'id' => $track->getId()], 201);
    }

    #[Route('/{id}/status', name: 'api_tracks_update_status', methods: ['PATCH'])]
    public function updateStatus(Track $track, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['status'])) {
            $track->setStatus($data['status']);
        }

        if (isset($data['isFinal'])) {
            $track->setIsFinal((bool)$data['isFinal']);
        }

        $entityManager->flush();

        return new JsonResponse(['message' => 'Estado del track actualizado']);
    }

    #[Route('/{id}', name: 'api_tracks_delete', methods: ['DELETE'])]
    public function delete(Track $track, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($track);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Track eliminado'], 204);
    }
}
