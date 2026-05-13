<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Doctor;
use App\Entity\Slot;
use App\Enum\SlotStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Slot>
 */
class SlotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Slot::class);
    }

    /**
     * @return list<Slot>
     */
    public function findOpenForDoctor(Doctor $doctor, \DateTimeImmutable $weekStart, \DateTimeImmutable $weekEnd): array
    {
        /** @var list<Slot> $result */
        $result = $this->createQueryBuilder('s')
            ->andWhere('s.doctor = :doctor')
            ->andWhere('s.status = :status')
            ->andWhere('s.startAt >= :weekStart')
            ->andWhere('s.startAt < :weekEnd')
            ->setParameter('doctor', $doctor)
            ->setParameter('status', SlotStatus::OPEN)
            ->setParameter('weekStart', $weekStart)
            ->setParameter('weekEnd', $weekEnd)
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * @return list<Slot>
     */
    public function findForDoctorBetween(Doctor $doctor, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        /** @var list<Slot> $result */
        $result = $this->createQueryBuilder('s')
            ->andWhere('s.doctor = :doctor')
            ->andWhere('s.startAt >= :from')
            ->andWhere('s.startAt < :to')
            ->setParameter('doctor', $doctor)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
