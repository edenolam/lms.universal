<?php

namespace App\Repository\FormationManagement;

use App\Entity\FormationManagement\VersioningModule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method VersioningModule|null find($id, $lockMode = null, $lockVersion = null)
 * @method VersioningModule|null findOneBy(array $criteria, array $orderBy = null)
 * @method VersioningModule[] findAll()
 * @method VersioningModule[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VersioningModuleRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, VersioningModule::class);
    }

    // /**
    //  * @return VersioningModule[] Returns an array of VersioningModule objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('v.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?VersioningModule
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
