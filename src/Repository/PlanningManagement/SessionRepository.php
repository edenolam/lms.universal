<?php

namespace App\Repository\PlanningManagement;

use App\Entity\PlanningManagement\Session;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Session|null find($id, $lockMode = null, $lockVersion = null)
 * @method Session|null findOneBy(array $criteria, array $orderBy = null)
 * @method Session[] findAll()
 * @method Session[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SessionRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

    public function getTotal()
    {
        $aResultTotal = $this->getEntityManager()
            ->createQuery('SELECT COUNT(S) FROM App:PlanningManagement\Session S')
            ->setMaxResults(1)
            ->getSingleScalarResult();

        return $aResultTotal;
    }

    public function createPaginator(Query $query, int $page): Pagerfanta
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query));
        $paginator->setMaxPerPage(Session::NUM_ITEMS);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    public function findLatest(int $page = 1): Pagerfanta
    {
        $query = $this->getEntityManager()
            ->createQuery('
                SELECT s
                FROM App:PlanningManagement\Session s
                WHERE s.createDate <= :now
                ORDER BY s.createDate DESC
            ')
            ->setParameter('now', new \DateTime())
        ;

        return $this->createPaginator($query, $page);
    }

    public function filterResult($formationPath, $user, $division, $team, $year, $orderBy, $order)
    {
        $qb = $this->createQueryBuilder('s')
            ->innerJoin('s.formationPath', 'fp');
        if ($formationPath != null) {
            $qb->andWhere('fp.id = :formationPathId')
               ->setParameter('formationPathId', $formationPath->getId());
        }
        if ($user != null) {
            $qb->innerJoin('s.users', 'u')
               ->andWhere('u.id = :userId')
               ->setParameter('userId', $user->getId());
        }
        if ($division != null) {
            $qb->innerJoin('s.divisions', 'd')
               ->andWhere('d.id = :divisionId')
               ->setParameter('divisionId', $division->getId());
        }
        if ($team != null) {
            $qb->innerJoin('s.teams', 't')
               ->andWhere('t.id = :teamId')
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

    public function filterSession($formationPath, $user, $division, $team)
    {
        $qb = $this->createQueryBuilder('s');

        if ($formationPath != null) {
            $qb->innerJoin('s.formationPath', 'fp')
               ->andWhere('fp.id = :formationPathId')
               ->setParameter('formationPathId', $formationPath->getId());
        }
        if ($user != null) {
            $qb->innerJoin('s.users', 'u')
               ->andWhere('u.id = :userId')
               ->setParameter('userId', $user->getId());
        }
        if ($division != null) {
            $qb->innerJoin('s.divisions', 'd')
               ->andWhere('u.id = :divisionId')
               ->setParameter('divisionId', $division->getId());
        }
        if ($team != null) {
            $qb->innerJoin('s.teams', 't')
               ->andWhere('u.id = :teamId')
               ->setParameter('teamId', $team->getId());
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return FormationPath[] Returns an array of FormationPath objects
     *
     * @param App\Entity\UserManagement\User
     */
    public function findCurrentSession($formation = null, $user = null)
    {
        $currentSess = null;
        if (is_array($formation)) {
            $formation = $formation[0];
        }

        // No user, no formation
        if ($formation == null || $user == null) {
            return $currentSess;
        } else {
            // TODO: Warning If change ManyToMany
            // $userLaboratory = implode(',', $user->getLaboratory());
            // $userDivision = implode(',', $user->getDivision());
            // $userTeam = implode(',', $user->getTeam());
            //  $userLaboratory = $user->getLaboratory();
            //  $userDivision = $user->getDivision();
            //  $userTeam = $user->getTeam();

            // Query
            $qb = $this->_em->createQueryBuilder();
            $qb->select('s')
               ->from('App:PlanningManagement\Session', 's')
               ->leftJoin('s.formationPath', 'fp')
               ->leftJoin('s.users', 'su')
               ->leftJoin('s.teams', 'st')
               ->leftJoin('s.divisions', 'sd')
               ->andWhere('fp.id = :formationPathId')
               ->andWhere('s.isValid = 1')
               ->andWhere('fp.isValid = 1')
               ->andWhere('s.openingDate <= :start')
               ->andWhere('s.closingDate >= :end')
               ->andWhere('su.id = :user')
               //->andWhere('(su.id = :user OR st.id IN (:team) OR sd.id IN (:division))')
               ->orderBy('s.closingDate', 'ASC')
               ->setParameters(['start' => new \DateTime(),
                                      'end' => new \DateTime(),
                                      'formationPathId' => $formation->getId(),
                                      'user' => $user,
                                      /*'team' => $userTeam,
                                        'division' => $userDivision*/]);
            $currentSess = $qb->getQuery()->setMaxResults(1)->getOneOrNullResult();
        }

        return $currentSess;
    }

    /**
     * @return session[] Returns an array of session objects
     *
     * @param App\Entity\UserManagement\User
     */
    public function findOpeningSessionsByUser($user)
    {
        $qb = $this->createQueryBuilder('S')
                  ->leftJoin('S.users', 'su')
                  ->where('S.openingDate <= :start')
                  ->andWhere('S.closingDate >= :start')
                  ->andWhere('su.id = :user')
                  ->setParameters(['start' => new \DateTime(),
                      'user' => $user
                      ])
                  ->getQuery()->getResult();

        return $qb;
    }

    /**
     * users keuwords search
     * @return Page[]
     */
    public function findBySearchQuery(string $rawQuery, int $limit = Session::NUM_ITEMS): array
    {
        $query = $this->sanitizeSearchQuery($rawQuery);
        $searchTerms = $this->extractSearchTerms($query);

        if (0 === count($searchTerms)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('s')
        ->leftjoin('s.formationPath', 'fp');

        foreach ($searchTerms as $key => $term) {
            $queryBuilder
        ->orWhere('s.title LIKE :t_' . $key)
        ->orWhere('fp.title LIKE :t_' . $key)
        ->orWhere('fp.description LIKE :t_' . $key)
        ->setParameter('t_' . $key, '%' . $term . '%')
      ;
        }

        return $queryBuilder
      ->orderBy('s.createDate', 'DESC')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult()
    ;
    }

    /**
     * Removes all non-alphanumeric characters except whitespaces.
     */
    private function sanitizeSearchQuery(string $query): string
    {
        return trim(preg_replace('/[[:space:]]+/', ' ', $query));
    }

    /**
     * Splits the search query into terms and removes the ones which are irrelevant.
     */
    private function extractSearchTerms(string $searchQuery): array
    {
        $terms = array_unique(explode(' ', $searchQuery));

        return array_filter($terms, function ($term) {
            return 2 <= mb_strlen($term);
        });
    }

    public function findOpeningSession()
    {
        $qb = $this->createQueryBuilder('S')
                ->leftJoin('S.formationPath', 'fp')
                ->where('S.openingDate <= :start')
                ->andWhere('S.closingDate >= :start')
                // ->andWhere('S.id !=1')
                ->setParameter('start', new \DateTime())
                ->getQuery()->getResult();

        return $qb;
    }

    public function findlastSessionByFormationandUser($user, $slugF)
    {
        $qb = $this->createQueryBuilder('s')
          ->leftJoin('s.users', 'u')
          ->leftJoin('s.formationPath', 'f')
          ->where('u.id = :user')
          ->setParameter('user', $user->getId())
          ->andWhere('f.slug = :slugF')
          ->setParameter('slugF', $slugF)
          ->andWhere('s.openingDate <= :start')
          ->setParameter('start', new \DateTime())
          ->orderBy('s.openingDate', 'DESC');

        return $qb->getQuery()->setMaxResults(1)->getOneOrNullResult();
    }

    public function findOldSessionByFormationandUser($user, $slugF)
    {
        $qb = $this->createQueryBuilder('s')
          ->leftJoin('s.users', 'u')
          ->leftJoin('s.formationPath', 'f')
          ->where('u.id = :user')
          ->setParameter('user', $user->getId())
          ->andWhere('f.slug = :slugF')
          ->setParameter('slugF', $slugF)
          ->andWhere('s.closingDate <= :close')
          ->setParameter('close', new \DateTime())
          ->orderBy('s.closingDate', 'DESC');

        return $qb->getQuery()->setMaxResults(1)->getOneOrNullResult();
    }

    /**
     * @param App\Entity\UserManagement\User
     */
    public function findAllFormationSession($user = null)
    {
        $sessions = null;
        // No user, no formation
        if ($user == null) {
            return $sessions;
        } else {
            $userLaboratory = $user->getLaboratory();
            $userDivision = $user->getDivision();
            $userTeam = $user->getTeam();

            // Query
            $qb = $this->_em->createQueryBuilder();
            $qb->select('s')
            ->from('App:PlanningManagement\Session', 's')
            ->leftJoin('s.formationPath', 'fp')
            ->leftJoin('s.users', 'su')
            ->leftJoin('s.teams', 'st')
            ->leftJoin('s.divisions', 'sd')
            ->andWhere('s.isValid = 1')
            ->andWhere('fp.isValid = 1')
            ->andWhere('su.id = :user')
            ->orderBy('s.closingDate', 'ASC')
            ->setParameters([
                'user' => $user
                                     ]);

            return $qb->getQuery()->getResult();
        }
    }
}
