<?php

namespace App\Controller\Api;

use App\Entity\BeatCatalog;
use App\Entity\User;
use App\Repository\BeatCatalogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/beats', name: 'api_beats_')]
class BeatCatalogController extends AbstractController
{
    #[Route('/featured', name: 'get_featured', methods: ['GET'])]
    public function getMyFeaturedBeat(
        BeatCatalogRepository $repository
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Usuario no autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->getRole() !== User::ROLE_PRODUCER) {
            return new JsonResponse(['error' => 'Acceso denegado. Se requiere rol de productor.'], Response::HTTP_FORBIDDEN);
        }

        $beat = $repository->findOneBy([
            'producer' => $user,
            'isFeatured' => true
        ]);

        if (!$beat) {
            return new JsonResponse(null, Response::HTTP_OK);
        }

        $responseData = [
            'id' => $beat->getId(),
            'title' => $beat->getTitle(),
            'genre' => $beat->getGenre(),
            'price' => $beat->getPrice(),
            'cloudinaryUrl' => $beat->getCloudinaryUrl(),
            'taggedAudioUrl' => $beat->getTaggedAudioUrl(),
            'untaggedAudioUrl' => $beat->getUntaggedAudioUrl(),
            'bpm' => $beat->getBpm(),
            'keySignature' => $beat->getKeySignature(),
            'isSold' => $beat->isSold(),
            'isFeatured' => $beat->isFeatured(),
            'createdAt' => $beat->getCreatedAt()?->format('Y-m-d H:i:s')
        ];

        return new JsonResponse($responseData, Response::HTTP_OK);
    }

    #[Route('/featured', name: 'save_featured', methods: ['POST'])]
    public function saveFeaturedBeat(
        Request $request,
        BeatCatalogRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User || $user->getRole() !== User::ROLE_PRODUCER) {
            return new JsonResponse(['error' => 'Acceso denegado'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['title'])) {
            return new JsonResponse(['error' => 'El título es obligatorio'], Response::HTTP_BAD_REQUEST);
        }

        $featuredBeat = $repository->findOneBy([
            'producer' => $user,
            'isFeatured' => true
        ]);

        if (!$featuredBeat) {
            $featuredBeat = new BeatCatalog();
            $featuredBeat->setProducer($user);
            $featuredBeat->setIsSold(false);
            $featuredBeat->setIsFeatured(true);
        }

        $featuredBeat->setTitle(trim($data['title']));
        $featuredBeat->setGenre($data['genre'] ?? 'Trap');

        $price = is_numeric($data['price'] ?? 0) ? number_format((float)$data['price'], 2, '.', '') : '0.00';
        $featuredBeat->setPrice($price);

        $cover = $data['coverUrl'] ?? '';
        $featuredBeat->setCloudinaryUrl(!empty($cover) ? $cover : 'https://images.unsplash.com/photo-1614613535308-eb5fbd3d2c17?q=80&w=500');

        $featuredBeat->setTaggedAudioUrl($data['taggedAudioUrl'] ?? null);
        $featuredBeat->setUntaggedAudioUrl($data['untaggedAudioUrl'] ?? null);
        $featuredBeat->setBpm($data['bpm'] ?? null);
        $featuredBeat->setKeySignature($data['keySignature'] ?? null);

        $em->persist($featuredBeat);
        $em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Beat destacado guardado correctamente',
            'beatId' => $featuredBeat->getId()
        ], Response::HTTP_OK);
    }

    #[Route('/my-beats', name: 'get_my_beats', methods: ['GET'])]
    public function getMyBeats(
        BeatCatalogRepository $repository
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Usuario no autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->getRole() !== User::ROLE_PRODUCER) {
            return new JsonResponse(['error' => 'Acceso denegado. Se requiere rol de productor.'], Response::HTTP_FORBIDDEN);
        }

        $beats = $repository->findBy(
            ['producer' => $user],
            ['id' => 'DESC']
        );

        $responseData = [];
        foreach ($beats as $beat) {
            $responseData[] = [
                'id' => $beat->getId(),
                'title' => $beat->getTitle(),
                'genre' => $beat->getGenre(),
                'price' => $beat->getPrice(),
                'cloudinaryUrl' => $beat->getCloudinaryUrl(),
                'taggedAudioUrl' => $beat->getTaggedAudioUrl(),
                'untaggedAudioUrl' => $beat->getUntaggedAudioUrl(),
                'bpm' => $beat->getBpm(),
                'keySignature' => $beat->getKeySignature(),
                'isSold' => $beat->isSold(),
                'isFeatured' => $beat->isFeatured(),
                'createdAt' => $beat->getCreatedAt()?->format('Y-m-d H:i:s')
            ];
        }

        return new JsonResponse($responseData, Response::HTTP_OK);
    }

    #[Route('/producer/{id}', name: 'get_producer_beats', methods: ['GET'])]
    public function getProducerBeats(
        int $id,
        EntityManagerInterface $em,
        BeatCatalogRepository $repository
    ): JsonResponse {
        $producer = $em->getRepository(User::class)->findOneBy([
            'id' => $id,
            'role' => User::ROLE_PRODUCER
        ]);

        if (!$producer) {
            return new JsonResponse(['error' => 'Productor no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $beats = $repository->findBy(
            [
                'producer' => $producer,
                'isSold' => false
            ],
            ['id' => 'DESC']
        );

        $responseData = [];
        foreach ($beats as $beat) {
            $responseData[] = [
                'id' => $beat->getId(),
                'title' => $beat->getTitle(),
                'genre' => $beat->getGenre(),
                'price' => $beat->getPrice(),
                'cloudinaryUrl' => $beat->getCloudinaryUrl(),
                'taggedAudioUrl' => $beat->getTaggedAudioUrl(),
                'bpm' => $beat->getBpm(),
                'keySignature' => $beat->getKeySignature(),
                'isSold' => $beat->isSold(),
                'isFeatured' => $beat->isFeatured(),
                'producer' => [
                    'id' => $producer->getId(),
                    'fullName' => $producer->getFullName()
                ]
            ];
        }

        return new JsonResponse($responseData, Response::HTTP_OK);
    }

    #[Route('', name: 'create_beat', methods: ['POST'])]
    public function createBeat(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User || $user->getRole() !== User::ROLE_PRODUCER) {
            return new JsonResponse(['error' => 'Acceso denegado'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['title'])) {
            return new JsonResponse(['error' => 'El título es obligatorio'], Response::HTTP_BAD_REQUEST);
        }

        $beat = new BeatCatalog();
        $beat->setProducer($user);
        $beat->setIsSold(false);
        $beat->setTitle(trim($data['title']));
        $beat->setGenre($data['genre'] ?? 'Trap');

        $price = is_numeric($data['price'] ?? 0) ? number_format((float)$data['price'], 2, '.', '') : '0.00';
        $beat->setPrice($price);

        $cover = $data['coverUrl'] ?? '';
        $beat->setCloudinaryUrl(!empty($cover) ? $cover : 'https://images.unsplash.com/photo-1614613535308-eb5fbd3d2c17?q=80&w=500');

        $beat->setTaggedAudioUrl($data['taggedAudioUrl'] ?? null);
        $beat->setUntaggedAudioUrl($data['untaggedAudioUrl'] ?? null);
        $beat->setBpm($data['bpm'] ?? null);
        $beat->setKeySignature($data['keySignature'] ?? null);

        $em->persist($beat);
        $em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Beat creado correctamente',
            'beatId' => $beat->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'delete_beat', methods: ['DELETE'])]
    public function deleteBeat(
        int $id,
        BeatCatalogRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Usuario no autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        $beat = $repository->find($id);

        if (!$beat) {
            return new JsonResponse(['error' => 'Beat no encontrado'], Response::HTTP_NOT_FOUND);
        }

        if ($beat->getProducer()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'No tienes permiso para eliminar este beat'], Response::HTTP_FORBIDDEN);
        }

        $em->remove($beat);
        $em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Beat eliminado correctamente'
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/sold', name: 'mark_as_sold', methods: ['PATCH'])]
    public function markAsSold(
        int $id,
        BeatCatalogRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Usuario no autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        $beat = $repository->find($id);

        if (!$beat) {
            return new JsonResponse(['error' => 'Beat no encontrado'], Response::HTTP_NOT_FOUND);
        }

        if ($beat->getProducer()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'No tienes permiso para modificar este beat'], Response::HTTP_FORBIDDEN);
        }

        $beat->setIsSold(true);
        $em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Beat marcado como vendido correctamente'
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/featured', name: 'set_featured_beat', methods: ['PATCH', 'OPTIONS'])]
    public function setFeaturedBeat(
        int $id,
        BeatCatalogRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return new JsonResponse(null, Response::HTTP_OK);
        }

        $user = $this->getUser();

        if (!$user instanceof User || $user->getRole() !== User::ROLE_PRODUCER) {
            return new JsonResponse(['error' => 'Acceso denegado'], Response::HTTP_UNAUTHORIZED);
        }

        $newFeaturedBeat = $repository->find($id);

        if (!$newFeaturedBeat) {
            return new JsonResponse(['error' => 'Beat no encontrado'], Response::HTTP_NOT_FOUND);
        }

        if ($newFeaturedBeat->getProducer()->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'No tienes permiso para este beat'], Response::HTTP_FORBIDDEN);
        }

        if ($newFeaturedBeat->isFeatured()) {
            return new JsonResponse([
                'status' => 'success',
                'message' => 'Este ya es tu beat destacado'
            ], Response::HTTP_OK);
        }

        $allBeats = $repository->findBy(['producer' => $user]);

        foreach ($allBeats as $beat) {
            $beat->setIsFeatured(false);
        }

        $newFeaturedBeat->setIsFeatured(true);
        $em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Beat destacado actualizado correctamente',
            'beatId' => $newFeaturedBeat->getId(),
            'isFeatured' => $newFeaturedBeat->isFeatured(),
            'isSold' => $newFeaturedBeat->isSold()
        ], Response::HTTP_OK);
    }
}
