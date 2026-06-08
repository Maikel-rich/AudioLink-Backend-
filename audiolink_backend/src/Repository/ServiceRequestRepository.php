<?php

namespace App\Repository;

use App\Entity\ServiceRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServiceRequest>
 */
class ServiceRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceRequest::class);
    }

    public function findByPaymentStatus(int $paymentStatus): array
    {
        return $this->createQueryBuilder('sr')
            ->andWhere('sr.isPaid = :paymentStatus')
            ->setParameter('paymentStatus', $paymentStatus)
            ->orderBy('sr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingDepositForArtist(User $artist): array
    {
        return $this->createQueryBuilder('sr')
            ->andWhere('sr.artist = :artist')
            ->andWhere('sr.status = :status')
            ->andWhere('sr.isPaid = :paymentStatus')
            ->setParameter('artist', $artist)
            ->setParameter('status', ServiceRequest::STATUS_ACCEPTED)
            ->setParameter('paymentStatus', ServiceRequest::PAYMENT_NOT_PAID)
            ->orderBy('sr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findFullyPaidForUser(User $user): array
    {
        return $this->createQueryBuilder('sr')
            ->andWhere('(sr.artist = :user OR sr.producer = :user)')
            ->andWhere('sr.isPaid = :paymentStatus')
            ->setParameter('user', $user)
            ->setParameter('paymentStatus', ServiceRequest::PAYMENT_FULL_PAID)
            ->orderBy('sr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
