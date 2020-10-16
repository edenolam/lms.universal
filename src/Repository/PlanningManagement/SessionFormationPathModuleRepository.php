<?php

namespace App\Repository\PlanningManagement;

use App\Entity\PlanningManagement\SessionFormationPathModule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method SessionFormationPathModule|null find($id, $lockMode = null, $lockVersion = null)
 * @method SessionFormationPathModule|null findOneBy(array $criteria, array $orderBy = null)
 * @method SessionFormationPathModule[] findAll()
 * @method SessionFormationPathModule[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SessionFormationPathModuleRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, SessionFormationPathModule::class);
    }

    public function findlastSessionByPageandUser($user, $slugP)
    {
        $qb = $this->createQueryBuilder('sm')
            ->leftJoin('sm.session', 's')
            ->leftJoin('s.users', 'u')
            ->leftJoin('sm.module', 'm')
            ->leftJoin('m.moduleCourses', 'cm')
            ->leftJoin('cm.course', 'c')
            ->leftJoin('c.pages', 'p')
            ->where('u.id = :user')
            ->setParameter('user', $user->getId())
            ->andWhere('p.slug = :slugP')
            ->setParameter('slugP', $slugP)
            ->andWhere('s.openingDate <= :start')
            ->setParameter('start', new \DateTime())
            ->orderBy('s.openingDate', 'DESC');
           
        return $qb->getQuery()->setMaxResults(1)->getOneOrNullResult();
    }

    public function findlastSessionByCourseandUser($user, $slugC)
    {
        $qb = $this->createQueryBuilder('sm')
            ->leftJoin('sm.session', 's')
            ->leftJoin('s.users', 'u')
            ->leftJoin('sm.module', 'm')
            ->leftJoin('m.moduleCourses', 'cm')
            ->leftJoin('cm.course', 'c')
            ->where('u.id = :user')
            ->setParameter('user', $user->getId())
            ->andWhere('c.slug = :slugC')
            ->setParameter('slugC', $slugC)
            ->andWhere('s.openingDate <= :start')
            ->setParameter('start', new \DateTime())
            ->orderBy('s.openingDate', 'DESC');

        return $qb->getQuery()->setMaxResults(1)->getOneOrNullResult();
    }

    public function findlastSessionByModuleandUser($user, $slugM)
    {
        $acces = $this->createQueryBuilder('sm')
            ->leftJoin('sm.session', 's')
            ->leftJoin('s.users', 'u')
            ->leftJoin('sm.module', 'm')
            ->where('u.id = :user')
            ->setParameter('user', $user->getId())
            ->andWhere('m.slug = :slugM')
            ->setParameter('slugM', $slugM)
            ->andWhere('s.openingDate <= :start')
            ->setParameter('start', new \DateTime())
            ->orderBy('s.openingDate', 'DESC')
            ->getQuery()->getResult();

        return $acces;
    }

    // /**
    //  * @return SessionFormationPathModule[] Returns an array of SessionFormationPathModule objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SessionFormationPathModule
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
