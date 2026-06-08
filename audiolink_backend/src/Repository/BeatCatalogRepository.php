<?php

namespace App\Repository;

use App\Entity\BeatCatalog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BeatCatalog>
 */
class BeatCatalogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BeatCatalog::class);
    }

    public function findAvailableBeats(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.isSold = :sold')
            ->setParameter('sold', false)
            ->orderBy('b.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findFeaturedBeatByUser(User $user): ?BeatCatalog
    {
        return $this->findOneBy([
            'producer' => $user,
            'isSold' => false
        ]);
    }
}
