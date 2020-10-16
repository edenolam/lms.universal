<?php

namespace App\Repository\UserManagement;

use App\Entity\UserManagement\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

/**
 * Class UserRepository
 */
class UserRepository extends ServiceEntityRepository
{
    /**
     * UserRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getTotal()
    {
        $aResultTotal = $this->getEntityManager()
            ->createQuery('SELECT COUNT(U) FROM App:UserManagement\User U')
            ->setMaxResults(1)
            ->getSingleScalarResult();

        return $aResultTotal;
    }

    public function findByTuteur($user)
    {
        $qb = $this->createQueryBuilder('u')
            ->leftjoin('u.team', 't')
            ->addSelect('t')
            ->leftjoin('t.division', 'd')
            ->addSelect('d')
            ->andWhere('u.supervisor = :user')
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
    }

    /**
     * find latest users
     *
     * @param int $page
     * @return User[]
     */
    public function findLatest(int $page = 1): Pagerfanta
    {
        $query = $this->getEntityManager()
            ->createQuery('
                SELECT U
                FROM App:UserManagement\User U
                WHERE U.createDate <= :now
                ORDER BY U.createDate DESC
            ')
            ->setParameter('now', new \DateTime());

        return $this->createPaginator($query, $page);
    }

    /**
     * @param Query $query
     * @param int $page
     * @return Pagerfanta
     */
    private function createPaginator(Query $query, int $page): Pagerfanta
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query));
        $paginator->setMaxPerPage(User::NUM_ITEMS);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    /**
     *  get the users by page
     *
     * @param type $page
     * @param type $maxperpage
     * @return array
     */
    public function findUserOrderBy(string $key, string $order, int $page, int $line)
    {
        $query = $this->getEntityManager()
            ->createQuery('
                    SELECT U 
                    FROM App:UserManagement\User U
                    ORDER BY U.' . $key . ' ' . $order)
            ->setFirstResult(($page - 1) * $line)
            ->setMaxResults($line);

        try {
            $entities = $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $entities = null;
        }

        return $entities;
    }

    /**
     *  get the users by page
     *
     * @param type $page
     * @param type $maxperpage
     * @return array
     */
    public function findUserOrderByLastName()
    {
        $query = $this->getEntityManager()
            ->createQuery('
                    SELECT U 
                    FROM App:UserManagement\User U
                    ORDER BY U.lastname ASC');

        try {
            $entities = $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $entities = null;
        }

        return $entities;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function findBySessionId($id)
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.sessions', 's')
            ->where('s.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array|\Generator
     */
    // public function findAll()
    // {
    //     $queryBuilder = $this->getEntityManager()->createQueryBuilder('u')
    //         ->select('u')
    //         ->orderBy('u.id');

    //     $limit = 1000;
    //     $offset = 0;

    //     while (true) {
    //         $queryBuilder->setFirstResult($offset);
    //         $queryBuilder->setMaxResults($limit);

    //         $interactions = $queryBuilder->getQuery()->getResult();

    //         if (count($interactions) === 0) {
    //             break;
    //         }

    //         foreach ($interactions as $interaction) {
    //             yield $interaction;
    //             $this->_em->detach($interaction);
    //         }

    //         $offset += $limit;
    //     }
    // }

    /**
     * @param string $rawQuery
     * @param int $limit
     * @return array
     */
    public function findBySearchQuery(string $rawQuery, int $limit = User::NUM_ITEMS): array
    {
        $query = $this->sanitizeSearchQuery($rawQuery);
        $searchTerms = $this->extractSearchTerms($query);

        if (0 === count($searchTerms)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('u')
            ->leftjoin('u.laboratory', 'l')
            ->leftjoin('u.division', 'd')
            ->leftjoin('u.team', 't');

        foreach ($searchTerms as $key => $term) {
            $queryBuilder
                ->orWhere('u.username LIKE :t_' . $key)
                ->orWhere('u.lastname LIKE :t_' . $key)
                ->orWhere('u.firstname LIKE :t_' . $key)
                ->orWhere('l.title LIKE :t_' . $key)
                ->orWhere('d.title LIKE :t_' . $key)
                ->orWhere('t.title LIKE :t_' . $key)
                ->setParameter('t_' . $key, '%' . $term . '%');
        }

        return $queryBuilder
            ->orderBy('u.createDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $query
     * @return string
     */
    private function sanitizeSearchQuery(string $query): string
    {
        return trim(preg_replace('/[[:space:]]+/', ' ', $query));
    }

    /**
     * @param string $searchQuery
     * @return array
     */
    private function extractSearchTerms(string $searchQuery): array
    {
        $terms = array_unique(explode(' ', $searchQuery));

        return array_filter($terms, function ($term) {
            return 2 <= mb_strlen($term);
        });
    }

    /**
     * @param $data
     * @param int $page
     * @param null $max
     * @param null $order
     * @param bool $getResult
     * @return Query|mixed
     */
    public function search($data, $page = 0, $max = null, $order = null, $getResult = true)
    {
        $qb = $this->_em->createQueryBuilder();
        $query = isset($data['query']) && $data['query'] ? $data['query'] : null;

        $qb->select('u')->from('App\UserManagement\User', 'u')
            ->leftjoin('u.laboratory', 'l')
            ->leftjoin('u.division', 'd')
            ->leftjoin('u.tream', 't');

        if ($query) {
            $qb->andWhere('u.username like :query OR u.firstname like :query OR u.lastname like :query OR l.title like :query OR d.title like :query OR t.title like :query')
                ->setParameter('query', '%' . $query . '%');
        }

        if ($order) {
            $orderBy = 'u.username';
            if ($order[0]['column'] == 1) {
                $orderBy = 'u.firstname';
            } elseif ($order[0]['column'] == 2) {
                $orderBy = 'u.lastname';
            } elseif ($order[0]['column'] == 3) {
                $orderBy = 'l.title';
            } elseif ($order[0]['column'] == 4) {
                $orderBy = 'd.title';
            } elseif ($order[0]['column'] == 5) {
                $orderBy = 't.title';
            } elseif ($order[0]['column'] == 6) {
                $orderBy = 'u.enabled';
            }

            $qb->orderBy($orderBy, $order[0]['dir']);
        }

        if ($max) {
            $preparedQuery = $qb->getQuery()
                ->setMaxResults($max)
                ->setFirstResult($page * $max);
        } else {
            $preparedQuery = $qb->getQuery();
        }

        return $getResult ? $preparedQuery->getResult() : $preparedQuery;
    }

    public function checkByFormationAndDate($user, $session)
    {
        $today = new \DateTime();
        $qb = $this->createQueryBuilder('u')
            ->innerJoin('u.sessions', 's')
            ->innerJoin('s.formationPath', 'p')
            ->where('p.id = :formationPathId')
            ->andWhere('u.id = :userId')
            ->andWhere('s.openingDate <= :start')
            ->andWhere('s.closingDate >= :end')
            ->setParameters([
                'start' => $today,
                'end' => $today,
                'userId' => $user->getId(),
                'formationPathId' => $session->getFormationPath()->getId()
            ]);
        if (count($qb->getQuery()->getResult())) {
            return true;
        } else {
            return false;
        }
    }

    public function findAllApprenant($team = null, $division = null, $lab = null, $session = null)
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.groups', 'g')
            ->where('g.id = 4');

        if ($team != null) {
            $qb->andWhere('u.team = :team')->setParameter('team', $team);
        }
        if ($division != null) {
            $qb->andWhere('u.division = :division')->setParameter('division', $division);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * findApprenantFollowBy
     *
     * @param $user
     * @param $role
     * @param null $tutorFollow
     * @return mixed
     */
    public function findApprenantFollowBy($user, $role, $tutorFollow = null)
    {
        $qb = $this->createQueryBuilder('u');
        if($role != 'autre'){
            if ($role == 'tuteur') {
    
                $tutorFollow = $this->getFollowTutors($user, $role);

                $qb->leftJoin('u.supervisors', 's')
                    ->where('s.id = :supervisor' . $user->getId() . '')
                    ->setParameter('supervisor' . $user->getId() . '', $user->getId());
                if ($tutorFollow != null) {
                    foreach ($tutorFollow as $tutor) {
                        $qb->orWhere('s.id = :supervisor' . $tutor->getId() . '')
                            ->setParameter('supervisor' . $tutor->getId() . '', $tutor->getId());
                    }
                }
                $qb->andWhere('u.hierarchyLevel < :hierarchyLevel')
                    ->setParameter('hierarchyLevel', $user->getHierarchyLevel());
            }

            $qb->andWhere('u.isValid = true')
                ->andWhere('u.enabled = true')
                ->orderBy('u.username', 'ASC');
            //var_dump($qb->getQuery()->getDQL());
            return $qb->getQuery()->getResult();
        }else{
            return Array(); 
        }
    }

    /**
     * allRespoTutorOfUser
     *
     * @param $user
     * @return mixed
     */
    public function emailsRespoTutorOfUser($user)
    {
        $dest = '';

        if ($user->getSupervisors()) {
            $qb = $this->_em->createQueryBuilder()
                ->select('u.email')
                ->from('App\Entity\UserManagement\User', 'u');
                foreach ($user->getSupervisors() as $tutor) {
                    $qb->where('u.id = :supervisor' . $tutor->getId() . '')
                        ->setParameter('supervisor' . $tutor->getId() . '', $tutor->getId());
                }
                $qb->andWhere('u.isValid = true')
                    ->andWhere('u.enabled = true');

                $dest = $qb->getQuery()->getArrayResult();
        }
        return $dest;
    }

    public function findAllByRoles($role)
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u.email')
            ->leftJoin('u.groups', 'g')
            ->where('g.roles LIKE :role')
            ->setParameter('role', '%"'.$role.'"%')
            ->andWhere('u.isValid = true')
            ->andWhere('u.enabled = true');

        return $qb->getQuery()->getResult();
    }

    /**
     * get Follow tutors
     *
     * @param $user
     * @param $role
     * @return array|null
     */
    public function getFollowTutors($user, $role)
    {
        if ($role == 'tuteur') {
            $level = $user->getHierarchyLevel() - 1;
            $tutorFollow = [];
            $tutors = null;
            for ($i = $level; $i >= 0; $i--) {
                $tutors = $this->findTutorFollowBy($tutors, $user, $level);
                foreach ($tutors as $tutor) {
                    array_push($tutorFollow, $tutor);
                }
            }

            return $tutorFollow;
        }

        return null;
    }

    /**
     * findTutorFollowBy
     *
     * @param $tutors
     * @param $user
     * @param $level
     * @return mixed
     */
    public function findTutorFollowBy($tutors, $user, $level)
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.groups', 'g')
            ->leftJoin('u.supervisors', 's')
            ->where('s.id = :supervisor' . $user->getId() . '')
            ->setParameter('supervisor' . $user->getId() . '', $user->getId());
        if ($tutors != null) {
            foreach ($tutors as $tutor) {
                $qb->orWhere('s.id = :supervisor' . $tutor->getId() . '')
                    ->setParameter('supervisor' . $tutor->getId() . '', $tutor->getId());
            }
        }
        $qb->andWhere('u.isValid = true')
            ->andWhere('u.enabled = true')
            ->andWhere('g.id = 3')
            ->andWhere('u.hierarchyLevel = :hierarchyLevel')
            ->setParameter('hierarchyLevel', $level)
            ->orderBy('u.username', 'ASC');

        return $qb->getQuery()->getResult();
    }



}
