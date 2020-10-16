<?php

namespace App\Repository\PlanningManagement;

use App\Entity\PlanningManagement\VirtualClassRoom;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method VirtualClassRoom|null find($id, $lockMode = null, $lockVersion = null)
 * @method VirtualClassRoom|null findOneBy(array $criteria, array $orderBy = null)
 * @method VirtualClassRoom[] findAll()
 * @method VirtualClassRoom[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VirtualClassRoomRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, VirtualClassRoom::class);
    }

    /**
     * find student Virtual ClassRoom
     *
     * @return VirtualClassRoom[] Returns an array of VirtualClassRoom objects
     */
    public function findVirtualClassRoom($current = VirtualClassRoom::NOW)
    {
        switch ($current) {
          case VirtualClassRoom::NOW:
            $qb = $this->createQueryBuilder('v');
            $qb->andWhere('v.openingDate <= :now')
               ->andWhere('v.closingDate >= :now')
               ->orderBy('v.closingDate', 'ASC')
               ->setParameters(['now' => new \DateTime()]);

            return $qb->getQuery()->getResult();
            break;
          case VirtualClassRoom::FUTURE:
            $qb = $this->createQueryBuilder('v');
            $qb->andWhere('v.openingDate > :now')
               ->orderBy('v.openingDate', 'ASC')
               ->setParameters(['now' => new \DateTime()]);

            return $qb->getQuery()->getResult();
            break;
          case VirtualClassRoom::PASSED:
            $qb = $this->createQueryBuilder('v');
            $qb->andWhere('v.closingDate < :now')
               ->orderBy('v.openingDate', 'DESC')
               ->setParameters(['now' => new \DateTime()]);

            return $qb->getQuery()->getResult();
            break;
        }
    }

    /**
     * find Teacher Virtual ClassRoom
     *
     * @return VirtualClassRoom[] Returns an array of VirtualClassRoom objects
     */
    public function findTeacherVirtualClassRoom($teacher, $current = VirtualClassRoom::NOW)
    {
        switch ($current) {
          case VirtualClassRoom::NOW:
            // Query
            $qb = $this->createQueryBuilder('v');
            $qb->andWhere('v.openingDate <= :now')
               ->andWhere('v.closingDate >= :now')
               ->andWhere('v.teacher = :teacher')
               ->orderBy('v.closingDate', 'ASC')
               ->setParameters(['now' => new \DateTime(),
                                      'teacher' => $teacher]);

            return $qb->getQuery()->getResult();
            break;
          case VirtualClassRoom::FUTURE:
            $qb = $this->createQueryBuilder('v');
            $qb->andWhere('v.openingDate > :now')
               ->andWhere('v.teacher = :teacher')
               //->andWhere('(su.id = :user OR st.id IN (:team) OR sd.id IN (:division))')
               ->orderBy('v.openingDate', 'ASC')
               ->setParameters(['now' => new \DateTime(),
                                      'teacher' => $teacher]);

            return $qb->getQuery()->getResult();
            break;
          case VirtualClassRoom::PASSED:
            $qb = $this->createQueryBuilder('v');
            $qb->andWhere('v.closingDate < :now')
               ->andWhere('v.teacher = :teacher')
               //->andWhere('(su.id = :user OR st.id IN (:team) OR sd.id IN (:division))')
               ->orderBy('v.openingDate', 'ASC')
               ->setParameters(['now' => new \DateTime(),
                                      'teacher' => $teacher]);

            return $qb->getQuery()->getResult();
            break;
        }
    }

    /**
     * find student Virtual ClassRoom
     *
     * @return VirtualClassRoom[] Returns an array of VirtualClassRoom objects
     */
    public function findStudentVirtualClassRoom($student, $current = VirtualClassRoom::NOW)
    {
        switch ($current) {
          case VirtualClassRoom::NOW:
            $qb = $this->createQueryBuilder('v');
            $qb->andWhere('v.openingDate <= :now')
               ->andWhere('v.closingDate >= :now')
               ->andWhere(':student MEMBER OF v.students')
               ->orderBy('v.closingDate', 'ASC')
               ->setParameters(['now' => new \DateTime(),
                                      'student' => $student]);

            return $qb->getQuery()->getResult();
            break;
          case VirtualClassRoom::FUTURE:
            $qb = $this->createQueryBuilder('v');
            $qb->andWhere('v.openingDate > :now')
               ->andWhere(':student MEMBER OF v.students')
               ->orderBy('v.openingDate', 'ASC')
               ->setParameters(['now' => new \DateTime(),
                                      'student' => $student]);

            return $qb->getQuery()->getResult();
            break;
          case VirtualClassRoom::PASSED:
            $qb = $this->createQueryBuilder('v');
            $qb->andWhere('v.closingDate < :now')
               ->andWhere(':student MEMBER OF v.students')
               ->orderBy('v.openingDate', 'ASC')
               ->setParameters(['now' => new \DateTime(),
                                      'student' => $student]);

            return $qb->getQuery()->getResult();
            break;
        }
    }

    // /**
    //  * @return VirtualClassRoom[] Returns an array of VirtualClassRoom objects
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
    public function findOneBySomeField($value): ?VirtualClassRoom
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
