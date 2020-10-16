<?php

namespace App\Repository\FormationManagement;

use App\Entity\FormationManagement\ModuleFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method ModuleFile|null find($id, $lockMode = null, $lockVersion = null)
 * @method ModuleFile|null findOneBy(array $criteria, array $orderBy = null)
 * @method ModuleFile[] findAll()
 * @method ModuleFile[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModuleFileRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, ModuleFile::class);
    }

//    /**
//     * @return ModuleFile[] Returns an array of ModuleFile objects
//     */

    public function findByModuleId($id)
    {
        return $this->createQueryBuilder('m')
            ->innerJoin('m.module', 'md')
            ->where('md.id = :id')
            //->andWhere('m.isValid = true')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?ModuleFile
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
