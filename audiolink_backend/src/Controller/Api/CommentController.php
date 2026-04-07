<?php

namespace App\Controller\Api;

use App\Repository\TrackCommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/comments')]
class CommentController extends AbstractController
{
    #[Route('', name: 'api_comments_list', methods: ['GET'])]
    public function index(TrackCommentRepository $repo): JsonResponse
    {
        $comments = $repo->findAll();
        return $this->json($comments, 200, [], ['groups' => 'comment:read']);
    }
}
