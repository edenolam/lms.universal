<?php

namespace App\Repository\UserFrontManagement;

use App\Entity\PlanningManagement\Session;
use App\Entity\TestManagement\Test;
use App\Entity\UserFrontManagement\UserTest;
use App\Entity\UserManagement\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method UserTest|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserTest|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserTest[] findAll()
 * @method UserTest[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserTestRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, UserTest::class);
    }

    public function findEvalByTestANDUserId($userId, $testId)
    {
        return $this->createQueryBuilder('ut')
            ->innerJoin('ut.user', 'u')
            ->innerJoin('ut.test', 't')
            ->join('t.typeTest', 'tt')
            ->where('t.id = :testId')
            ->andWhere('u.id = :userId')
            ->andWhere('tt.id = 4')
            ->setParameter('testId', $testId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    public function findPreTestByTestAndUserIdAndModuleRef($userId, $testId, $moduleRef, $sessionId)
    {
        return $this->createQueryBuilder('ut')
            ->innerJoin('ut.user', 'u')
            ->innerJoin('ut.test', 't')
            ->join('t.typeTest', 'tt')
            ->where('t.id = :testId')
            ->andwhere('ut.session = :sessionId')
            ->andWhere('ut.refModule = :refModule')
            ->andWhere('u.id = :userId')
            ->andWhere('tt.id = 1')
            ->setParameter('testId', $testId)
            ->setParameter('userId', $userId)
            ->setParameter('refModule', $moduleRef)
            ->setParameter('sessionId', $sessionId)
            ->getQuery()
            ->getResult();
    }

    /**
     * findLastTestByTestType
     *
     * @param User $user
     * @param Session $session
     * @param string $test_type
     * @return mixed
     */
    public function findLastTestByTestType(User $user, Session $session, string $test_type)
    {
        return $this->createQueryBuilder('ut')
            ->join('ut.test', 't')
            ->join('t.typeTest', 'tt')
            ->where('ut.session = :session')
            ->andwhere('ut.user = :user')
            ->andwhere('tt.conditional = :test_type')
            ->setParameters(['user' => $user, 'session' => $session, 'test_type' => $test_type])
            ->orderBy('ut.tentative', 'DESC')
            ->getQuery()->setMaxResults(1)
            ->getOneOrNullResult();
    }

      /**
     * findLastTestByTestType
     *
     * @param User $user
     * @param Session $session
     * @param string $test_type
     * @return mixed
     */
    public function findLastTestByTest(User $user, Session $session, $testId, string $test_type)
    {
        if($testId == null){
            return null;
        }else{
            return $this->createQueryBuilder('ut')
                ->join('ut.test', 't')
                ->join('t.typeTest', 'tt')
                ->where('ut.session = :session')
                ->andwhere('ut.user = :user')
                ->andwhere('tt.conditional = :test_type')
                ->andwhere('t.id = :id')
                ->andwhere('ut.score >= 0')
                ->setParameters(['user' => $user, 'session' => $session, 'test_type' => $test_type, 'id' => $testId])
                ->orderBy('ut.tentative', 'DESC')
                ->getQuery()->setMaxResults(1)
                ->getOneOrNullResult();
        }
    }

    public function findPreTest($userId, $sessionId, $moduleRef)
    {
        return $this->createQueryBuilder('ut')
            ->innerJoin('ut.user', 'u')
            ->innerJoin('ut.test', 't')
            ->join('t.typeTest', 'tt')
            ->where('ut.session = :sessionId')
            ->andWhere('ut.refModule = :refModule')
            ->andWhere('u.id = :userId')
            ->andWhere('tt.id = 1')
            ->setParameter('userId', $userId)
            ->setParameter('sessionId', $sessionId)
            ->setParameter('refModule', $moduleRef)
            ->getQuery()
            ->getResult();
    }

    public function findScoreOfSessionLastEvalUser($evaluation,$session,$user){
        $query = $this->createQueryBuilder('ut')
                    ->select('ut.score AS score, MAX(ut.tentative) AS max_temp')
                    ->innerJoin('ut.user', 'u')
                    ->innerJoin('ut.test', 't')
                    ->where('t.id = :testId')
                    ->andWhere('ut.session = :session')
                    ->andWhere('ut.user = :user ')
                    ->setParameter('testId', $evaluation->getId())
                    ->setParameter('user', $user)
                    ->setParameter('session', $session)
                    ->groupBy('u.id')
                    ->setMaxResults(1);
       
        return $query->getQuery()->getOneOrNullResult();
    }

    public function findByTestAndUserIdAndModuleRef($userId, $testId, $moduleRef, $sessionId)
    {
        return $this->createQueryBuilder('ut')
            ->innerJoin('ut.user', 'u')
            ->innerJoin('ut.test', 't')
            ->where('t.id = :testId')
            ->andwhere('ut.session = :sessionId')
            ->andWhere('ut.refModule = :moduleRef')
            ->andWhere('u.id = :userId')
            ->setParameter('testId', $testId)
            ->setParameter('userId', $userId)
            ->setParameter('moduleRef', $moduleRef)
            ->setParameter('sessionId', $sessionId)
            ->getQuery()
            ->getResult();
    }

    public function findByTestLastUser($userId, $testId, $session)
    {
        return $this->createQueryBuilder('ut')
            ->innerJoin('ut.user', 'u')
            ->innerJoin('ut.test', 't')
            ->where('t.id = :testId')
            ->andWhere('ut.session = :session')
            ->andWhere('u.id = :userId')
            ->setParameter('testId', $testId)
            ->setParameter('userId', $userId)
            ->setParameter('session', $session)
            ->setMaxResults(1)
            ->orderBy('ut.tentative', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function successTestByType($user, $testTypeConditional, $module, $session, $test)
    {
        return $this->createQueryBuilder('ut')
            ->leftJoin('ut.test', 't')
            ->leftJoin('ut.session', 's')
            ->andWhere('t.id = :testId')
            ->andWhere('ut.user = :user')
            ->andWhere('s.id = :sessionId')
            ->andWhere('ut.datePass IS NOT NULL')
            ->setParameter('user', $user)
            ->setParameter('testId', $test->getId())
            ->setParameter('sessionId', $session->getId())
            ->getQuery()->setMaxResults(1)->getOneOrNullResult();
    }

    public function resultSondage($division = null, $team = null, $user = null, $test = null, $formationPath = null, $session = null)
    {
        $qb = $this->createQueryBuilder('ut')
            ->leftJoin('ut.test', 't')
            ->leftJoin('t.typeTest', 'testT')
            ->leftJoin('ut.session', 's')
            ->leftjoin('ut.user', 'us')
            ->leftJoin('us.supervisors', 'sup')
            ->leftjoin('us.team', 'team')
            ->leftjoin('us.division', 'd')
            ->leftJoin('s.formationPath', 'fp')
            ;

        if ($session instanceof Session) {
            $qb->andWhere('s.id =:sessionId')
                ->setParameter('sessionId', $session->getId());
        } elseif (is_array($session)) {
            $i = 0;
            foreach ($session as $ses) {
                if ($i == 0) {
                    $qb->andWhere('s.id = :sessionId' . $ses->getId() . '')
                        ->setParameter('sessionId' . $ses->getId() . '', $ses->getId());
                } else {
                    $qb->orWhere('s.id = :sessionId' . $ses->getId() . '')
                        ->setParameter('sessionId' . $ses->getId() . '', $ses->getId());
                }
                $i++;
            }
        } elseif ($session and !$session->isEmpty()) { //Doctrine/Common/Collections/ArrayCollection
            $i = 0;
            foreach ($session as $ses) {
                if ($i == 0) {
                    $qb->andWhere('s.id IN (:sessionId' . $ses->getId() . ')')
                        ->setParameter('sessionId' . $ses->getId() . '', $ses->getId());
                } else {
                    $qb->orWhere('s.id IN (:sessionId' . $ses->getId() . ')')
                        ->setParameter('sessionId' . $ses->getId() . '', $ses->getId());
                }
                $i++;
            }
        }

        if ($formationPath) {
            $qb->andWhere('fp.id = :formationPathId')
                ->setParameter('formationPathId', $formationPath->getId());
        }
        if ($user) {
            $qb->andWhere('us.id = :user')
                ->setParameter('user', $user->getId());
        }
        if ($team) {
            $qb->andWhere('team.id = :team')
                ->setParameter('team', $team->getId());
        }
        if ($division) {
            $qb->andWhere('d.id = :division')
                ->setParameter('division', $division->getId());
        }
        if ($test) {
            $qb->andWhere('t.id = :test')
                ->setParameter('test', $test->getId());
        }
        // isDeleted == false;
        $qb->andWhere('us.isValid = true')
            ->andWhere('us.enabled = true')
            ->andWhere('ut.lastIndexQuestion = -1')
            ->andWhere('testT.conditional = :conditional')
            ->setParameter('conditional', 'sondage')
            ->orderBy('us.username', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /*
    public function findOneBySomeField($value): ?UserTest
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
