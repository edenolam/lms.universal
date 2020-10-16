<?php

namespace App\Repository\ServiceManagement;

use App\Entity\ServiceManagement\Audit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Audit|null find($id, $lockMode = null, $lockVersion = null)
 * @method Audit|null findOneBy(array $criteria, array $orderBy = null)
 * @method Audit[] findAll()
 * @method Audit[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuditRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, Audit::class);
    }

    /**
     *  get the audit trail by filter and by page
     *
     * @param number $page
     * @param number $maxperpage
     * @param string $filter
     *
     * @return array
     */
    public function getFilterByEntity($entity)
    {
        $query = $this->createQueryBuilder('a')
            ->where('a.entityName = :entityName')
            ->setParameter('entityName', $entity)
            ->orderBy('a.datetime', 'DESC')
            ->getQuery();

        try {
            $entities = $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $entities = null;
        }

        return $entities;
    }

//    /**
//     * @return Audit[] Returns an array of Audit objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Audit
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
