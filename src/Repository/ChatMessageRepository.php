<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ChatMessage;
use App\Entity\ChatSession;
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

    /**
     * @return list<ChatMessage>
     */
    public function findBySession(ChatSession $session): array
    {
        /** @var list<ChatMessage> $result */
        $result = $this->createQueryBuilder('m')
            ->andWhere('m.session = :session')
            ->setParameter('session', $session)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
