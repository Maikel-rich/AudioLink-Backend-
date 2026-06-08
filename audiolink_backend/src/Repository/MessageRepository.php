<?php

namespace App\Repository;

use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Obtiene el historial completo de mensajes entre dos usuarios
     * (Tanto los enviados como los recibidos entre ambos)
     * @param int $u1 ID del primer usuario
     * @param int $u2 ID del segundo usuario
     * @return Message[]
     */
    public function findConversation(int $u1, int $u2): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.sender = :u1 AND m.receiver = :u2) OR (m.sender = :u2 AND m.receiver = :u1)')
            ->setParameter('u1', $u1)
            ->setParameter('u2', $u2)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
