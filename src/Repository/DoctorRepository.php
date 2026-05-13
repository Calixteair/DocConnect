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
    public function findForSearch(string $citySearch, ?string $specialtySlug, int $limit, int $offset): array
    {
        $qb = $this->baseSearchQb($citySearch, $specialtySlug)
            ->leftJoin('d.specialties', 's2')
            ->leftJoin('d.addresses', 'a2')
            ->addSelect('s2', 'a2', 'u')
            ->leftJoin('d.user', 'u')
            ->orderBy('u.firstName', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        /** @var list<Doctor> $result */
        $result = $qb->getQuery()->getResult();
        return $result;
    }

    public function countForSearch(string $citySearch, ?string $specialtySlug): int
    {
        $qb = $this->baseSearchQb($citySearch, $specialtySlug)
            ->select('COUNT(DISTINCT d.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function baseSearchQb(string $citySearch, ?string $specialtySlug): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('d');

        if ($specialtySlug !== null && $specialtySlug !== '') {
            $qb->innerJoin('d.specialties', 's', 'WITH', 's.slug = :spe')
                ->setParameter('spe', $specialtySlug);
        }

        if ($citySearch !== '') {
            $qb->innerJoin('d.addresses', 'a', 'WITH', 'a.city LIKE :c')
                ->setParameter('c', '%' . $citySearch . '%');
        }

        return $qb;
    }
}
