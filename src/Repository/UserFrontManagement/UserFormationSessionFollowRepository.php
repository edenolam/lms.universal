<?php

namespace App\Repository\UserFrontManagement;

use App\Entity\UserFrontManagement\UserFormationSessionFollow;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method UserFormationSessionFollow|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserFormationSessionFollow|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserFormationSessionFollow[] findAll()
 * @method UserFormationSessionFollow[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserFormationSessionFollowRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, UserFormationSessionFollow::class);
    }

    public function filterUserFormation($module, $typeTest, $formationPath, $user, $division, $team, $year, $orderBy, $order)
    {
        $qb = $this->createQueryBuilder('us')
            ->innerJoin('us.session', 's')
            ->innerJoin('us.formation', 'fp')
            ->leftJoin('fp.formationPathModules', 'fpm');
        if ($module != null) {
            $qb->leftJoin('fpm.module', 'm')
                ->andWhere('m.id = :moduleId')
                ->setParameter('moduleId', $module->getId());
        }
        if ($formationPath != null) {
            $qb->andWhere('fp.id = :formationPathId')
                ->setParameter('formationPathId', $formationPath->getId());
        }
        if ($user != null) {
            $qb->innerJoin('us.user', 'u')
                ->andWhere('u.id = :userId')
                ->setParameter('userId', $user->getId());
        }
        if ($division != null) {
            $qb->innerJoin('s.divisions', 'd')
                ->andWhere('d.id = :divisionId')
                ->setParameter('divisionId', $division->getId());
        }
        if ($team != null) {
            $qb->innerJoin('s.teams', 'tm')
                ->andWhere('tm.id = :teamId')
                ->setParameter('teamId', $team->getId());
        }
        if ($year != null) {
            $qb->andWhere('YEAR(s.openingDate) = :year')
                ->setParameter('year', $year);
        }
        if ($orderBy != null) {
            if ($orderBy == 1) {
                $qb->orderBy('fp.title', $order);
            } elseif ($orderBy == 2) {
                $qb->orderBy('s.title', $order);
            } else {
                $qb->orderBy('s.openingDate', $order);
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return number of ongooing formation
     * @Param User user
     */
    public function userCountAllStart($user = null)
    {
        $qb = $this->_em->createQueryBuilder()
                    ->select('COUNT(us)')
                    ->from('App:UserFrontManagement\UserFormationSessionFollow', 'us')
                    ->innerJoin('us.session', 's')
                    ->andWhere('s.isValid = 1')
                    ->andWhere('s.openingDate <= :start')
                    ->andWhere('s.closingDate >= :end')
                    ->andWhere('us.endDate IS NULL')
                    ->andWhere('(us.user = :user)')
                    ->orderBy('s.closingDate', 'ASC')
                    ->setParameters(['start' => new \DateTime(),
                                          'end' => new \DateTime(),
                                          'user' => $user]);

        $totalStartSession = $qb->getQuery()->getSingleScalarResult();

        return $totalStartSession;
    }

    /**
     * @return number of ongooing formation
     * @Param User user
     */
    public function userCountAllDone($user = null)
    {
        $qb = $this->_em->createQueryBuilder()
                    ->select('COUNT(us)')
                    ->from('App:UserFrontManagement\UserFormationSessionFollow', 'us')
                    ->innerJoin('us.session', 's')
                    ->andWhere('s.isValid = 1')
                    ->andWhere('(us.user = :user)')
                    ->andWhere('(us.endDate IS NOT NULL)')
                    ->setParameters(['user' => $user]);

        $totalStartSession = $qb->getQuery()->getSingleScalarResult();

        return $totalStartSession;
    }

    public function findByActiveSession($user)
    {
        $qb = $this->createQueryBuilder('usf')
                ->leftJoin('usf.session', 's')
                ->leftJoin('usf.user', 'u')
                ->where('u.id = :userId')
                ->andWhere('s.openingDate <= :start')
                ->andWhere('s.closingDate >= :end')
                //->andWhere('usf.success != 1')
                ->orderBy('s.closingDate', 'ASC')
                ->setParameters(
                    [
                        'start' => new \DateTime(),
                        'end' => new \DateTime(),
                        'userId' => $user->getId()
                    ]
                );

        return $qb->getQuery()->getResult();
    }

    public function findByPastSession($user)
    {
        $qb = $this->createQueryBuilder('usf')
                ->leftJoin('usf.session', 's')
                ->leftJoin('usf.user', 'u')
                ->where('u.id = :userId')
                ->andWhere('s.closingDate <= :end')
                ->orderBy('s.closingDate', 'ASC')
                ->setParameters(
                    [
                        'end' => new \DateTime(),
                        'userId' => $user->getId()
                    ]
                );

        return $qb->getQuery()->getResult();
    }

    public function findLastSessionFormationBypageAndUser($user, $slugF)
    {
        $qb = $this->createQueryBuilder('FF')
            ->leftJoin('FF.formation', 'f')
            ->leftJoin('FF.session', 's')
            ->where('FF.user = :user')
            ->setParameter('user', $user)
            ->andWhere('f.slug = :slugF')
            ->setParameter('slugF', $slugF)
            ->andWhere('s.openingDate <= :start')
            ->setParameter('start', new \DateTime())
            ->orderBy('s.openingDate', 'DESC');

        return $qb->getQuery()->setMaxResults(1)->getOneOrNullResult();
    }

    public function getSessionFormationFollow($session, $userTuteur, $role, $tutorFollow = null)
    {
        $qb = $this->createQueryBuilder('us')
                    ->leftJoin('us.session', 's')
                    ->leftjoin('us.user', 'u')
                    ->addSelect('u')
                    ->leftJoin('u.supervisors', 'sup');

        if ($role == 'tuteur') {
            $qb->where('sup.id = :supervisor' . $userTuteur->getId() . '')
            ->setParameter('supervisor' . $userTuteur->getId() . '', $userTuteur->getId());
            if ($tutorFollow != null) {
                foreach ($tutorFollow as $tutor) {
                    $qb->orWhere('sup.id = :supervisor' . $tutor->getId() . '')
                    ->setParameter('supervisor' . $tutor->getId() . '', $tutor->getId());
                }
            }
            $qb->andWhere('u.hierarchyLevel < :hierarchyLevel')
                ->setParameter('hierarchyLevel', $userTuteur->getHierarchyLevel());
        }

        $qb->andWhere('s.id =:sessionId')
               ->setParameter('sessionId', $session->getId())
                ->andWhere('u.isValid = true')
                ->andWhere('u.enabled = true')
                ->orderBy('u.username', 'ASC');

        return $qb->orderBy('us.LastConnexion', 'DESC')->getQuery()->getResult();
    }
}
