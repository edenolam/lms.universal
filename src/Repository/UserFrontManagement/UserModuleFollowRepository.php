<?php

namespace App\Repository\UserFrontManagement;

use App\Entity\PlanningManagement\Session;
use App\Entity\UserFrontManagement\UserModuleFollow;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method UserModuleFollow|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserModuleFollow|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserModuleFollow[] findAll()
 * @method UserModuleFollow[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserModuleFollowRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, UserModuleFollow::class);
    }

    public function getTotalFollow($user, $session)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('COUNT(MF)')
            ->from('App:UserFrontManagement\UserModuleFollow', 'MF')
            ->where('MF.user = :user')
            ->setParameter('user', $user->getId())
            ->andWhere('MF.session = :session')
            ->setParameter('session', $session->getId());

        return $qb->getQuery()->setMaxResults(1)->getSingleScalarResult();
    }
    public function getTotalTimeSecSession($user, $session)
    {
        $qb = $this->_em->createQueryBuilder()
                ->select('SUM(MF.durationTotalSec) AS TimetotalSec')
                ->from('App:UserFrontManagement\UserModuleFollow', 'MF')
                ->innerJoin('MF.module', 'm')
                ->where('MF.user = :user')
                ->andWhere('m.isValid = 1')
                ->setParameter('user', $user->getId())
                ->andWhere('MF.session = :session')
                ->setParameter('session', $session->getId());
                
        return $qb->getQuery()->setMaxResults(1)->getSingleScalarResult();
    }

    public function tableauFiltreResult($divisions = null, $teams = null, $users = null, $modules = null, $formations = null, $sessions = null, $datestart = null, $dateend = null, $userTuteur, $role, $tutorFollow = null, $typeModule = null)
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.session', 's')
            ->leftjoin('u.user', 'us')
            ->leftJoin('us.supervisors', 'sup')
            ->leftjoin('us.team', 't')
            ->leftjoin('us.division', 'd')
            ->leftJoin('u.module', 'm')
            ->leftJoin('m.type', 'mt')
            ->leftJoin('m.formationPathModules', 'fpm')
            ->leftJoin('s.formationPath', 'fp');

        if ($role == 'tuteur') {
            $qb->where('sup.id = :supervisor' . $userTuteur->getId() . '')
                ->setParameter('supervisor' . $userTuteur->getId() . '', $userTuteur->getId());
            if ($tutorFollow != null) {
                foreach ($tutorFollow as $tutor) {
                    $qb->orWhere('sup.id = :supervisor' . $tutor->getId() . '')
                        ->setParameter('supervisor' . $tutor->getId() . '', $tutor->getId());
                }
            }
            $qb->andWhere('us.hierarchyLevel < :hierarchyLevel')
                ->setParameter('hierarchyLevel', $userTuteur->getHierarchyLevel());
        }
        if ($typeModule) {
            $qb->andWhere('mt.conditional = :typeModule')
                ->setParameter('typeModule', $typeModule);
        }

        if (sizeof($sessions)>0) { //Doctrine/Common/Collections/ArrayCollection
            $qb->andWhere('s.id IN (:sessionId)')
                ->setParameter('sessionId', $sessions);
        }


        if (sizeof($teams)>0) { //Doctrine/Common/Collections/ArrayCollection
           $qb->andWhere('t.id IN (:teamId)')
                ->setParameter('teamId' , $teams);
        }


       if (sizeof($divisions)>0) { //Doctrine/Common/Collections/ArrayCollection
            $qb->andWhere('d.id IN (:divisionId)')
                ->setParameter('divisionId', $divisions);
        }


        if (sizeof($formations)>0) { //Doctrine/Common/Collections/ArrayCollection
            $qb->andWhere('fp.id IN (:formationId)')
                ->setParameter('formationId', $formations);
        }


       if (sizeof($modules)>0) { //Doctrine/Common/Collections/ArrayCollection
           $qb->andWhere('m.id IN (:moduleId)')
                ->setParameter('moduleId' , $modules);
        }


       if (sizeof($users) >0) { //Doctrine/Common/Collections/ArrayCollection
            $qb->andWhere('us.id IN (:userId)')
                ->setParameter('userId' , $users);
        }

        if ($datestart) {
            $qb->andWhere('s.openingDate >= :datestart')
                ->setParameter('datestart', $datestart)
            ;
        }
        if ($dateend) {
            $qb->andWhere('s.closingDate <= :dateend')
                ->setParameter('dateend', $dateend)
            ;
        }


        $qb->andWhere('u.isDeleted = :isDeleted')
            ->setParameter('isDeleted', 0)
            ->andWhere('us.isValid = true')
            ->andWhere('us.enabled = true')
            ->andWhere('mt.conditional != :conditional')
            ->setParameter('conditional', 'notFollow')
            ->orderBy('us.username', 'ASC');

        //   dump($qb->getQuery());
        //  exit();

        return $qb->orderBy('u.LastConnexion', 'DESC')->getQuery()->getResult();
        }

    public function resultTeam($team)
    {
        $qb = $this->createQueryBuilder('u')
            ->join('u.user', 'us')
            ->addSelect('us')
            ->where('us.id = :user')
            ->setParameter('user', $team->getId());

        return $qb->getQuery()->getResult();
    }

    public function getTotalFollowEnd($user, $session)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('MF')
            ->from('App:UserFrontManagement\UserModuleFollow', 'MF')
            ->leftJoin('MF.module', 'm')
            ->leftJoin('m.type', 'mt')
            ->where('MF.user = :user')
            ->setParameter('user', $user->getId())
            ->andWhere('MF.session = :session')
            ->andWhere('MF.endDate IS NOT NULL')
            ->setParameter('session', $session->getId());

        return $qb->getQuery()->getResult();
    }

    public function findLastSessionModuleBypageAndUser($user, $slugM)
    {
        $qb = $this->createQueryBuilder('MF')
            ->leftJoin('MF.module', 'm')
            ->leftJoin('MF.session', 's')
            ->where('MF.user = :user')
            ->setParameter('user', $user)
            ->andWhere('m.slug = :slugM')
            ->setParameter('slugM', $slugM)
            ->andWhere('s.openingDate <= :start')
            ->setParameter('start', new \DateTime())
            ->orderBy('s.openingDate', 'DESC');

        return $qb->getQuery()->setMaxResults(1)->getOneOrNullResult();
    }

//    /**
//     * @return UserModuleFollow[] Returns an array of UserModuleFollow objects
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
    public function findOneBySomeField($value): ?UserModuleFollow
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
