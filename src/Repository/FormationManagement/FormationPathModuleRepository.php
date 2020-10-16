<?php

namespace App\Repository\FormationManagement;

use App\Entity\FormationManagement\FormationPathModule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method FormationPathModule|null find($id, $lockMode = null, $lockVersion = null)
 * @method FormationPathModule|null findOneBy(array $criteria, array $orderBy = null)
 * @method FormationPathModule[] findAll()
 * @method FormationPathModule[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormationPathModuleRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationPathModule::class);
    }

//    /**
//     * @return FormationPathModule[] Returns an array of FormationPathModule objects
//     */

    public function findByModuleId($id)
    {
        return $this->createQueryBuilder('f')
            ->innerJoin('f.module', 'm')
            ->where('m.id = :id')
            ->orderBy('f.sort', 'ASC')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findSortByFormationPathId($id)
    {
        return $this->createQueryBuilder('f')
            ->select('f.sort')
            ->innerJoin('f.formationPath', 'fp')
            ->where('fp.id = :id')
            ->orderBy('f.sort', 'DESC')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByFormationPathId($id)
    {
        return $this->createQueryBuilder('f')
            ->innerJoin('f.formationPath', 'fp')
            ->where('fp.id = :id')
            ->orderBy('f.sort', 'ASC')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByModuleANDFormationPathId($moduleId, $formationPathId)
    {
        return $this->createQueryBuilder('f')
            ->innerJoin('f.module', 'm')
            ->innerJoin('f.formationPath', 'fp')
            ->where('m.id = :module_id')
            ->andWhere('fp.id = :formation_path_id')
            ->orderBy('f.sort', 'ASC')
            ->setParameter('module_id', $moduleId)
            ->setParameter('formation_path_id', $formationPathId)
            ->getQuery()
            ->getResult()
        ;
    }

    // useless codes module->getNbFormationLinked or moduel.nbFormationLinked
    // public function countFormationLinked($moduleSlug)
    // {
    //     return $this->_em->createQueryBuilder()
    //             ->select('COUNT(fm)')
    //             ->from('App:FormationManagement\FormationPathModule', 'fm')
    //             ->leftjoin('fm.module', 'm')
    //             ->where('m.slug = :moduleSlug')
    //             ->setParameter('moduleSlug', $moduleSlug)
    //             ->getQuery()
    //             ->getSingleScalarResult();
    // }

    /*
    public function findOneBySomeField($value): ?FormationPathModule
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
