<?php

namespace App\Controller\Api;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/projects')]
class ProjectController extends AbstractController
{
    #[Route('', name: 'api_projects_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Acceso denegado'], 401);
        }

        $conn = $entityManager->getConnection();

        $sql = '
        SELECT 
            p.id, 
            p.title, 
            p.status, 
            p.progress_percentage, 
            p.current_stage_name, 
            p.created_at, 
            p.artist_id, 
            p.producer_id,
            COALESCE(
                (SELECT is_paid 
                 FROM audiolink.service_requests sr 
                 WHERE sr.artist_id = p.artist_id 
                   AND sr.producer_id = p.producer_id 
                   AND sr.status = \'accepted\'
                 ORDER BY sr.created_at DESC 
                 LIMIT 1), 
                0
            ) as is_paid,
            EXISTS(
                SELECT 1 
                FROM audiolink.tracks t 
                WHERE t.project_id = p.id AND t.is_final = true
                LIMIT 1
            ) as has_final_audio
        FROM audiolink.projects p
        WHERE p.artist_id = :userId OR p.producer_id = :userId
    ';

        $resultSet = $conn->executeQuery($sql, ['userId' => $user->getId()]);
        $projects = $resultSet->fetchAllAssociative();

        $responseData = [];
        foreach ($projects as $p) {
            $isPaid = (int)$p['is_paid'] === 2;
            $isProgressComplete = (int)$p['progress_percentage'] === 100;
            $hasFinalAudio = (bool)$p['has_final_audio'];

            $isFinalAudioAvailable = $isPaid && $isProgressComplete && $hasFinalAudio;

            $responseData[] = [
                'id' => (int)$p['id'],
                'title' => $p['title'],
                'status' => $p['status'] ?? 'active',
                'progressPercentage' => (int)($p['progress_percentage'] ?? 0),
                'currentStageName' => $p['current_stage_name'] ?? 'Estudio',
                'isPaid' => $isPaid,
                'isFinalAudioAvailable' => $isFinalAudioAvailable,
                'finalAudioUrl' => $isFinalAudioAvailable ? 'available' : null,
                'createdAt' => $p['created_at'],
                'artist' => $p['artist_id'] ? ['id' => (int)$p['artist_id']] : null,
                'producer' => $p['producer_id'] ? ['id' => (int)$p['producer_id']] : null,
            ];
        }

        return new JsonResponse($responseData, 200);
    }

    private function hasFinalAudioFile(EntityManagerInterface $em, int $projectId): bool
    {
        $conn = $em->getConnection();

        $sql = '
        SELECT COUNT(*) as count 
        FROM audiolink.tracks 
        WHERE project_id = :projectId AND is_final = true
    ';

        $result = $conn->executeQuery($sql, ['projectId' => $projectId]);
        $count = (int)$result->fetchOne();

        return $count > 0;
    }

    private function getProjectPaymentStatus(EntityManagerInterface $em, int $artistId, int $producerId): bool
    {
        $conn = $em->getConnection();

        $sql = '
        SELECT is_paid 
        FROM audiolink.service_requests 
        WHERE artist_id = :artistId 
        AND producer_id = :producerId 
        AND status = :status
        ORDER BY created_at DESC 
        LIMIT 1
    ';

        $result = $conn->executeQuery($sql, [
            'artistId' => $artistId,
            'producerId' => $producerId,
            'status' => 'accepted'
        ]);

        $isPaid = $result->fetchOne();

        return (int)$isPaid === 2;
    }

    #[Route('/{id}', name: 'api_projects_show', methods: ['GET'])]
    public function show(Project $project): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Acceso denegado. Terminal no autenticada.'], 401);
        }

        if ($project->getArtist() !== $user && $project->getProducer() !== $user) {
            return new JsonResponse(['error' => 'No tienes permisos para ver este proyecto.'], 403);
        }

        $artistObj = $project->getArtist();
        $producerObj = $project->getProducer();

        return new JsonResponse([
            'id' => $project->getId(),
            'title' => $project->getTitle(),
            'status' => $project->getStatus(),
            'progressPercentage' => $project->getProgressPercentage() ?? 0,
            'currentStageName' => $project->getCurrentStageName() ?? 'Estudio',
            'isPaid' => $project->isPaid(),
            'createdAt' => $project->getCreatedAt() ? $project->getCreatedAt()->format(\DateTime::ATOM) : null,
            'artist' => $artistObj ? [
                'id' => $artistObj->getId(),
                'fullName' => $artistObj->getFullName(),
                'email' => $artistObj->getEmail()
            ] : null,
            'producer' => $producerObj ? [
                'id' => $producerObj->getId(),
                'fullName' => $producerObj->getFullName(),
                'email' => $producerObj->getEmail()
            ] : null
        ], 200);
    }

    #[Route('', name: 'api_projects_create', methods: ['POST'])]
    public function create(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title']) || !isset($data['artist_id'])) {
            return new JsonResponse(['error' => 'Faltan parámetros obligatorios: title o artist_id'], 400);
        }

        $artist = $userRepository->find($data['artist_id']);
        $producer = isset($data['producer_id']) ? $userRepository->find($data['producer_id']) : null;

        if (!$artist) {
            return new JsonResponse(['error' => 'El artista especificado no existe'], 404);
        }

        $project = new Project();
        $project->setTitle($data['title']);
        $project->setArtist($artist);
        $project->setProducer($producer);
        $project->setStatus($data['status'] ?? 'active');
        $project->setIsPaid($data['isPaid'] ?? false);
        $project->setProgressPercentage($data['progressPercentage'] ?? 0);
        $project->setCurrentStageName($data['currentStageName'] ?? 'Estudio');

        $entityManager->persist($project);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Proyecto creado con éxito',
            'id' => $project->getId()
        ], 201);
    }

    #[Route('/{id}/progress', name: 'api_projects_update_progress', methods: ['PATCH', 'OPTIONS'])]
    public function updateProgress(Project $project, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return new JsonResponse(null, Response::HTTP_OK);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['progressPercentage'])) {
            $project->setProgressPercentage((int)$data['progressPercentage']);
        }

        if (isset($data['currentStageName'])) {
            $project->setCurrentStageName($data['currentStageName']);
        }

        if (isset($data['status'])) {
            $project->setStatus($data['status']);
        }

        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Parámetros de progreso actualizados correctamente.',
            'progressPercentage' => $project->getProgressPercentage(),
            'currentStageName' => $project->getCurrentStageName(),
            'status' => $project->getStatus()
        ], 200);
    }

    #[Route('/{id}/final-audio', name: 'api_projects_upload_final_audio', methods: ['POST'])]
    #[IsGranted('ROLE_PRODUCER')]
    public function uploadFinalAudio(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $project = $em->getRepository(Project::class)->find($id);

        if (!$project) {
            return new JsonResponse(['error' => 'Proyecto no encontrado'], 404);
        }

        $currentUser = $this->getUser();

        if (!$currentUser instanceof User || $project->getProducer()->getId() !== $currentUser->getId()) {
            return new JsonResponse(['error' => 'No tienes permiso para subir el audio final de este proyecto'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['finalAudioUrl']) || empty($data['finalAudioUrl'])) {
            return new JsonResponse(['error' => 'La URL del audio final es requerida'], 400);
        }

        $project->setFinalAudioUrl($data['finalAudioUrl']);
        $project->setStatus('completed');
        $project->setProgressPercentage(100);
        $project->setCurrentStageName('Entregado');

        $em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Audio final subido correctamente',
            'finalAudioUrl' => $project->getFinalAudioUrl()
        ], 200);
    }

    #[Route('/{id}/final-audio/download', name: 'api_projects_download_final_audio', methods: ['GET'])]
    #[IsGranted('ROLE_ARTIST')]
    public function downloadFinalAudio(int $id, EntityManagerInterface $em): JsonResponse
    {
        $project = $em->getRepository(Project::class)->find($id);

        if (!$project) {
            return new JsonResponse(['error' => 'Proyecto no encontrado'], 404);
        }

        $currentUser = $this->getUser();

        if (!$currentUser instanceof User || $project->getArtist()->getId() !== $currentUser->getId()) {
            return new JsonResponse(['error' => 'No tienes permiso para descargar el audio final de este proyecto'], 403);
        }

        if (!$project->getFinalAudioUrl()) {
            return new JsonResponse(['error' => 'El audio final aún no ha sido subido por el productor'], 404);
        }

        return new JsonResponse([
            'downloadUrl' => $project->getFinalAudioUrl(),
            'fileName' => $project->getTitle() . '_final.wav',
            'projectTitle' => $project->getTitle()
        ], 200);
    }

    #[Route('/{id}', name: 'api_projects_get', methods: ['GET'])]
    public function getProject(int $id, EntityManagerInterface $em): JsonResponse
    {
        $project = $em->getRepository(Project::class)->find($id);

        if (!$project) {
            return new JsonResponse(['error' => 'Proyecto no encontrado'], 404);
        }

        $currentUser = $this->getUser();

        if (
            !$currentUser instanceof User ||
            ($project->getArtist()->getId() !== $currentUser->getId() &&
                $project->getProducer()->getId() !== $currentUser->getId())
        ) {
            return new JsonResponse(['error' => 'No tienes permiso para ver este proyecto'], 403);
        }

        $conn = $em->getConnection();

        $sqlPayment = '
        SELECT is_paid 
        FROM audiolink.service_requests 
        WHERE artist_id = :artistId 
        AND producer_id = :producerId 
        AND status = \'accepted\'
        ORDER BY created_at DESC 
        LIMIT 1
    ';

        $paymentResult = $conn->executeQuery($sqlPayment, [
            'artistId' => $project->getArtist()->getId(),
            'producerId' => $project->getProducer()->getId(),
        ]);

        $isPaidValue = $paymentResult->fetchOne();
        $isPaid = (int)$isPaidValue === 2;

        $sqlAudio = '
        SELECT COUNT(*) as count 
        FROM audiolink.tracks 
        WHERE project_id = :projectId AND is_final = true
    ';

        $audioResult = $conn->executeQuery($sqlAudio, ['projectId' => $id]);
        $hasFinalAudio = (int)$audioResult->fetchOne() > 0;

        $isProgressComplete = $project->getProgressPercentage() === 100;
        $isFinalAudioAvailable = $isPaid && $isProgressComplete && $hasFinalAudio;

        return new JsonResponse([
            'id' => $project->getId(),
            'title' => $project->getTitle(),
            'artist' => [
                'id' => $project->getArtist()->getId(),
                'fullName' => $project->getArtist()->getFullName(),
                'profilePicture' => $project->getArtist()->getProfilePicture()
            ],
            'producer' => [
                'id' => $project->getProducer()->getId(),
                'fullName' => $project->getProducer()->getFullName(),
                'profilePicture' => $project->getProducer()->getProfilePicture()
            ],
            'status' => $project->getStatus(),
            'progressPercentage' => $project->getProgressPercentage(),
            'currentStageName' => $project->getCurrentStageName(),
            'isPaid' => $isPaid,
            'isFinalAudioAvailable' => $isFinalAudioAvailable,
            'finalAudioUrl' => $isFinalAudioAvailable ? 'available' : null,
            'createdAt' => $project->getCreatedAt()?->format('Y-m-d H:i:s')
        ], 200);
    }

    #[Route('/{id}/has-final-audio', name: 'api_projects_has_final_audio', methods: ['GET'])]
    public function hasFinalAudio(int $id, EntityManagerInterface $em): JsonResponse
    {
        $project = $em->getRepository(Project::class)->find($id);

        if (!$project) {
            return new JsonResponse(['error' => 'Proyecto no encontrado'], 404);
        }

        $conn = $em->getConnection();
        $sql = 'SELECT COUNT(*) as count FROM audiolink.tracks WHERE project_id = :projectId AND is_final = true';
        $result = $conn->executeQuery($sql, ['projectId' => $id]);
        $count = (int)$result->fetchOne();

        return new JsonResponse([
            'hasFinalAudio' => $count > 0
        ], 200);
    }

    #[Route('/{id}/complete', name: 'api_projects_complete', methods: ['POST'])]
    #[IsGranted('ROLE_ARTIST')]
    public function completeProject(int $id, EntityManagerInterface $em): JsonResponse
    {
        $project = $em->getRepository(Project::class)->find($id);

        if (!$project) {
            return new JsonResponse(['error' => 'Proyecto no encontrado'], 404);
        }

        $currentUser = $this->getUser();

        if (!$currentUser instanceof User || $project->getArtist()->getId() !== $currentUser->getId()) {
            return new JsonResponse(['error' => 'No tienes permiso'], 403);
        }

        if (!$project->getFinalAudioUrl()) {
            return new JsonResponse(['error' => 'El productor aún no ha subido el audio final'], 400);
        }

        $project->setStatus('delivered');
        $project->setCurrentStageName('Entregado y aceptado');
        $em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Proyecto marcado como completado'
        ], 200);
    }
}
