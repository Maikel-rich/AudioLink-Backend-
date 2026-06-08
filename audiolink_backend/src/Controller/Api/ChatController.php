<?php

namespace App\Controller\Api;

use App\Entity\ChatMessage;
use App\Entity\ServiceRequest;
use App\Entity\User;
use App\Repository\ChatMessageRepository;
use App\Repository\ServiceRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/chat', name: 'api_chat_')]
class ChatController extends AbstractController
{
    #[Route('/request/{requestId}', name: 'get_messages', methods: ['GET', 'OPTIONS'])]
    public function getMessages(
        int $requestId,
        ServiceRequestRepository $requestRepository,
        ChatMessageRepository $messageRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return new JsonResponse(null, Response::HTTP_OK);
        }

        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        $request = $requestRepository->find($requestId);
        if (!$request) {
            return new JsonResponse(['error' => 'Solicitud no encontrada'], Response::HTTP_NOT_FOUND);
        }

        if ($request->getArtist()->getId() !== $user->getId() && $request->getProducer()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'No tienes permiso'], Response::HTTP_FORBIDDEN);
        }

        $unreadMessages = $messageRepository->findBy([
            'request' => $request,
            'isRead' => false
        ]);

        foreach ($unreadMessages as $message) {
            if ($message->getSender()->getId() !== $user->getId()) {
                $message->setIsRead(true);
            }
        }
        $em->flush();

        $messages = $messageRepository->findBy(
            ['request' => $request],
            ['createdAt' => 'ASC']
        );

        $responseData = [];
        foreach ($messages as $message) {
            $responseData[] = [
                'id' => $message->getId(),
                'requestId' => $message->getRequest()->getId(),
                'senderId' => $message->getSender()->getId(),
                'senderName' => $message->getSender()->getFullName(),
                'senderRole' => $message->getSender()->getRole() === 0 ? 'producer' : 'artist',
                'message' => $message->getMessage(),
                'isRead' => $message->isRead(),
                'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }

        return new JsonResponse([
            'request' => [
                'id' => $request->getId(),
                'status' => $request->getStatus(),
                'amount' => $request->getAmount(),
                'isPaid' => $request->getIsPaid(),
                'serviceName' => $request->getService()->getName()
            ],
            'messages' => $responseData
        ], Response::HTTP_OK);
    }

    #[Route('/request/{requestId}/send', name: 'send_message', methods: ['POST', 'OPTIONS'])]
    public function sendMessage(
        int $requestId,
        Request $req,
        ServiceRequestRepository $requestRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return new JsonResponse(null, Response::HTTP_OK);
        }

        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($req->getContent(), true);

        if (empty($data['message'])) {
            return new JsonResponse(['error' => 'Mensaje vacío'], Response::HTTP_BAD_REQUEST);
        }

        $request = $requestRepository->find($requestId);
        if (!$request) {
            return new JsonResponse(['error' => 'Solicitud no encontrada'], Response::HTTP_NOT_FOUND);
        }

        if ($request->getArtist()->getId() !== $user->getId() && $request->getProducer()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'No tienes permiso'], Response::HTTP_FORBIDDEN);
        }

        $message = new ChatMessage();
        $message->setRequest($request);
        $message->setSender($user);
        $message->setMessage($data['message']);
        $message->setIsRead(false);

        $em->persist($message);
        $em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Mensaje enviado',
            'messageId' => $message->getId()
        ], Response::HTTP_CREATED);
    }
}
