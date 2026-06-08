<?php

namespace App\Controller\Api;

use App\Entity\TrackComment;
use App\Repository\TrackCommentRepository;
use App\Repository\TrackRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/comments')]
class TrackCommentController extends AbstractController
{
    #[Route('/track/{trackId}', name: 'api_comments_by_track', methods: ['GET'])]
    public function index(int $trackId, TrackCommentRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $comments = $repository->findBy(['track' => $trackId], ['timestampSeconds' => 'ASC']);
        $json = $serializer->serialize($comments, 'json', ['groups' => 'comment:read']);

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('', name: 'api_comments_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        TrackRepository $trackRepo,
        UserRepository $userRepo
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $track = $trackRepo->find($data['trackId']);
        $user = $userRepo->find($data['userId']);

        if (!$track || !$user) {
            return new JsonResponse(['error' => 'Track o Usuario no encontrado'], 404);
        }

        $comment = new TrackComment();
        $comment->setTrack($track);
        $comment->setUser($user);
        $comment->setContent($data['content']);
        $comment->setTimestampSeconds((float)$data['timestampSeconds']);
        $comment->setIsResolved(false);
        $comment->setCreatedAt(new \DateTime());

        $em->persist($comment);
        $em->flush();

        return new JsonResponse(['message' => 'Comentario añadido', 'id' => $comment->getId()], 201);
    }

    #[Route('/{id}/resolve', name: 'api_comments_resolve', methods: ['PATCH'])]
    public function resolve(TrackComment $comment, EntityManagerInterface $em): JsonResponse
    {
        $comment->setIsResolved(!$comment->isResolved());
        $em->flush();

        return new JsonResponse(['message' => 'Estado del comentario actualizado']);
    }
}
