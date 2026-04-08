<?php

namespace App\Controller\Api;

use App\Entity\ProducerService;
use App\Repository\ProducerServiceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/services')]
class ProducerServiceController extends AbstractController
{
    #[Route('', name: 'api_services_index', methods: ['GET'])]
    public function index(ProducerServiceRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $services = $repository->findAll();
        $json = $serializer->serialize($services, 'json', ['groups' => 'service:read']);
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/producer/{producerId}', name: 'api_producer_services', methods: ['GET'])]
    public function listByProducer(int $producerId, ProducerServiceRepository $repository, SerializerInterface $serializer): JsonResponse
    {
        $services = $repository->findBy(['producer' => $producerId]);
        $json = $serializer->serialize($services, 'json', ['groups' => 'service:read']);
        return new JsonResponse($json, 200, [], true);
    }

    #[Route('', name: 'api_services_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['producerId'], $data['name'], $data['price'])) {
            return new JsonResponse(['error' => 'Faltan campos obligatorios'], 400);
        }

        $producer = $userRepository->find($data['producerId']);
        if (!$producer) {
            return new JsonResponse(['error' => 'Productor no encontrado'], 404);
        }

        $service = new ProducerService();
        $service->setProducer($producer);
        $service->setName($data['name']);
        $service->setDescription($data['description'] ?? null);
        $service->setPrice($data['price']);
        $service->setDeliveryTimeDays($data['deliveryTimeDays'] ?? null);

        $entityManager->persist($service);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Servicio creado', 'id' => $service->getId()], 201);
    }
}
