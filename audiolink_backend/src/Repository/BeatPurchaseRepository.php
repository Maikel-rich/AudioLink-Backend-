<?php

namespace App\Repository;

use App\Entity\BeatPurchase;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BeatPurchase>
 */
class BeatPurchaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BeatPurchase::class);
    }

    public function findPurchasedBeatsByArtist(User $artist): array
    {
        return $this->createQueryBuilder('bp')
            ->andWhere('bp.artist = :artist')
            ->andWhere('bp.status = :status')
            ->setParameter('artist', $artist)
            ->setParameter('status', 'completed')
            ->orderBy('bp.purchaseDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPurchasedBeatsByProducer(User $producer): array
    {
        return $this->createQueryBuilder('bp')
            ->andWhere('bp.producer = :producer')
            ->setParameter('producer', $producer)
            ->orderBy('bp.purchaseDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function hasPurchasedBeat(User $artist, int $beatId): bool
    {
        $result = $this->createQueryBuilder('bp')
            ->select('COUNT(bp.id)')
            ->andWhere('bp.artist = :artist')
            ->andWhere('bp.beat = :beatId')
            ->andWhere('bp.status = :status')
            ->setParameter('artist', $artist)
            ->setParameter('beatId', $beatId)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    public function hasExclusivePurchase(int $beatId): bool
    {
        $result = $this->createQueryBuilder('bp')
            ->select('COUNT(bp.id)')
            ->andWhere('bp.beat = :beatId')
            ->andWhere('bp.licenseType = :licenseType')
            ->andWhere('bp.status = :status')
            ->setParameter('beatId', $beatId)
            ->setParameter('licenseType', 'exclusive')
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }
}
