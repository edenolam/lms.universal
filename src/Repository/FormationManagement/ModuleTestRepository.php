<?php

namespace App\Repository\FormationManagement;

use App\Entity\FormationManagement\ModuleTest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method ModuleTest|null find($id, $lockMode = null, $lockVersion = null)
 * @method ModuleTest|null findOneBy(array $criteria, array $orderBy = null)
 * @method ModuleTest[] findAll()
 * @method ModuleTest[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModuleTestRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, ModuleTest::class);
    }

    public function findByModuleANDTestId($moduleId, $testId)
    {
        return $this->createQueryBuilder('mt')
            ->innerJoin('mt.module', 'm')
            ->innerJoin('mt.test', 't')
            ->where('t.id = :testId')
            ->andWhere('m.id = :moduleId')
            ->setParameter('testId', $testId)
            ->setParameter('moduleId', $moduleId)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByTestIdANDModuleRef($testId, $moduleRef)
    {
        return $this->createQueryBuilder('mt')
            ->innerJoin('mt.module', 'm')
            ->innerJoin('mt.test', 't')
            ->where('t.id = :testId')
            ->andWhere('m.reference = :moduleRef')
            ->setParameter('testId', $testId)
            ->setParameter('moduleRef', $moduleRef)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByTestTypeIdANDModule($module, $testTypeId)
    {
        return $this->createQueryBuilder('mt')
            ->innerJoin('mt.module', 'm')
            ->innerJoin('mt.test', 't')
            ->leftJoin('t.typeTest', 'tt')
            ->where('tt.id = :testTypeId')
            ->andWhere('mt.module = :module')
            ->setParameter('testTypeId', $testTypeId)
            ->setParameter('module', $module)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByTestTypeANDModule($module, $testTypeCond)
    {
        return $this->createQueryBuilder('mt')
            ->innerJoin('mt.module', 'm')
            ->innerJoin('mt.test', 't')
            ->leftJoin('t.typeTest', 'tt')
            ->where('tt.conditional = :testTypeCond')
            ->andWhere('mt.module = :module')
            ->setParameter('testTypeCond', $testTypeCond)
            ->setParameter('module', $module)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
