<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Doctor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Doctor>
 */
class DoctorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Doctor::class);
    }

    public function findBySlug(string $slug): ?Doctor
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * @return list<Doctor>
     */
    public function findForSearch(string $citySearch, ?int $specialtyId, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.specialties', 's')
            ->leftJoin('d.addresses', 'a')
            ->addSelect('s', 'a');

        if ($specialtyId !== null) {
            $qb->andWhere('s.id = :sid')->setParameter('sid', $specialtyId);
        }

        if ($citySearch !== '') {
            $qb->andWhere('a.city LIKE :c')->setParameter('c', '%' . $citySearch . '%');
        }

        /** @var list<Doctor> $result */
        $result = $qb->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return $result;
    }
}
