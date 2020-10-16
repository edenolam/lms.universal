<?php

namespace App\Repository\UserFrontManagement;

use App\Entity\UserFrontManagement\UserCourseFollow;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method UserCourseFollow|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserCourseFollow|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserCourseFollow[] findAll()
 * @method UserCourseFollow[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserCourseFollowRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCourseFollow::class);
    }

    public function getTotalFollow($user, $session, $module)
    {
        $qb = $this->_em->createQueryBuilder()
                ->select('COUNT(CF)')
                ->from('App:UserFrontManagement\UserCourseFollow', 'CF')
                ->innerJoin('CF.course', 'c')
                ->where('CF.user = :user')
                ->andWhere('c.isValid = 1')
                ->setParameter('user', $user->getId())
                ->andWhere('CF.session = :session')
                ->setParameter('session', $session->getId())
                ->andWhere('CF.module = :module')
                ->setParameter('module', $module->getId());

        return $qb->getQuery()->setMaxResults(1)->getSingleScalarResult();
    }

    public function getTotalTimeSecModule($user, $session, $module)
    {
        $qb = $this->_em->createQueryBuilder()
                ->select('SUM(CF.durationTotalSec) AS TimetotalSec')
                ->from('App:UserFrontManagement\UserCourseFollow', 'CF')
                ->innerJoin('CF.course', 'c')
                ->where('CF.user = :user')
                ->andWhere('c.isValid = 1')
                ->setParameter('user', $user->getId())
                ->andWhere('CF.session = :session')
                ->setParameter('session', $session->getId())
                ->andWhere('CF.module = :module')
                ->setParameter('module', $module->getId());
        return $qb->getQuery()->setMaxResults(1)->getSingleScalarResult();
    }
   

//    /**
//     * @return UserCourseFollow[] Returns an array of UserCourseFollow objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?UserCourseFollow
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function findLastSessionCourseBypageAndUser($user, $slugC)
    {
        $qb = $this->createQueryBuilder('CF')
        ->leftJoin('CF.course', 'c')
            ->leftJoin('CF.session', 's')
            ->where('CF.user = :user')
            ->setParameter('user', $user)
            ->andWhere('c.slug = :slugC')
            ->setParameter('slugC', $slugC)
            ->andWhere('s.openingDate <= :start')
            ->setParameter('start', new \DateTime())
            ->orderBy('s.openingDate', 'DESC');

        return $qb->getQuery()->setMaxResults(1)->getOneOrNullResult();
    }
}
