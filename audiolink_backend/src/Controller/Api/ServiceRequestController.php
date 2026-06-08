<?php

namespace App\Controller\Api;

use App\Entity\ServiceRequest;
use App\Entity\ChatMessage;
use App\Entity\ProducerService;
use App\Entity\User;
use App\Entity\Project;
use App\Repository\ServiceRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/requests', name: 'api_requests_')]
class ServiceRequestController extends AbstractController
{
    #[Route('', name: 'create', methods: ['POST', 'OPTIONS'])]
    public function createRequest(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return new JsonResponse(null, Response::HTTP_OK);
        }

        $artist = $this->getUser();

        if (!$artist instanceof User || $artist->getRole() !== User::ROLE_ARTIST) {
            return new JsonResponse(['error' => 'Acceso denegado. Se requiere rol de artista.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['producerId']) || empty($data['serviceId'])) {
            return new JsonResponse(['error' => 'Datos incompletos: producerId y serviceId son requeridos'], Response::HTTP_BAD_REQUEST);
        }

        $producer = $em->getRepository(User::class)->find($data['producerId']);
        if (!$producer || $producer->getRole() !== User::ROLE_PRODUCER) {
            return new JsonResponse(['error' => 'Productor no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $service = $em->getRepository(ProducerService::class)->find($data['serviceId']);
        if (!$service) {
            return new JsonResponse(['error' => 'Servicio no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $existingRequest = $em->getRepository(ServiceRequest::class)->findOneBy([
            'artist' => $artist,
            'producer' => $producer,
            'service' => $service,
            'status' => ServiceRequest::STATUS_PENDING
        ]);

        if ($existingRequest) {
            return new JsonResponse(['error' => 'Ya tienes una solicitud pendiente para este servicio'], Response::HTTP_CONFLICT);
        }

        $serviceRequest = new ServiceRequest();
        $serviceRequest->setArtist($artist);
        $serviceRequest->setProducer($producer);
        $serviceRequest->setService($service);
        $serviceRequest->setAmount($service->getPrice());
        $serviceRequest->setMessage($data['message'] ?? null);
        $serviceRequest->setProjectDetails($data['projectDetails'] ?? null);
        $serviceRequest->setStatus(ServiceRequest::STATUS_PENDING);
        $serviceRequest->setIsPaid(ServiceRequest::PAYMENT_NOT_PAID);

        $em->persist($serviceRequest);

        $initialMessage = new ChatMessage();
        $initialMessage->setRequest($serviceRequest);
        $initialMessage->setSender($artist);
        $initialMessage->setMessage($data['message'] ?? "📨 He enviado una solicitud para el servicio: {$service->getName()}");
        $em->persist($initialMessage);

        $em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Solicitud enviada correctamente',
            'requestId' => $serviceRequest->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('', name: 'get_requests', methods: ['GET'])]
    public function getRequests(
        ServiceRequestRepository $repository
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->getRole() === User::ROLE_ARTIST) {
            $requests = $repository->findBy(['artist' => $user], ['createdAt' => 'DESC']);
        } elseif ($user->getRole() === User::ROLE_PRODUCER) {
            $requests = $repository->findBy(['producer' => $user], ['createdAt' => 'DESC']);
        } else {
            return new JsonResponse(['error' => 'Rol no válido'], Response::HTTP_FORBIDDEN);
        }

        $responseData = [];
        foreach ($requests as $request) {
            $responseData[] = [
                'id' => $request->getId(),
                'artist' => [
                    'id' => $request->getArtist()->getId(),
                    'name' => $request->getArtist()->getFullName(),
                    'avatar' => $request->getArtist()->getProfilePicture()
                ],
                'producer' => [
                    'id' => $request->getProducer()->getId(),
                    'name' => $request->getProducer()->getFullName(),
                    'avatar' => $request->getProducer()->getProfilePicture()
                ],
                'service' => [
                    'id' => $request->getService()->getId(),
                    'name' => $request->getService()->getName(),
                    'price' => $request->getService()->getPrice()
                ],
                'status' => $request->getStatus(),
                'message' => $request->getMessage(),
                'projectDetails' => $request->getProjectDetails(),
                'amount' => $request->getAmount(),
                'isPaid' => $request->getIsPaid(),
                'createdAt' => $request->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $request->getUpdatedAt()?->format('Y-m-d H:i:s')
            ];
        }

        return new JsonResponse($responseData, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'get_request', methods: ['GET'])]
    public function getRequest(
        int $id,
        ServiceRequestRepository $repository
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        $request = $repository->find($id);
        if (!$request) {
            return new JsonResponse(['error' => 'Solicitud no encontrada'], Response::HTTP_NOT_FOUND);
        }

        if ($request->getArtist()->getId() !== $user->getId() && $request->getProducer()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'No tienes permiso'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse([
            'id' => $request->getId(),
            'artist' => [
                'id' => $request->getArtist()->getId(),
                'name' => $request->getArtist()->getFullName(),
                'avatar' => $request->getArtist()->getProfilePicture()
            ],
            'producer' => [
                'id' => $request->getProducer()->getId(),
                'name' => $request->getProducer()->getFullName(),
                'avatar' => $request->getProducer()->getProfilePicture()
            ],
            'service' => [
                'id' => $request->getService()->getId(),
                'name' => $request->getService()->getName(),
                'price' => $request->getService()->getPrice()
            ],
            'status' => $request->getStatus(),
            'message' => $request->getMessage(),
            'projectDetails' => $request->getProjectDetails(),
            'amount' => $request->getAmount(),
            'isPaid' => $request->getIsPaid(),
            'createdAt' => $request->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $request->getUpdatedAt()?->format('Y-m-d H:i:s')
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/accept', name: 'accept', methods: ['PATCH', 'OPTIONS'])]
    public function acceptRequest(
        int $id,
        ServiceRequestRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return new JsonResponse(null, Response::HTTP_OK);
        }

        $user = $this->getUser();

        if (!$user instanceof User || $user->getRole() !== User::ROLE_PRODUCER) {
            return new JsonResponse(['error' => 'Acceso denegado. Solo el productor puede aceptar solicitudes.'], Response::HTTP_UNAUTHORIZED);
        }

        $request = $repository->find($id);
        if (!$request) {
            return new JsonResponse(['error' => 'Solicitud no encontrada'], Response::HTTP_NOT_FOUND);
        }

        if ($request->getProducer()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'No tienes permiso para aceptar esta solicitud'], Response::HTTP_FORBIDDEN);
        }

        if ($request->getStatus() !== ServiceRequest::STATUS_PENDING) {
            return new JsonResponse(['error' => 'Esta solicitud ya fue procesada'], Response::HTTP_BAD_REQUEST);
        }

        $request->setStatus(ServiceRequest::STATUS_ACCEPTED);
        $request->setUpdatedAt(new \DateTime());

        $project = new Project();
        $project->setTitle($request->getService()->getName());
        $project->setArtist($request->getArtist());
        $project->setProducer($request->getProducer());
        $project->setStatus('active');
        $project->setIsPaid(false);
        $project->setProgressPercentage(0);
        $project->setCurrentStageName('Proyecto creado - Esperando pago');

        $em->persist($project);

        $acceptMessage = new ChatMessage();
        $acceptMessage->setRequest($request);
        $acceptMessage->setSender($user);
        $acceptMessage->setMessage("✅ He aceptado tu solicitud. Se ha creado un proyecto. Procedamos con el pago del 50% para iniciar el trabajo.");
        $em->persist($acceptMessage);

        $em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Solicitud aceptada. Proyecto creado correctamente.',
            'projectId' => $project->getId()
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/reject', name: 'reject', methods: ['PATCH', 'OPTIONS'])]
    public function rejectRequest(
        int $id,
        ServiceRequestRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return new JsonResponse(null, Response::HTTP_OK);
        }

        $user = $this->getUser();

        if (!$user instanceof User || $user->getRole() !== User::ROLE_PRODUCER) {
            return new JsonResponse(['error' => 'Acceso denegado'], Response::HTTP_UNAUTHORIZED);
        }

        $request = $repository->find($id);
        if (!$request) {
            return new JsonResponse(['error' => 'Solicitud no encontrada'], Response::HTTP_NOT_FOUND);
        }

        if ($request->getProducer()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'No tienes permiso'], Response::HTTP_FORBIDDEN);
        }

        if ($request->getStatus() !== ServiceRequest::STATUS_PENDING) {
            return new JsonResponse(['error' => 'Esta solicitud ya fue procesada'], Response::HTTP_BAD_REQUEST);
        }

        $request->setStatus(ServiceRequest::STATUS_REJECTED);
        $request->setUpdatedAt(new \DateTime());

        $rejectMessage = new ChatMessage();
        $rejectMessage->setRequest($request);
        $rejectMessage->setSender($user);
        $rejectMessage->setMessage("❌ Lo siento, he rechazado tu solicitud. Puedes contactarme para más detalles.");
        $em->persist($rejectMessage);

        $em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Solicitud rechazada'
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/pay-deposit', name: 'pay_deposit', methods: ['POST', 'OPTIONS'])]
    public function payDeposit(
        int $id,
        ServiceRequestRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return new JsonResponse(null, Response::HTTP_OK);
        }

        $user = $this->getUser();

        if (!$user instanceof User || $user->getRole() !== User::ROLE_ARTIST) {
            return new JsonResponse(['error' => 'Acceso denegado. Solo el artista puede realizar pagos.'], Response::HTTP_UNAUTHORIZED);
        }

        $request = $repository->find($id);
        if (!$request) {
            return new JsonResponse(['error' => 'Solicitud no encontrada'], Response::HTTP_NOT_FOUND);
        }

        if ($request->getArtist()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'No tienes permiso para pagar esta solicitud'], Response::HTTP_FORBIDDEN);
        }

        if ($request->getStatus() !== ServiceRequest::STATUS_ACCEPTED) {
            return new JsonResponse(['error' => 'La solicitud no está aceptada. El productor debe aceptarla primero.'], Response::HTTP_BAD_REQUEST);
        }

        if ($request->getIsPaid() !== ServiceRequest::PAYMENT_NOT_PAID) {
            return new JsonResponse(['error' => 'Ya se ha realizado un pago para esta solicitud'], Response::HTTP_BAD_REQUEST);
        }

        $request->setIsPaid(ServiceRequest::PAYMENT_DEPOSIT_PAID);
        $request->setUpdatedAt(new \DateTime());

        $project = $em->getRepository(Project::class)->findOneBy([
            'artist' => $request->getArtist(),
            'producer' => $request->getProducer(),
            'status' => 'active'
        ]);

        if ($project) {
            $project->setCurrentStageName('Pago recibido - En producción');
        }

        $paymentMessage = new ChatMessage();
        $paymentMessage->setRequest($request);
        $paymentMessage->setSender($user);
        $paymentMessage->setMessage("✅ Pago del 50% realizado correctamente. El productor comenzará a trabajar en breve.");
        $em->persist($paymentMessage);

        $em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Pago de adelanto realizado correctamente',
            'paymentStatus' => $request->getIsPaid()
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/pay-remaining', name: 'pay_remaining', methods: ['POST', 'OPTIONS'])]
    public function payRemaining(
        int $id,
        ServiceRequestRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return new JsonResponse(null, Response::HTTP_OK);
        }

        $user = $this->getUser();

        if (!$user instanceof User || $user->getRole() !== User::ROLE_ARTIST) {
            return new JsonResponse(['error' => 'Acceso denegado. Solo el artista puede realizar pagos.'], Response::HTTP_UNAUTHORIZED);
        }

        $request = $repository->find($id);
        if (!$request) {
            return new JsonResponse(['error' => 'Solicitud no encontrada'], Response::HTTP_NOT_FOUND);
        }

        if ($request->getArtist()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'No tienes permiso para pagar esta solicitud'], Response::HTTP_FORBIDDEN);
        }

        if ($request->getIsPaid() !== ServiceRequest::PAYMENT_DEPOSIT_PAID) {
            return new JsonResponse(['error' => 'Primero debe pagarse el adelanto del 50%'], Response::HTTP_BAD_REQUEST);
        }

        $request->setIsPaid(ServiceRequest::PAYMENT_FULL_PAID);
        $request->setUpdatedAt(new \DateTime());

        $project = $em->getRepository(Project::class)->findOneBy([
            'artist' => $request->getArtist(),
            'producer' => $request->getProducer(),
            'status' => 'active'
        ]);

        if ($project) {
            $project->setIsPaid(true);
            $project->setCurrentStageName('Proyecto pagado - Esperando archivo final');
        }

        $paymentMessage = new ChatMessage();
        $paymentMessage->setRequest($request);
        $paymentMessage->setSender($user);
        $paymentMessage->setMessage("✅ Pago completado. El proyecto está oficialmente pagado en su totalidad.");
        $em->persist($paymentMessage);

        $em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Pago completado correctamente',
            'paymentStatus' => $request->getIsPaid()
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/payment-status', name: 'payment_status', methods: ['GET'])]
    public function getPaymentStatus(
        int $id,
        ServiceRequestRepository $repository
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Acceso denegado'], Response::HTTP_UNAUTHORIZED);
        }

        $request = $repository->find($id);
        if (!$request) {
            return new JsonResponse(['error' => 'Solicitud no encontrada'], Response::HTTP_NOT_FOUND);
        }

        if ($request->getArtist()->getId() !== $user->getId() && $request->getProducer()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'No tienes permiso'], Response::HTTP_FORBIDDEN);
        }

        $statusMap = [
            ServiceRequest::PAYMENT_NOT_PAID => 'not_paid',
            ServiceRequest::PAYMENT_DEPOSIT_PAID => 'deposit_paid',
            ServiceRequest::PAYMENT_FULL_PAID => 'fully_paid'
        ];

        $amount = (float) $request->getAmount();
        $depositAmount = number_format($amount / 2, 2, '.', '');
        $remainingAmount = number_format($amount / 2, 2, '.', '');

        return new JsonResponse([
            'paymentStatus' => $request->getIsPaid(),
            'statusLabel' => $statusMap[$request->getIsPaid()],
            'depositAmount' => $depositAmount,
            'remainingAmount' => $remainingAmount,
            'totalAmount' => $request->getAmount()
        ], Response::HTTP_OK);
    }

    #[Route('/payment-status-by-project', name: 'payment_status_by_project', methods: ['GET'])]
    public function getPaymentStatusByProject(Request $request, ServiceRequestRepository $repository): JsonResponse
    {
        $artistId = $request->query->get('artistId');
        $producerId = $request->query->get('producerId');

        if (!$artistId || !$producerId) {
            return new JsonResponse(['error' => 'Faltan parámetros'], 400);
        }

        $serviceRequest = $repository->findOneBy([
            'artist' => $artistId,
            'producer' => $producerId,
            'status' => ServiceRequest::STATUS_ACCEPTED
        ], ['createdAt' => 'DESC']);

        if (!$serviceRequest) {
            return new JsonResponse([
                'paymentStatus' => 0,
                'statusLabel' => 'not_paid'
            ], 200);
        }

        $statusMap = [
            ServiceRequest::PAYMENT_NOT_PAID => 'not_paid',
            ServiceRequest::PAYMENT_DEPOSIT_PAID => 'deposit_paid',
            ServiceRequest::PAYMENT_FULL_PAID => 'fully_paid'
        ];

        return new JsonResponse([
            'paymentStatus' => $serviceRequest->getIsPaid(),
            'statusLabel' => $statusMap[$serviceRequest->getIsPaid()]
        ], 200);
    }
}
