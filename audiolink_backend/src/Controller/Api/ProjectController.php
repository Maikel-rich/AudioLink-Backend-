<?php

namespace App\Controller\Api;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/projects')]
class ProjectController extends AbstractController
{
    #[Route('', name: 'api_projects_index', methods: ['GET'])]
    public function index(ProjectRepository $projectRepository, SerializerInterface $serializer): JsonResponse
    {
        $projects = $projectRepository->findAll();
        $json = $serializer->serialize($projects, 'json', ['groups' => 'project:read']);

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/{id}', name: 'api_projects_show', methods: ['GET'])]
    public function show(Project $project, SerializerInterface $serializer): JsonResponse
    {
        $json = $serializer->serialize($project, 'json', ['groups' => 'project:read']);
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('', name: 'api_projects_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title'], $data['artistId'], $data['producerId'])) {
            return new JsonResponse(['error' => 'Título, Artista y Productor son obligatorios'], 400);
        }

        $artist = $userRepository->find($data['artistId']);
        $producer = $userRepository->find($data['producerId']);

        if (!$artist || !$producer) {
            return new JsonResponse(['error' => 'Artista o Productor no encontrado'], 404);
        }

        $project = new Project();
        $project->setTitle($data['title']);
        $project->setArtist($artist);
        $project->setProducer($producer);
        $project->setStatus($data['status'] ?? 'active');
        $project->setIsPaid($data['isPaid'] ?? false);
        $project->setProgressPercentage($data['progressPercentage'] ?? 0);
        $project->setCurrentStageName($data['currentStageName'] ?? 'Pre-producción');

        $entityManager->persist($project);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Proyecto creado con éxito',
            'id' => $project->getId()
        ], 201);
    }

    #[Route('/{id}/progress', name: 'api_projects_update_progress', methods: ['PATCH'])]
    public function updateProgress(Project $project, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['progressPercentage'])) {
            $project->setProgressPercentage((int)$data['progressPercentage']);
        }

        if (isset($data['status'])) {
            $project->setStatus($data['status']);
        }

        if (isset($data['currentStageName'])) {
            $project->setCurrentStageName($data['currentStageName']);
        }

        $entityManager->flush();

        return new JsonResponse(['message' => 'Progreso del proyecto actualizado']);
    }
}
