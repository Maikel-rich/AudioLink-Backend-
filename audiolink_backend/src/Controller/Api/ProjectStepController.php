<?php

namespace App\Controller\Api;

use App\Entity\Project;
use App\Entity\ProjectStep;
use App\Repository\ProjectRepository;
use App\Repository\ProjectStepRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/project-steps')]
class ProjectStepController extends AbstractController
{
    #[Route('/project/{id}', name: 'api_project_steps_list', methods: ['GET'])]
    public function list(Project $project): JsonResponse
    {
        return $this->json($project->getProjectSteps(), 200, [], [
            'groups' => 'project:read'
        ]);
    }

    #[Route('/project/{id}/current', name: 'api_project_current_step', methods: ['GET'])]
    public function getCurrentStep(Project $project): JsonResponse
    {
        $currentStep = null;

        foreach ($project->getProjectSteps() as $step) {
            if ($step->getStatus() === 'doing') {
                $currentStep = $step;
                break;
            }
        }

        if (!$currentStep) {
            return $this->json(['message' => 'No hay ningún paso en curso (doing)'], 200);
        }

        return $this->json($currentStep, 200, [], [
            'groups' => 'project:read'
        ]);
    }

    #[Route('', name: 'api_project_steps_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        ProjectRepository $projectRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['projectId'], $data['name'], $data['position'])) {
            return $this->json(['error' => 'Faltan parámetros críticos'], 400);
        }

        $project = $projectRepository->find($data['projectId']);
        if (!$project) {
            return $this->json(['error' => 'Proyecto no encontrado'], 404);
        }

        $step = new ProjectStep();
        $step->setProject($project);
        $step->setName($data['name']);
        $step->setPosition((int)$data['position']);
        $step->setStatus($data['status'] ?? 'todo');

        $entityManager->persist($step);
        $entityManager->flush();

        return $this->json([
            'message' => 'Paso creado',
            'id' => $step->getId()
        ], 201);
    }

    #[Route('/{id}/rename', name: 'api_project_steps_rename', methods: ['PATCH'])]
    public function rename(ProjectStep $step, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || empty(trim($data['name']))) {
            return $this->json(['error' => 'El nombre es obligatorio'], 400);
        }

        $step->setName($data['name']);
        $entityManager->flush();

        return $this->json(['message' => 'Nombre actualizado correctamente']);
    }

    #[Route('/{id}/status', name: 'api_project_steps_status', methods: ['PATCH'])]
    public function changeStatus(ProjectStep $step, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;

        $validStatuses = ['todo', 'doing', 'done'];

        if (!$newStatus || !in_array($newStatus, $validStatuses)) {
            return $this->json([
                'error' => 'Estado inválido',
                'allowed' => $validStatuses
            ], 400);
        }

        $step->setStatus($newStatus);
        $entityManager->flush();

        return $this->json(['message' => 'Estado actualizado a ' . $newStatus]);
    }

    #[Route('/{id}', name: 'api_project_steps_delete', methods: ['DELETE'])]
    public function delete(ProjectStep $step, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($step);
        $entityManager->flush();

        return $this->json(null, 204);
    }
}
