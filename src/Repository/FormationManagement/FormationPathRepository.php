<?php

namespace App\Repository\FormationManagement;

use App\Entity\FormationManagement\FormationPath;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method FormationPath|null find($id, $lockMode = null, $lockVersion = null)
 * @method FormationPath|null findOneBy(array $criteria, array $orderBy = null)
 * @method FormationPath[] findAll()
 * @method FormationPath[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FormationPathRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, FormationPath::class);
    }

    public function getTotal()
    {
        $aResultTotal = $this->getEntityManager()
        ->createQuery('SELECT COUNT(F) FROM App:FormationManagement\FormationPath F')
        ->setMaxResults(1)
        ->getSingleScalarResult();

        return $aResultTotal;
    }

    public function getTotalModuleFormation($formation)
    {
        $qb = $this->_em->createQueryBuilder()
        ->select('COUNT(M)')
        ->from('App:FormationManagement\FormationPathModule', 'M')
        ->leftjoin('M.formationPath', 'F')
        ->where('M.formationPath = :formation')
        ->setParameter('formation', $formation->getId())
        ->andWhere('F.isValid = 1');

        return $qb->getQuery()->setMaxResults(1)->getSingleScalarResult();
    }

    public function getTotalPageFormation($formation)
    {
        $em = $this->getEntityManager();
        $total = 0;
        $modules = $em->getRepository('App:FormationManagement\FormationPathModule')->findBy(['formationPath' => $formation]);
        foreach ($modules as $module) {
            $courses = $em->getRepository('App:FormationManagement\ModuleCourse')->findBy(['module' => $module]);
            foreach ($courses as $course) {
                $qb = $this->_em->createQueryBuilder()
                ->select('COUNT(P)')
                ->from('App:FormationManagement\Page', 'P')
                ->where('P.course = :course')
                ->setParameter('course', $course->getId())
                ->andWhere('P.isValid = 1');
                $total = $total + $qb->getQuery()->setMaxResults(1)->getSingleScalarResult();
            }
        }

        return $total;
    }

    public function createPaginator(Query $query, int $page): Pagerfanta
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query));
        $paginator->setMaxPerPage(FormationPath::NUM_ITEMS);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    public function findLatest(int $page = 1): Pagerfanta
    {
        $query = $this->getEntityManager()
        ->createQuery('
			SELECT f
			FROM App:FormationManagement\FormationPath f
			WHERE f.createDate <= :now
			ORDER BY f.createDate DESC
			')
        ->setParameter('now', new \DateTime())
        ;

        return $this->createPaginator($query, $page);
    }

    /**
     * users keuwords search
     * @return FormationPath[]
     */
    public function findBySearchQuery(string $rawQuery, int $limit = FormationPath::NUM_ITEMS): array
    {
        $query = $this->sanitizeSearchQuery($rawQuery);
        $searchTerms = $this->extractSearchTerms($query);

        if (0 === count($searchTerms)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('fp')
            ->leftjoin('fp.sessions', 's');

        foreach ($searchTerms as $key => $term) {
            $queryBuilder
                ->orWhere('fp.title LIKE :t_' . $key)
                ->orWhere('fp.description LIKE :t_' . $key)
                ->orWhere('s.title LIKE :t_' . $key)
                ->setParameter('t_' . $key, '%' . $term . '%')
                ;
        }

        return $queryBuilder
            ->orderBy('fp.createDate', 'DESC')
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

    /**
     * @return FormationPath[] Returns an array of FormationPath objects
     *
     * @param App\Entity\UserManagement\User
     */
    public function findAllActiveFormation($user = null)
    {
        $currentFormations = null;

        // No user, no formation
        if ($user == null) {
            return $currentFormations;
        } else {
            // TODO: Warning If change ManyToMany
            // $userLaboratory = implode(',', $user->getLaboratory());
            // $userDivision = implode(',', $user->getDivision());
            // $userTeam = implode(',', $user->getTeam());
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
            ->andWhere('s.openingDate <= :start')
            ->andWhere('s.closingDate >= :end')
            ->andWhere('su.id = :user')
               //->andWhere('(su.id = :user OR st.id IN (:team) OR sd.id IN (:division))')
            ->orderBy('s.closingDate', 'ASC')
            ->setParameters(['start' => new \DateTime(),
                'end' => new \DateTime(),
                'user' => $user
                                      /*'team' => $userTeam,
                                    'division' => $userDivision*/]);
            $currentFormations = [];
            $currentSessions = $qb->getQuery()->getResult();
            foreach ($currentSessions as $session) {
                $formation = $session->getFormationPath();
                $formation->setCurrentSession($session);
                array_push($currentFormations, $formation);
            }
            // dump($session);
        }

        return $currentFormations;
    }

    /**
     * @return bool access to the specified formation
     * @Param FormationPath  formation
     * @Param User user
     */
    public function hasFormationAccess($formation = null, $user = null)
    {
        $access = false;

        // No formation, no user?
        if ($user == null || $formation == null) {
            return $access;
        } else {
            // TODO: Warning If change ManyToMany
            // $userLaboratory = implode(',', $user->getLaboratory());
            // $userDivision = implode(',', $user->getDivision());
            // $userTeam = implode(',', $user->getTeam());
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
            ->andWhere('s.openingDate <= :start')

            ->andWhere('fp.id >= :formation')
            ->andWhere('(su.id = :user OR st.id IN (:team) OR sd.id IN (:division))')
            ->orderBy('s.closingDate', 'ASC')
            ->setParameters(['start' => new \DateTime(),
                'formation' => $formation,
                'user' => $user,
                'team' => $userTeam,
                'division' => $userDivision]);

            $resultAccess = $qb->getQuery()->getResult();
            if (!empty($resultAccess)) {
                $access = true;
            }
        }

        return $access;
    }

    /**
     * @return FormationPath[] Returns an array of FormationPath objects
     * @Param App\Entity\UserManagement\User
     */
    public function findAllFormation($user = null)
    {
        $currentFormations = null;

        // No user, no formation
        if ($user == null) {
            return $currentFormations;
        } else {
            // TODO: Warning If change ManyToMany
            // $userLaboratory = implode(',', $user->getLaboratory());
            // $userDivision = implode(',', $user->getDivision());
            // $userTeam = implode(',', $user->getTeam());
            $userLaboratory = $user->getLaboratory();
            $userDivision = $user->getDivision();
            $userTeam = $user->getTeam();

            // Query
            $qb = $this->_em->createQueryBuilder();
            $qb->select('DISTINCT(s.formationPath)')
            ->select('s')
            ->from('App:PlanningManagement\Session', 's')
            ->leftJoin('s.formationPath', 'fp')
            ->leftJoin('s.users', 'su')
            ->leftJoin('s.teams', 'st')
            ->leftJoin('s.divisions', 'sd')
            ->andWhere('s.isValid = 1')
            ->andWhere('fp.isValid = 1')
            ->andWhere('(su.id = :user OR st.id IN (:team) OR sd.id IN (:division))')
            ->setParameters([
                'user' => $user,
                'team' => $userTeam,
                'division' => $userDivision
            ]);
            /* $qb->select('DISTINCT(s.formationPath)')
             ->select('fp')
             ->from('App:FormationManagement\FormationPath', 'fp')
             ->leftJoin('fp.sessions', 's')
             ->leftJoin('s.users', 'su')
             ->leftJoin('s.teams', 'st')
             ->leftJoin('s.divisions', 'sd')
             ->andWhere('s.isValid = 1')
             ->andWhere('fp.isValid = 1')
             ->andWhere('(su.id = :user OR st.id IN (:team) OR sd.id IN (:division))')
             ->orderBy('s.closingDate', 'DESC')*/
            $currentFormations = [];
            $currentSessions = $qb->getQuery()->getResult();
            foreach ($currentSessions as $session) {
                $formation = $session->getFormationPath();
                $formation->setCurrentSession($session);
                array_push($currentFormations, $formation);
            }
            // var_dump($currentFormations);
        }

        return $currentFormations;
    }

    public function filterFormationPath($module, $session)
    {
        $qb = $this->createQueryBuilder('fp');

        if ($module != null) {
            $qb->innerJoin('fp.formationPathModules', 'fpm')
            ->leftJoin('fpm.module', 'm')
            ->andWhere('m.id = :moduleId')
            ->setParameter('moduleId', $module->getId());
        }
        if ($session != null) {
            $qb->innerJoin('fp.sessions', 's')
            ->andWhere('s.id = :sessionId')
            ->setParameter('sessionId', $session->getId());
        }

        return $qb->getQuery()->getResult();
    }

    public function filterFormation($formation, $session)
    {
        $qb = $this->createQueryBuilder('fp')
        ->innerJoin('fp.sessions', 's')
        ->where('fp.id = :formation')
        ->andWhere('s.id = :sessionId')
        ->setParameter('formation', $formation->getId())
        ->setParameter('sessionId', $session->getId());

        return $qb->getQuery()->getResult();
    }

    public function findAllPastFormation($user = null)
    {
        $currentFormations = null;

        // No user, no formation
        if ($user == null) {
            return $currentFormations;
        } else {
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
            ->andWhere('s.closingDate >= :end')
            ->andWhere('su.id = :user')
            ->orderBy('s.closingDate', 'ASC')
            ->setParameters(
                [
                    'end' => new \DateTime(),
                    'user' => $user
                ]
            );
            $currentFormations = [];
            $currentSessions = $qb->getQuery()->getResult();
            foreach ($currentSessions as $session) {
                $formation = $session->getFormationPath();
                $formation->setCurrentSession($session);
                array_push($currentFormations, $formation);
            }
            // dump($session);
        }

        return $currentFormations;
    }

    /**
     * @param App\Entity\UserManagement\User
     */
    public function searchFormationSession($user, $search, $page, $max)
    {
        $firstResult = $page * $max;
        $qb = $this->createQueryBuilder('f')
        ->leftJoin('f.sessions', 's')
        ->leftJoin('s.users', 'su')
        ->where('f.title LIKE  :search')
        ->orWhere('f.description LIKE  :search')
        ->andWhere('su.id = :user')
        ->setParameters(['search' => '%' . $search . '%',
            'user' => $user->getId()
        ])
        ->orderBy('f.title', 'ASC')
        ->getQuery()
        ->setFirstResult($firstResult)
        ->setMaxResults($max)
        ->getResult();

        return $qb;
    }

    /**
     * @param App\Entity\UserManagement\User
     */
    public function searchFormationSessionCount($user, $search)
    {
        $qb = $this->createQueryBuilder('f')
        ->select('COUNT(f)')
        ->leftJoin('f.sessions', 's')
        ->leftJoin('s.users', 'su')
        ->where('f.title LIKE  :search')
        ->orWhere('f.description LIKE  :search')
        ->andWhere('su.id = :user')
        ->setParameters(['search' => '%' . $search . '%',
            'user' => $user->getId()
        ])
        ->getQuery()->setMaxResults(1)->getSingleScalarResult();

        $sessions = $this->createQueryBuilder('f')
        ->select('COUNT(s)')
        ->leftJoin('f.sessions', 's')
        ->leftJoin('s.users', 'su')
        ->where('f.title LIKE  :search')
        ->orWhere('f.description LIKE  :search')
        ->andWhere('su.id = :user')
        ->setParameters(['search' => '%' . $search . '%',
            'user' => $user->getId()
        ])
        ->getQuery()->setMaxResults(1)->getSingleScalarResult();
        if($sessions == 0){
            return 0;
        }else{
            return $qb/$sessions;
        }
    }
}
