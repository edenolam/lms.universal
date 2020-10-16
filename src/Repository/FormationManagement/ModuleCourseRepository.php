<?php

namespace App\Repository\FormationManagement;

use App\Entity\FormationManagement\ModuleCourse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method ModuleCourse|null find($id, $lockMode = null, $lockVersion = null)
 * @method ModuleCourse|null findOneBy(array $criteria, array $orderBy = null)
 * @method ModuleCourse[] findAll()
 * @method ModuleCourse[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModuleCourseRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, ModuleCourse::class);
    }

    public function findByModuleId($id)
    {
        return $this->createQueryBuilder('mc')
            ->innerJoin('mc.module', 'm')
            ->where('m.id = :id')
            ->orderBy('mc.sort', 'ASC')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByCourseId($id)
    {
        return $this->createQueryBuilder('mc')
            ->innerJoin('mc.course', 'c')
            ->where('c.id = :id')
            ->orderBy('mc.sort', 'ASC')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findSortByModuleId($id)
    {
        return $this->createQueryBuilder('mc')
            ->select('mc.sort')
            ->innerJoin('mc.module', 'm')
            ->where('m.id = :id')
            ->orderBy('mc.sort', 'DESC')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByModuleANDCourseId($moduleId, $courseId)
    {
        return $this->createQueryBuilder('mc')
            ->innerJoin('mc.module', 'm')
            ->innerJoin('mc.course', 'c')
            ->where('m.id = :module_id')
            ->andWhere('c.id = :course_id')
            ->setParameter('module_id', $moduleId)
            ->setParameter('course_id', $courseId)
            ->orderBy('mc.sort', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
