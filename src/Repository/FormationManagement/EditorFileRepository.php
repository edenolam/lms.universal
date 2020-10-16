<?php

namespace App\Repository\FormationManagement;

use App\Entity\FormationManagement\EditorFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method EditorFile|null find($id, $lockMode = null, $lockVersion = null)
 * @method EditorFile|null findOneBy(array $criteria, array $orderBy = null)
 * @method EditorFile[] findAll()
 * @method EditorFile[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EditorFileRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, EditorFile::class);
    }

//    /**
//     * @return EditorFile[] Returns an array of EditorFile objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?EditorFile
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
