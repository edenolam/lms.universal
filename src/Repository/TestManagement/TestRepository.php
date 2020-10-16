<?php

namespace App\Repository\TestManagement;

use App\Entity\TestManagement\Test;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Test|null find($id, $lockMode = null, $lockVersion = null)
 * @method Test|null findOneBy(array $criteria, array $orderBy = null)
 * @method Test[] findAll()
 * @method Test[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TestRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, Test::class);
    }

    public function getTotal()
    {
        $aResultTotal = $this->getEntityManager()
            ->createQuery('SELECT COUNT(T) FROM App:TestManagement\Test T')
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

    public function filterTest($module, $typeTest, $formationPath, $user, $division, $team, $year, $orderBy, $order)
    {
        $qb = $this->createQueryBuilder('t')
            ->innerJoin('t.moduleTests', 'mt')
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
            $qb->innerJoin('t.typeTest', 'tt')
                ->andWhere('tt.id = :typeTestId')
                ->setParameter('typeTestId', $typeTest->getId());
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

    /*
     * useless function, replace by $test->getTotalRequiredQuestion()
    */
    // public function getQuestionTirer($testId)
    // {
    //     $qb = $this->_em->createQueryBuilder()
    //         ->select('SUM(p.nbRequQuestions)')
    //         ->from('App:TestManagement\Test', 't')
    //         ->leftJoin('t.pools', 'p')
    //         ->where('t.id = :testId')
    //         ->setParameter('testId', $testId)
    //         ->andWhere('p.isValid = 1');
    //     return $qb->getQuery()->getSingleScalarResult();
    // }
}
