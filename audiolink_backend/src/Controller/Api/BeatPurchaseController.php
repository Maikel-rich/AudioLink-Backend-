<?php

namespace App\Controller\Api;

use App\Entity\BeatPurchase;
use App\Entity\BeatCatalog;
use App\Entity\User;
use App\Repository\BeatPurchaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/beat-purchases', name: 'api_beat_purchases_')]
class BeatPurchaseController extends AbstractController
{
    #[Route('', name: 'create', methods: ['POST', 'OPTIONS'])]
    public function purchaseBeat(
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

        if (!$data || empty($data['beatId'])) {
            return new JsonResponse(['error' => 'Beat ID es requerido'], Response::HTTP_BAD_REQUEST);
        }

        $beat = $em->getRepository(BeatCatalog::class)->find($data['beatId']);
        if (!$beat) {
            return new JsonResponse(['error' => 'Beat no encontrado'], Response::HTTP_NOT_FOUND);
        }

        $licenseType = $data['licenseType'] ?? 'standard';

        if ($licenseType === 'exclusive') {
            $existingExclusive = $em->getRepository(BeatPurchase::class)->findOneBy([
                'beat' => $beat,
                'licenseType' => 'exclusive',
                'status' => 'completed'
            ]);

            if ($existingExclusive) {
                return new JsonResponse(['error' => 'Este beat ya fue vendido como licencia exclusiva'], Response::HTTP_CONFLICT);
            }
        }

        $existingPurchase = $em->getRepository(BeatPurchase::class)->findOneBy([
            'artist' => $artist,
            'beat' => $beat,
            'licenseType' => $licenseType,
            'status' => 'completed'
        ]);

        if ($existingPurchase) {
            return new JsonResponse(['error' => 'Ya has comprado este beat con esta licencia anteriormente'], Response::HTTP_CONFLICT);
        }

        $purchase = new BeatPurchase();
        $purchase->setBeat($beat);
        $purchase->setArtist($artist);
        $purchase->setProducer($beat->getProducer());
        $purchase->setPricePaid($beat->getPrice());
        $purchase->setLicenseType($licenseType);
        $purchase->setStatus('completed');

        if ($beat->getUntaggedAudioUrl()) {
            $purchase->setDownloadUrl($beat->getUntaggedAudioUrl());
        }

        $em->persist($purchase);

        if ($licenseType === 'exclusive') {
            $beat->setIsSold(true);
        }

        $em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Beat comprado correctamente',
            'purchaseId' => $purchase->getId(),
            'isExclusive' => $licenseType === 'exclusive'
        ], Response::HTTP_CREATED);
    }

    #[Route('/my-library', name: 'my_library', methods: ['GET'])]
    public function getMyLibrary(
        BeatPurchaseRepository $repository
    ): JsonResponse {
        $artist = $this->getUser();

        if (!$artist instanceof User) {
            return new JsonResponse(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        if ($artist->getRole() !== User::ROLE_ARTIST) {
            return new JsonResponse(['error' => 'Acceso denegado'], Response::HTTP_FORBIDDEN);
        }

        $purchases = $repository->findPurchasedBeatsByArtist($artist);

        $responseData = [];
        foreach ($purchases as $purchase) {
            $beat = $purchase->getBeat();
            $responseData[] = [
                'id' => $purchase->getId(),
                'beatId' => $beat->getId(),
                'title' => $beat->getTitle(),
                'genre' => $beat->getGenre(),
                'bpm' => $beat->getBpm(),
                'keySignature' => $beat->getKeySignature(),
                'pricePaid' => $purchase->getPricePaid(),
                'licenseType' => $purchase->getLicenseType(),
                'purchaseDate' => $purchase->getPurchaseDate()->format('Y-m-d H:i:s'),
                'downloadUrl' => $purchase->getDownloadUrl(),
                'taggedAudioUrl' => $beat->getTaggedAudioUrl(),
                'coverUrl' => $beat->getCloudinaryUrl(),
                'producer' => [
                    'id' => $purchase->getProducer()->getId(),
                    'name' => $purchase->getProducer()->getFullName()
                ]
            ];
        }

        return new JsonResponse($responseData, Response::HTTP_OK);
    }

    #[Route('/{id}/download', name: 'download', methods: ['GET'])]
    public function downloadBeat(
        int $id,
        BeatPurchaseRepository $repository
    ): JsonResponse {
        $artist = $this->getUser();

        if (!$artist instanceof User) {
            return new JsonResponse(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        $purchase = $repository->find($id);
        if (!$purchase) {
            return new JsonResponse(['error' => 'Compra no encontrada'], Response::HTTP_NOT_FOUND);
        }

        if ($purchase->getArtist()->getId() !== $artist->getId()) {
            return new JsonResponse(['error' => 'No tienes permiso'], Response::HTTP_FORBIDDEN);
        }

        if (!$purchase->getDownloadUrl()) {
            return new JsonResponse(['error' => 'No hay archivo disponible para descargar'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'downloadUrl' => $purchase->getDownloadUrl(),
            'fileName' => $purchase->getBeat()->getTitle() . '_master.wav'
        ], Response::HTTP_OK);
    }
}
