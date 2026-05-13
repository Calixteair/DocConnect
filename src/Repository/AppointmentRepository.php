<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Appointment;
use App\Entity\Doctor;
use App\Entity\Patient;
use App\Enum\AppointmentStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointment>
 */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    /**
     * @return list<Appointment>
     */
    public function findUpcomingForPatient(Patient $patient): array
    {
        /** @var list<Appointment> $result */
        $result = $this->createQueryBuilder('a')
            ->innerJoin('a.slot', 's')
            ->andWhere('a.patient = :patient')
            ->andWhere('a.status NOT IN (:excluded)')
            ->andWhere('s.startAt >= :now')
            ->setParameter('patient', $patient)
            ->setParameter('excluded', [AppointmentStatus::CANCELLED, AppointmentStatus::DONE])
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * @return list<Appointment>
     */
    public function findPastForPatient(Patient $patient): array
    {
        /** @var list<Appointment> $result */
        $result = $this->createQueryBuilder('a')
            ->innerJoin('a.slot', 's')
            ->andWhere('a.patient = :patient')
            ->andWhere('a.status IN (:finished) OR s.startAt < :now')
            ->setParameter('patient', $patient)
            ->setParameter('finished', [AppointmentStatus::CANCELLED, AppointmentStatus::DONE])
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('s.startAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * @return list<Appointment>
     */
    public function findPendingForDoctor(Doctor $doctor): array
    {
        /** @var list<Appointment> $result */
        $result = $this->createQueryBuilder('a')
            ->innerJoin('a.slot', 's')
            ->andWhere('s.doctor = :doctor')
            ->andWhere('a.status = :status')
            ->setParameter('doctor', $doctor)
            ->setParameter('status', AppointmentStatus::PENDING)
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
