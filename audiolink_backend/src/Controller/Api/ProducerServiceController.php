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

    #[Route('/producer/{producerId}/sync', name: 'api_services_sync', methods: ['PUT'])]
    public function sync(
        int $producerId,
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ProducerServiceRepository $serviceRepository
    ): JsonResponse {
        $producer = $userRepository->find($producerId);
        if (!$producer) {
            return new JsonResponse(['error' => 'Productor no encontrado'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $incomingServices = $data['services'] ?? [];

        $currentServices = $serviceRepository->findBy(['producer' => $producer]);

        $currentServicesMap = [];
        foreach ($currentServices as $service) {
            $currentServicesMap[$service->getId()] = $service;
        }

        $processedIds = [];

        foreach ($incomingServices as $incoming) {
            if (!empty($incoming['id']) && is_numeric($incoming['id']) && isset($currentServicesMap[$incoming['id']])) {
                $serviceEntity = $currentServicesMap[$incoming['id']];
                $processedIds[] = $serviceEntity->getId();
            } else {
                $serviceEntity = new ProducerService();
                $serviceEntity->setProducer($producer);
            }

            $serviceEntity->setName($incoming['name'] ?? 'Nuevo Servicio');
            $serviceEntity->setPrice($incoming['price'] ?? 0.00);
            $serviceEntity->setDescription($incoming['description'] ?? null);
            $serviceEntity->setDeliveryTimeDays($incoming['deliveryTimeDays'] ?? 3);

            $entityManager->persist($serviceEntity);
        }

        foreach ($currentServices as $oldService) {
            if (!in_array($oldService->getId(), $processedIds)) {
                $entityManager->remove($oldService);
            }
        }

        $entityManager->flush();

        return new JsonResponse(['message' => 'Consola de servicios sincronizada correctamente'], 200);
    }

    #[Route('/{id}', name: 'api_services_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        ProducerServiceRepository $repository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $service = $repository->find($id);

        if (!$service) {
            return new JsonResponse(['error' => 'Servicio no encontrado'], 404);
        }

        $entityManager->remove($service);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Servicio eliminado correctamente'], 200);
    }
}
