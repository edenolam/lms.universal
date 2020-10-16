<?php

namespace App\Repository\UserFrontManagement;

use App\Entity\UserFrontManagement\UserPageFollow;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method UserPageFollow|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserPageFollow|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserPageFollow[] findAll()
 * @method UserPageFollow[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserPageFollowRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPageFollow::class);
    }

    public function getTotalFollow($user, $session, $module = null, $course = null)
    {
        $qb = $this->_em->createQueryBuilder()
                ->select('COUNT(PF)')
                ->from('App:UserFrontManagement\UserPageFollow', 'PF')
                ->innerJoin('PF.page', 'p')
                ->where('PF.user = :user')
                ->andWhere('p.isValid = 1')
                ->setParameter('user', $user->getId())
                ->andWhere('PF.session = :session')
                ->setParameter('session', $session->getId());
        if ($module != null) {
            $qb->andWhere('PF.module = :module')
                ->setParameter('module', $module->getId());
        }
        if ($course != null) {
            $qb->andWhere('PF.course = :course')
                ->setParameter('course', $course->getId());
        }

        return $qb->getQuery()->setMaxResults(1)->getSingleScalarResult();
    }

    public function findByUserANDModuleANDPageANDSessionID($userId, $moduleId, $pageId, $sessionId)
    {
        return $this->createQueryBuilder('upf')
            ->innerJoin('upf.user', 'u')
            ->innerJoin('upf.module', 'm')
            ->innerJoin('upf.page', 'p')
            ->innerJoin('upf.session', 's')
            ->where('u.id = :userId')
            ->andWhere('m.id = :moduleId')
            ->andWhere('p.id = :pageId')
            ->andWhere('s.id = :sessionId')
            ->setParameter('userId', $userId)
            ->setParameter('moduleId', $moduleId)
            ->setParameter('pageId', $pageId)
            ->setParameter('sessionId', $sessionId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findLastUserNote($user)
    {
        $qb = $this->createQueryBuilder('PF')
            ->where('PF.user = :user')
            ->setParameter('user', $user)
            ->andWhere('PF.note IS NOT NULL')
            ->orderBy('PF.LastConnexion', 'DESC');

        return $qb->getQuery()->setMaxResults(1)->getOneOrNullResult();
    }

    public function findLastUserPage($user)
    {
        $qb = $this->createQueryBuilder('PF')
            ->where('PF.user = :user')
            ->setParameter('user', $user)
            ->orderBy('PF.LastConnexion', 'DESC');

        return $qb->getQuery()->setMaxResults(1)->getOneOrNullResult();
    }

    public function findLastSessionPageBypageAndUser($user, $slugP)
    {
        $qb = $this->createQueryBuilder('PF')
            ->leftJoin('PF.page', 'p')
            ->leftJoin('PF.session', 's')
            ->where('PF.user = :user')
            ->setParameter('user', $user)
            ->andWhere('p.slug = :slugP')
            ->setParameter('slugP', $slugP)
            ->andWhere('s.openingDate <= :start')
            ->setParameter('start', new \DateTime())
            ->orderBy('s.openingDate', 'DESC');

        return $qb->getQuery()->setMaxResults(1)->getOneOrNullResult();
    }

    public function findNotesByUser($user)
    {
        $qb = $this->createQueryBuilder('PF')
              ->where('PF.user = :user')
              ->andWhere('PF.note IS NOT NULL')
              ->setParameter('user', $user)
              ->orderBy('PF.LastConnexion', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function findByNote($user, $session, $module)
    {
        $qb = $this->createQueryBuilder('PF')
            ->where('PF.user = :user')
            ->andWhere('PF.module = :module')
            ->andWhere('PF.session = :session')
            ->andWhere('PF.note IS NOT NULL')
            ->setParameter('user', $user)
            ->setParameter('module', $module)
            ->setParameter('session', $session)
            ->orderBy('PF.LastConnexion', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function findNotesForExport($user, $session, $module)
    {
        $qb = $this->createQueryBuilder('PF')
            ->where('PF.user = :user')
            ->andWhere('PF.note IS NOT NULL')
            ->andWhere('PF.session = :session')
            ->andWhere('PF.module = :module')
            ->setParameter('user', $user)
            ->setParameter('session', $session)
            ->setParameter('module', $module)
            ->orderBy('PF.LastConnexion', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function filterNotes($user, $formationSlug = null, $moduleSlug = null, $courseSlug = null)
    {
        $qb = $this->createQueryBuilder('pf')
                ->where('pf.user = :user');

        if ($formationSlug != null && $formationSlug != 'null') {
            $qb->leftJoin('pf.session', 's')
                ->leftJoin('s.formationPath', 'fp')
                ->andWhere('fp.slug = :formationSlug')
                ->setParameter('formationSlug', $formationSlug);
        }

        if ($moduleSlug != null && $moduleSlug != 'null') {
            $qb->leftJoin('pf.module', 'm')
                ->andWhere('m.slug = :moduleSlug')
                ->setParameter('moduleSlug', $moduleSlug);
        }

        if ($courseSlug != null && $courseSlug != 'null') {
            $qb->leftJoin('pf.course', 'c')
                ->andWhere('c.slug = :courseSlug')
                ->setParameter('courseSlug', $courseSlug);
        }

        $qb->andWhere('pf.note IS NOT NULL')
            ->setParameter('user', $user)
            ->orderBy('pf.LastConnexion', 'DESC');

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return UserPageFollow[] Returns an array of UserPageFollow objects
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
    public function findOneBySomeField($value): ?UserPageFollow
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getTotalTimeSecCourse($user, $session, $module = null, $course = null)
    {
        $qb = $this->_em->createQueryBuilder()
                ->select('SUM(PF.durationTotalSec) AS TimetotalSec')
                ->from('App:UserFrontManagement\UserPageFollow', 'PF')
                ->innerJoin('PF.page', 'p')
                ->where('PF.user = :user')
                ->andWhere('p.isValid = 1')
                ->setParameter('user', $user->getId())
                ->andWhere('PF.session = :session')
                ->setParameter('session', $session->getId());
        if ($module != null) {
            $qb->andWhere('PF.module = :module')
                ->setParameter('module', $module->getId());
        }
        if ($course != null) {
            $qb->andWhere('PF.course = :course')
                ->setParameter('course', $course->getId());
        }

        return $qb->getQuery()->setMaxResults(1)->getSingleScalarResult();
    }
}
