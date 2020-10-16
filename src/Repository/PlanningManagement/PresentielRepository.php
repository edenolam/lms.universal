<?php

namespace App\Repository\PlanningManagement;

use App\Entity\PlanningManagement\Presentiel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Presentiel|null find($id, $lockMode = null, $lockVersion = null)
 * @method Presentiel|null findOneBy(array $criteria, array $orderBy = null)
 * @method Presentiel[]    findAll()
 * @method Presentiel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PresentielRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Presentiel::class);
    }

    // /**
    //  * @return Presentiel[] Returns an array of Presentiel objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Presentiel
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
