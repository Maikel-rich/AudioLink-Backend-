<?php

namespace App\Controller\Api;

use App\Entity\Message;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/messages')]
class MessageController extends AbstractController
{
    #[Route('/chat/{user1}/{user2}', name: 'api_messages_history', methods: ['GET'])]
    public function history(int $user1, int $user2, MessageRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $messages = $repository->findConversation($user1, $user2);

        $json = $serializer->serialize($messages, 'json', ['groups' => 'message:read']);
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('', name: 'api_messages_send', methods: ['POST'])]
    public function send(
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepo
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $sender = $userRepo->find($data['senderId']);
        $receiver = $userRepo->find($data['receiverId']);

        if (!$sender || !$receiver) {
            return new JsonResponse(['error' => 'Remitente o destinatario no encontrado'], 404);
        }

        $message = new Message();
        $message->setSender($sender);
        $message->setReceiver($receiver);
        $message->setContent($data['content']);
        $message->setIsRead(false);
        $message->setCreatedAt(new \DateTime());

        $em->persist($message);
        $em->flush();

        return new JsonResponse(['message' => 'Mensaje enviado', 'id' => $message->getId()], 201);
    }
}
