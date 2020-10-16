<?php

namespace App\Repository\TestManagement;

use App\Entity\TestManagement\Pool;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Pool|null find($id, $lockMode = null, $lockVersion = null)
 * @method Pool|null findOneBy(array $criteria, array $orderBy = null)
 * @method Pool[] findAll()
 * @method Pool[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PoolRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, Pool::class);
    }

    public function findFirstPoolByTest($idTest)
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.test', 't')
            ->where('t.id = :id')
            ->andWhere('p.isValid = true')
            ->setParameter('id', $idTest)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
