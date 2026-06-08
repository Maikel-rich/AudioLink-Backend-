<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\BeatCatalog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/producers')]
class ProducerController extends AbstractController
{
    #[Route('', name: 'api_producers_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $qb = $entityManager->createQueryBuilder();

        $qb->select('u.id', 'u.fullName', 'u.profilePicture', 'u.genres', 'u.yearsExperience')
            ->addSelect('(SELECT MIN(s.price) FROM App\Entity\ProducerService s WHERE s.producer = u.id) as minPrice')
            ->from(User::class, 'u')
            ->where('u.role = :role')
            ->setParameter('role', User::ROLE_PRODUCER);

        $results = $qb->getQuery()->getResult();

        $producersData = array_map(function ($row) {
            $genres = $row['genres'];
            if (is_string($genres)) {
                $genres = json_decode($genres, true) ?: [];
            }
            if (!is_array($genres)) {
                $genres = [];
            }

            return [
                'id' => $row['id'],
                'fullName' => $row['fullName'],
                'avatarUrl' => $row['profilePicture'],
                'skills' => $genres,
                'yearsExperience' => $row['yearsExperience'] ?? 0,
                'minPrice' => $row['minPrice'] !== null ? (float)$row['minPrice'] : 0,
                'price' => $row['minPrice'] !== null ? (float)$row['minPrice'] : 0
            ];
        }, $results);

        return new JsonResponse($producersData, 200);
    }

    #[Route('/me', name: 'api_producers_get_me', methods: ['GET'])]
    public function getMyProfile(EntityManagerInterface $em): JsonResponse
    {
        $producer = $this->getUser();

        if (!$producer instanceof User || $producer->getRole() !== User::ROLE_PRODUCER) {
            return new JsonResponse(['error' => 'Acceso denegado'], 403);
        }

        $freshProducer = $em->getRepository(User::class)->find($producer->getId());

        $genres = $freshProducer->getGenres();

        if (is_string($genres)) {
            $genres = json_decode($genres, true) ?: [];
        }

        if (!is_array($genres)) {
            $genres = [];
        }

        return new JsonResponse([
            'id' => $freshProducer->getId(),
            'email' => $freshProducer->getEmail(),
            'fullName' => $freshProducer->getFullName(),
            'profilePicture' => $freshProducer->getProfilePicture(),
            'bio' => $freshProducer->getBio(),
            'yearsExperience' => $freshProducer->getYearsExperience(),
            'totalStreams' => $freshProducer->getTotalStreams(),
            'genres' => $genres,
            'skills' => $genres
        ], 200);
    }

    #[Route('/me/update', name: 'api_producers_update_me', methods: ['PUT'])]
    public function updateMyProfile(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $producer = $this->getUser();

        if (!$producer instanceof User || $producer->getRole() !== User::ROLE_PRODUCER) {
            return new JsonResponse(['error' => 'Acceso denegado'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['fullName'])) {
            $producer->setFullName($data['fullName']);
        }

        if (isset($data['bio'])) {
            $producer->setBio($data['bio']);
        }

        if (isset($data['avatarUrl'])) {
            $producer->setProfilePicture($data['avatarUrl']);
        }

        if (isset($data['genres']) && is_array($data['genres'])) {
            $producer->setGenres($data['genres']);
        }

        $entityManager->flush();

        return new JsonResponse([
            'status' => 'Perfil actualizado correctamente',
            'genres_saved' => $producer->getGenres()
        ], 200);
    }

    #[Route('/{id}', name: 'api_producers_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $producer = $entityManager->getRepository(User::class)->findOneBy([
            'id' => $id,
            'role' => User::ROLE_PRODUCER
        ]);

        if (!$producer) {
            return new JsonResponse(['error' => 'Productor no encontrado'], 404);
        }

        $genres = $producer->getGenres();
        if (is_string($genres)) {
            $genres = json_decode($genres, true) ?: [];
        }
        if (!is_array($genres)) {
            $genres = [];
        }

        return new JsonResponse([
            'id' => $producer->getId(),
            'fullName' => $producer->getFullName(),
            'profilePicture' => $producer->getProfilePicture(),
            'bio' => $producer->getBio(),
            'skills' => $genres,
            'yearsExperience' => $producer->getYearsExperience(),
            'totalStreams' => $producer->getTotalStreams()
        ], 200);
    }

    #[Route('/{id}/beats', name: 'api_producers_add_beat', methods: ['POST'])]
    public function addBeat(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $producer = $entityManager->getRepository(User::class)->findOneBy([
            'id' => $id,
            'role' => User::ROLE_PRODUCER
        ]);

        if (!$producer) {
            return new JsonResponse(['error' => 'Productor no encontrado'], 404);
        }

        $data = json_decode($request->getContent(), true);

        $beat = new BeatCatalog();
        $beat->setProducer($producer);
        $beat->setTitle($data['title'] ?? 'Untitled Beat');
        $beat->setGenre($data['genre'] ?? null);
        $beat->setPrice($data['price'] ?? '0.00');
        $beat->setTaggedAudioUrl($data['taggedAudioUrl'] ?? null);
        $beat->setUntaggedAudioUrl($data['untaggedAudioUrl'] ?? null);
        $beat->setCloudinaryUrl($data['coverUrl'] ?? '');

        $entityManager->persist($beat);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Beat añadido con éxito al catálogo', 'id' => $beat->getId()], 201);
    }

    #[Route('/{id}/presentation-audio', name: 'api_producers_set_presentation_audio', methods: ['PUT'])]
    public function setPresentationAudio(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $producer = $entityManager->getRepository(User::class)->findOneBy([
            'id' => $id,
            'role' => User::ROLE_PRODUCER
        ]);

        if (!$producer) {
            return new JsonResponse(['error' => 'Productor no encontrado'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['presentationAudioUrl'])) {
            return new JsonResponse(['error' => 'La URL del audio de presentación es requerida'], 400);
        }

        $producer->setPresentationAudioUrl($data['presentationAudioUrl']);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Audio de presentación actualizado con éxito'], 200);
    }
}
