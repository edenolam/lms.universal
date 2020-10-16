<?php

namespace App\Repository\TestManagement;

use App\Entity\TestManagement\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Question|null find($id, $lockMode = null, $lockVersion = null)
 * @method Question|null findOneBy(array $criteria, array $orderBy = null)
 * @method Question[] findAll()
 * @method Question[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    public function getTotal()
    {
        $aResultTotal = $this->getEntityManager()
            ->createQuery('SELECT COUNT(Q) FROM App:TestManagement\Question Q')
            ->setMaxResults(1)
            ->getSingleScalarResult();

        return $aResultTotal;
    }

    public function createPaginator(Query $query, int $page): Pagerfanta
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query));
        $paginator->setMaxPerPage(Question::NUM_ITEMS);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    public function findLatest(int $page = 1): Pagerfanta
    {
        $query = $this->getEntityManager()
            ->createQuery('
                SELECT q
                FROM App:TestManagement\Question q
                WHERE q.createDate <= :now
                ORDER BY q.createDate DESC
            ')
            ->setParameter('now', new \DateTime())
        ;

        return $this->createPaginator($query, $page);
    }

    public function getTest($typeId, $courses)
    {
        $qb = $this->_em->createQueryBuilder()
                ->select('Q')
                ->from('App:TestManagement\Question', 'Q')
                ->leftJoin('Q.typeTest', 'T');
        $first = true;
        foreach ($courses as $key => $course) {
            if ($first == true) {
                $qb->Where('Q.refCourse = :C_' . $key)
                ->setParameter('C_' . $key, '' . $course->getCourse()->getReference() . '');
            } else {
                $qb->orWhere('Q.refCourse = :C_' . $key)
                ->setParameter('C_' . $key, '' . $course->getCourse()->getReference() . '');
            }
            $first = false;
        }
        $qb->andWhere('T.id = :type')
            ->andWhere('Q.isValid = true')
            ->setParameter('type', '' . $typeId . '');

        $q = $qb->getQuery()->getResult();

        return $q;
    }

    public function findRequiredByPoolID($idPool)
    {
        return $this->createQueryBuilder('q')
            ->innerJoin('q.pool', 'p')
            ->where('p.id = :id')
            ->andWhere('q.required = true')
            ->andWhere('q.isValid = true')
            ->andWhere('p.isValid = true')
            ->setParameter('id', $idPool)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findUnRequiredPoolID($idPool)
    {
        $qb = $this->createQueryBuilder('q')
        ->innerJoin('q.pool', 'p')
        ->where('p.id = :id')
        ->andWhere('q.required = false')
        ->andWhere('q.isValid = true')
        ->andWhere('p.isValid = true')
        ->setParameter('id', $idPool);

        return  $qb->getQuery()->getResult();
    }

    public function findRequiredByTestID($idTest)
    {
        return $this->createQueryBuilder('q')
            ->innerJoin('q.test', 't')
            ->where('t.id = :id')
            ->andWhere('q.required = true')
            ->andWhere('q.isValid = true')
            ->setParameter('id', $idTest)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findUnrequiredByTestID($idTest)
    {
        $qb = $this->createQueryBuilder('q')
        ->innerJoin('q.test', 't')
        ->where('t.id = :id')
        ->andWhere('q.required = false')
        ->andWhere('q.isValid = true')
        ->setParameter('id', $idTest);

        return  $qb->getQuery()->getResult();
    }

    public function findFirstQuestionByTest($idTest)
    {
        return $this->createQueryBuilder('q')
            ->leftJoin('q.test', 't')
            ->where('t.id = :id')
            ->andWhere('q.isValid = true')
            ->setParameter('id', $idTest)
            ->orderBy('q.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function filterQuestion($module, $typeTest, $formationPath, $user, $division, $team, $year, $orderBy, $order)
    {
        $qb = $this->createQueryBuilder('q')
            ->innerJoin('q.test', 't')
            ->leftJoin('t.moduleTests', 'mt')
            ->leftJoin('mt.module', 'm')
            ->leftJoin('m.formationPathModules', 'fpm')
            ->leftJoin('fpm.formationPath', 'fp')
            ->leftJoin('fp.sessions', 's');
        if ($module != null) {
            $qb->andWhere('m.id = :moduleId')
               ->setParameter('moduleId', $module->getId());
        }
        if ($formationPath != null) {
            $qb->andWhere('fp.id = :formationPathId')
                ->setParameter('formationPathId', $formationPath->getId());
        }
        if ($user != null) {
            $qb->leftJoin('s.users', 'u')
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
        if ($typeTest != null) {
            $qb->leftJoin('t.typeTest', 'tt')
               ->andWhere('tt.id = :typeTestId')
               ->setParameter('typeTestId', $typeTest->getId());
        }
        $qb->andWhere('q.isValid = true');
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

    public function checkQuestionStat($question, $success = null, $lastReview = true)
    {
        $query = $this->_em->createQueryBuilder()
                ->select('COUNT(uq)')
                ->from('App:UserFrontManagement\UserQuestion', 'uq')
                ->leftJoin('uq.question', 'q')
                ->leftJoin('uq.userTest', 'ut')
                ->where('q.id = :id')
                ->setParameter('id', $question->getId());
        if ($lastReview == true) {
            $query->andWhere('ut.dateDown >= :dateUpdate')
                    ->setParameter('dateUpdate', $question->getUpdateDate());
        }

        if ($success == 'oui') {
            $query->andWhere('uq.scored != 0');
        } elseif ($success == 'non') {
            $query->andWhere('uq.scored = 0');
        }

        return  $query->getQuery()->getSingleScalarResult();
    }

    public function sumScore($test, $nbQuestion)
    {
        $sumRequire = $this->_em->createQueryBuilder()
                        ->select('SUM(q.weight) AS score, COUNT(q) AS nbQ')
                        ->from('App:TestManagement\Question', 'q')
                        ->where('q.test =:test')
                        ->andwhere('q.isValid =1')
                        ->andwhere('q.required =1')
                        ->setParameter('test', $test)
                        ->getQuery()
                        ->getScalarResult();

        $nbQuestionToAdd = $nbQuestion - $sumRequire[0]['nbQ'];
        $sumMaxUnrequire = 0;
        $sumMinUnrequire = 0;
        if ($nbQuestionToAdd > 0) {
            $MaxUnrequire = $this->_em->createQueryBuilder()
                            ->select('q.weight')
                            ->from('App:TestManagement\Question', 'q')
                            ->setMaxResults($nbQuestionToAdd)
                            ->where('q.test =:test')
                            ->andwhere('q.isValid =1')
                            ->andwhere('q.required =0')
                            ->setParameter('test', $test)
                            ->orderBy('q.weight', 'DESC')
                            ->getQuery()
                            ->getResult();
            $MinUnrequire = $this->_em->createQueryBuilder()
                            ->select('q.weight')
                            ->from('App:TestManagement\Question', 'q')
                            ->setMaxResults($nbQuestionToAdd)
                            ->where('q.test =:test')
                            ->andwhere('q.isValid =1')
                            ->andwhere('q.required =0')
                            ->setParameter('test', $test)
                            ->orderBy('q.weight', 'ASC')
                            ->getQuery()
                            ->getResult();

            foreach ($MaxUnrequire as $weight) {
                $sumMaxUnrequire += $weight['weight'];
            }
            foreach ($MinUnrequire as $weight) {
                $sumMinUnrequire += $weight['weight'];
            }
        }

        return ['max' => $sumMaxUnrequire + $sumRequire[0]['score'], 'min' => $sumMinUnrequire + $sumRequire[0]['score']];
    }
}
