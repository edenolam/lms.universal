<?php

namespace App\Repository\FormationManagement;

use App\Entity\FormationManagement\PageReference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method PageReference|null find($id, $lockMode = null, $lockVersion = null)
 * @method PageReference|null findOneBy(array $criteria, array $orderBy = null)
 * @method PageReference[] findAll()
 * @method PageReference[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageReferenceRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, PageReference::class);
    }

    public function findByPageId($id)
    {
        return $this->createQueryBuilder('pr')
            ->innerJoin('pr.page', 'p')
            ->where('p.id = :id')
            ->orderBy('pr.sort', 'ASC')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByReferenceId($id)
    {
        return $this->createQueryBuilder('pr')
            ->innerJoin('pr.reference', 'r')
            ->where('r.id = :id')
            ->orderBy('pr.sort', 'ASC')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByPageANDReferenceId($pageId, $referenceId)
    {
        return $this->createQueryBuilder('pr')
            ->innerJoin('pr.reference', 'r')
            ->innerJoin('pr.page', 'p')
            ->where('r.id = :reference_id')
            ->andWhere('p.id = :page_id')
            ->setParameter('reference_id', $referenceId)
            ->setParameter('page_id', $pageId)
            ->orderBy('pr.sort', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findSortByPageId($id)
    {
        return $this->createQueryBuilder('pr')
            ->select('pr.sort')
            ->innerJoin('pr.page', 'p')
            ->where('p.id = :id')
            ->orderBy('pr.sort', 'DESC')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }

//    /**
//     * @return PageReference[] Returns an array of PageReference objects
//     */
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
    public function findOneBySomeField($value): ?PageReference
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
