<?php

namespace App\Repository\FormationManagement;

use App\Entity\FormationManagement\Module;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Module|null find($id, $lockMode = null, $lockVersion = null)
 * @method Module|null findOneBy(array $criteria, array $orderBy = null)
 * @method Module[] findAll()
 * @method Module[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ModuleRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, Module::class);
    }

//    /**
//     * @return Module[] Returns an array of Module objects
//     */

    public function getTotal()
    {
        $aResultTotal = $this->getEntityManager()
            ->createQuery('SELECT COUNT(M) FROM App:FormationManagement\Module M')
            ->setMaxResults(1)
            ->getSingleScalarResult();

        return $aResultTotal;
    }

    public function getTotalCourseModule($module)
    {
        $qb = $this->_em->createQueryBuilder()
                ->select('COUNT(MC)')
                ->from('App:FormationManagement\ModuleCourse', 'MC')
                ->leftjoin('MC.course', 'C')
                ->where('MC.module = :module')
                ->setParameter('module', $module->getId())
                ->andWhere('C.isValid = 1');

        return $qb->getQuery()->setMaxResults(1)->getSingleScalarResult();
    }

    public function getTotalPageModule($module)
    {
        $em = $this->getEntityManager();
        $total = 0;

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

        return $total;
    }

    public function createPaginator(Query $query, int $page): Pagerfanta
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query));
        $paginator->setMaxPerPage(Module::NUM_ITEMS);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    public function findLatest(int $page = 1): Pagerfanta
    {
        $query = $this->getEntityManager()
            ->createQuery('
                SELECT m
                FROM App:FormationManagement\Module m
                WHERE m.createDate <= :now
                ORDER BY m.createDate DESC
            ')
            ->setParameter('now', new \DateTime())
        ;

        return $this->createPaginator($query, $page);
    }

    public function filterModule($formationPath, $course, $validationMode)
    {
        $qb = $this->createQueryBuilder('m');
        if ($formationPath != null) {
            $qb->innerJoin('m.formationPathModules', 'fpm')
            ->leftJoin('fpm.formationPath', 'fp')
            ->andWhere('fp.id = :formationPathId')
            ->setParameter('formationPathId', $formationPath->getId());
        }
        if ($course != null) {
            $qb->innerJoin('m.moduleCourses', 'mc')
            ->leftJoin('mc.course', 'c')
            ->andWhere('c.id = :courseId')
            ->setParameter('courseId', $course->getId());
        }
        if ($validationMode != null) {
            $qb->innerJoin('m.validationModes', 'vm')
            ->andWhere('vm.id = :validationModeId')
            ->setParameter('validationModeId', $validationMode->getId());
        }

        return $qb->getQuery()->getResult();
    }

    public function findByCourseId($id)
    {
        return $this->createQueryBuilder('m')
            ->innerJoin('m.courses', 'c')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * users keuwords search
     * @return Module[]
     */
    public function findBySearchQuery(string $rawQuery, int $limit = Module::NUM_ITEMS): array
    {
        $query = $this->sanitizeSearchQuery($rawQuery);
        $searchTerms = $this->extractSearchTerms($query);

        if (0 === count($searchTerms)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('m')
        ->leftjoin('m.files', 'f');

        foreach ($searchTerms as $key => $term) {
            $queryBuilder
          ->orWhere('m.title LIKE :t_' . $key)
          ->orWhere('m.description LIKE :t_' . $key)
          ->orWhere('m.prerequisites LIKE :t_' . $key)
          ->orWhere('f.name LIKE :t_' . $key)
          ->setParameter('t_' . $key, '%' . $term . '%')
        ;
        }

        return $queryBuilder
        ->orderBy('m.createDate', 'DESC')
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

    /*
    public function findOneBySomeField($value): ?Module
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    /**
     * @return bool access to the specified module
     * @Param Module module
     * @Param User user
     */
    public function hasModuleAccess($module = null, $user)
    {
        $access = false;

        // No module, no page
        if ($module == null) {
            return $access;
        } else {
            // Query
            $qb = $this->_em->createQueryBuilder();
            $qb->select('c')
               ->from('App:FormationManagement\Course', 'c')
               ->leftJoin('c.moduleCourses', 'mc')
               ->andWhere('c.isValid = 1')
               ->andWhere('mc.module = :module')
               ->orderBy('mc.sort', 'ASC')
               ->setParameters(['module' => $module])
               ;

            $firstChapter = $qb->getQuery()->getResult();
        }
        // var_dump($firstChapter);
        return $access;
    }

    /**
     * @param App\Entity\UserManagement\User
     */
    public function searchModuleSession($user, $search, $page, $max)
    {
        $firstResult = $page * $max;
        $qb = $this->createQueryBuilder('m')
                  ->leftJoin('m.sessionFormationPathModules', 'sfm')
                  ->leftJoin('sfm.session', 's')
                  ->leftJoin('s.users', 'su')
                  ->where('m.title LIKE  :search')
                  ->orWhere('m.description LIKE  :search')
                  ->orWhere('m.regulatoryRef LIKE  :search')
                  ->andWhere('su.id = :user')
                  ->andWhere('m.isValid = 1')
                  ->setParameters(['search' => '%' . $search . '%',
                      'user' => $user->getId()
                  ])
                  ->orderBy('m.title', 'ASC')
                  ->getQuery()
                  ->setFirstResult($firstResult)
                  ->setMaxResults($max)
                  ->getResult();

        return $qb;
    }

    /**
     * @param App\Entity\UserManagement\User
     */
    public function searchModuleSessionCount($user, $search)
    {
        $qb = $this->createQueryBuilder('m')
                ->select('COUNT(m)')
                ->leftJoin('m.sessionFormationPathModules', 'sfm')
                ->leftJoin('sfm.session', 's')
                ->leftJoin('s.users', 'su')
                ->where('m.title LIKE  :search')
                ->orWhere('m.description LIKE  :search')
                ->orWhere('m.regulatoryRef LIKE  :search')
                ->andWhere('su.id = :user')
                ->andWhere('m.isValid = 1')
                ->setParameters(['search' => '%' . $search . '%',
                    'user' => $user->getId()
                ])
                ->getQuery()->getSingleScalarResult();
        $session = $this->createQueryBuilder('m')
                ->select('COUNT(s)')
                ->leftJoin('m.sessionFormationPathModules', 'sfm')
                ->leftJoin('sfm.session', 's')
                ->leftJoin('s.users', 'su')
                ->where('m.title LIKE  :search')
                ->orWhere('m.description LIKE  :search')
                ->orWhere('m.regulatoryRef LIKE  :search')
                ->andWhere('su.id = :user')
                ->andWhere('m.isValid = 1')
                ->setParameters(['search' => '%' . $search . '%',
                    'user' => $user->getId()
                ])
                ->getQuery()->getSingleScalarResult();
        if($session==0){
            return 0;
        }else{
            return $qb/$session;
        }        
    }
}
