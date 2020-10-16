<?php

namespace App\Repository\ResultManagement;

use App\Entity\ResultManagement\Certificat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Certificat|null find($id, $lockMode = null, $lockVersion = null)
 * @method Certificat|null findOneBy(array $criteria, array $orderBy = null)
 * @method Certificat[] findAll()
 * @method Certificat[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CertificatRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, Certificat::class);
    }

    // /**
    //  * @return Certificat[] Returns an array of Certificat objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Certificat
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}