<?php

namespace App\Repository;

use App\Entity\ChatMessage;
use App\Entity\ServiceRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChatMessage>
 */
class ChatMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatMessage::class);
    }

    public function findByRequest(ServiceRequest $request): array
    {
        return $this->createQueryBuilder('cm')
            ->andWhere('cm.request = :request')
            ->setParameter('request', $request)
            ->orderBy('cm.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getLastMessagesForUser(User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT DISTINCT ON (sr.id) 
                sr.id as request_id,
                sr.status,
                sr.amount,
                ps.name as service_name,
                cm.message as last_message,
                cm.created_at as last_message_time,
                CASE 
                    WHEN sr.artist_id = :userId THEN u2.full_name 
                    ELSE u1.full_name 
                END as other_party_name,
                CASE 
                    WHEN sr.artist_id = :userId THEN 'producer' 
                    ELSE 'artist' 
                END as other_party_role,
                CASE 
                    WHEN sr.artist_id = :userId THEN u2.avatar_url 
                    ELSE u1.avatar_url 
                END as other_party_avatar,
                (SELECT COUNT(*) FROM audiolink.chat_messages WHERE request_id = sr.id AND is_read = false AND sender_id != :userId) as unread_count
            FROM audiolink.service_requests sr
            JOIN audiolink.chat_messages cm ON cm.request_id = sr.id
            JOIN audiolink.users u1 ON u1.id = sr.artist_id
            JOIN audiolink.users u2 ON u2.id = sr.producer_id
            JOIN audiolink.producer_services ps ON ps.id = sr.service_id
            WHERE sr.artist_id = :userId OR sr.producer_id = :userId
            ORDER BY sr.id, cm.created_at DESC
        ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['userId' => $user->getId()]);

        return $result->fetchAllAssociative();
    }

    public function markAsRead(ServiceRequest $request, User $user): int
    {
        return $this->createQueryBuilder('cm')
            ->update()
            ->set('cm.isRead', ':isRead')
            ->where('cm.request = :request')
            ->andWhere('cm.sender != :user')
            ->setParameter('isRead', true)
            ->setParameter('request', $request)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function getUnreadCount(User $user): int
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT COUNT(cm.id) 
            FROM audiolink.chat_messages cm
            JOIN audiolink.service_requests sr ON sr.id = cm.request_id
            WHERE (sr.artist_id = :userId OR sr.producer_id = :userId)
            AND cm.sender_id != :userId
            AND cm.is_read = false
        ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['userId' => $user->getId()]);

        return (int) $result->fetchOne();
    }

    public function getLatestMessages(ServiceRequest $request, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('cm')
            ->andWhere('cm.request = :request')
            ->setParameter('request', $request)
            ->orderBy('cm.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
}
