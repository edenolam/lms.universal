<?php

namespace App\Repository\FormationManagement;

use App\Entity\FormationManagement\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Course|null find($id, $lockMode = null, $lockVersion = null)
 * @method Course|null findOneBy(array $criteria, array $orderBy = null)
 * @method Course[] findAll()
 * @method Course[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CourseRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    public function getTotal()
    {
        $aResultTotal = $this->getEntityManager()
            ->createQuery('SELECT COUNT(C) FROM App:FormationManagement\Course C')
            ->setMaxResults(1)
            ->getSingleScalarResult();

        return $aResultTotal;
    }

    public function getTotalPageCourse($course)
    {
        $qb = $this->_em->createQueryBuilder()
                ->select('COUNT(P)')
                ->from('App:FormationManagement\Page', 'P')
                ->where('P.course = :course')
                ->setParameter('course', $course->getId())
                ->andWhere('P.isValid = 1');

        return $qb->getQuery()->setMaxResults(1)->getSingleScalarResult();
    }

    public function createPaginator(Query $query, int $page): Pagerfanta
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query));
        $paginator->setMaxPerPage(Course::NUM_ITEMS);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    public function findLatest(int $page = 1): Pagerfanta
    {
        $query = $this->getEntityManager()
            ->createQuery('
                SELECT c
                FROM App:FormationManagement\Course c
                WHERE c.createDate <= :now
                ORDER BY c.createDate DESC
            ')
            ->setParameter('now', new \DateTime())
        ;

        return $this->createPaginator($query, $page);
    }

    public function findByKnowledgeId($id)
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.knowledges', 'k')
            ->where('k.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Course[] Returns a [] Course object which the 0 index is the first of the specified module
     * @Param Module module
     */
    public function findFirstChapter($module = null)
    {
        $firstChapter = null;

        // No module, no page
        if ($module == null) {
            return $firstChapter;
        } else {
            // Query
            $qb = $this->_em->createQueryBuilder();
            $qb->select('c')
               ->from('App:FormationManagement\Course', 'c')
                ->leftJoin('c.moduleCourses', 'mc')
                ->leftJoin('c.pages', 'p')
                ->andWhere('c.isValid = 1')
                ->andWhere('mc.module = :module')
                ->having('COUNT(p) != 0')
                ->orderBy('mc.sort', 'ASC')
                ->groupBy('c')
                ->groupBy('mc.id')
               ->setParameters(['module' => $module])
               ;

            $firstChapter = $qb->getQuery()->getResult();
        }

        return $firstChapter;
    }

    /**
     * @return Course[] Returns a [] Course object which the 0 index is the first of the specified module
     * @Param Module module
     */
    public function findNextChapter($Modulecourse, $module)
    {
        $nextChapter = null;

        // No module, no page
        if ($Modulecourse == null) {
            return $nextChapter;
        } else {
            // Query
            $qb = $this->_em->createQueryBuilder();
            $qb->select('c')
                ->from('App:FormationManagement\Course', 'c')
          ->leftJoin('c.moduleCourses', 'mc')
          ->leftJoin('c.pages', 'p')
              ->andWhere('c.isValid = 1')
          ->andWhere('mc.module = :module')
          ->andWhere('mc.sort > :sort')
                ->orderBy('mc.sort', 'ASC')
                ->setParameters([
                      'module' => $module,
                      'sort' => $Modulecourse->getSort()])
         ->setMaxResults(1)
         ;

            $firstChapter = $qb->getQuery()->getOneOrNullResult();
        }

        return $firstChapter;
    }

    /**
     * @return Course[] Returns a [] Course object which the 0 index is the first of the specified module
     * @Param Module module
     */
    public function findPrevChapter($Modulecourse, $module)
    {
        $nextChapter = null;

        // No module, no page
        if ($Modulecourse == null) {
            return $nextChapter;
        } else {
            // Query
            $qb = $this->_em->createQueryBuilder();
            $qb->select('c')
                ->from('App:FormationManagement\Course', 'c')
          ->leftJoin('c.moduleCourses', 'mc')
          ->leftJoin('c.pages', 'p')
              ->andWhere('c.isValid = 1')
          ->andWhere('mc.module = :module')
          ->andWhere('mc.sort < :sort')
                ->orderBy('mc.sort', 'ASC')
                ->setParameters([
                      'module' => $module,
                      'sort' => $Modulecourse->getSort()])
          ->setMaxResults(1)
               ;

            $firstChapter = $qb->getQuery()->getOneOrNullResult();
        }

        return $firstChapter;
    }

    /**
     * users keuwords search
     * @return Course[]
     */
    public function findBySearchQuery(string $rawQuery, int $limit = Course::NUM_ITEMS): array
    {
        $query = $this->sanitizeSearchQuery($rawQuery);
        $searchTerms = $this->extractSearchTerms($query);

        if (0 === count($searchTerms)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('c')
      ->leftjoin('c.pages', 'p');

        foreach ($searchTerms as $key => $term) {
            $queryBuilder
        ->orWhere('c.title LIKE :t_' . $key)
        ->orWhere('c.description LIKE :t_' . $key)
        ->orWhere('p.title LIKE :t_' . $key)
        ->setParameter('t_' . $key, '%' . $term . '%')
      ;
        }

        return $queryBuilder
      ->orderBy('c.createDate', 'DESC')
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

    public function filterCourse($module, $knowledge)
    {
        $qb = $this->createQueryBuilder('c');
        if ($module != null) {
            $qb->innerJoin('c.moduleCourses', 'mc')
        ->leftJoin('mc.module', 'm')
        ->andWhere('m.id = :moduleId')
        ->setParameter('moduleId', $module->getId());
        }

        if ($knowledge != null) {
            $qb->innerJoin('c.knowledges', 'k')
        ->andwhere('k.id = :knowledgeId')
        ->setParameter('knowledgeId', $knowledge->getId());
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param App\Entity\UserManagement\User
     */
    public function searchCourseSession($user, $search, $page, $max)
    {
        $firstResult = $page * $max;
        $qb = $this->createQueryBuilder('c')
                  ->leftJoin('c.moduleCourses', 'mc')
                  ->leftJoin('mc.module', 'm')
                  ->leftJoin('m.sessionFormationPathModules', 'sfm')
                  ->leftJoin('sfm.session', 's')
                  ->leftJoin('s.users', 'su')
                  ->where('c.title LIKE  :search')
                  ->orWhere('c.description LIKE  :search')
                  ->andWhere('su.id = :user')
                  ->andWhere('c.isValid = 1')
                  ->setParameters(['search' => '%' . $search . '%',
                      'user' => $user->getId()
                  ])
                  ->orderBy('c.title', 'ASC')
                  ->getQuery()
                  ->setFirstResult($firstResult)
                  ->setMaxResults($max)
                  ->getResult();

        return $qb;
    }

    /**
     * @param App\Entity\UserManagement\User
     */
    public function searchCourseSessionCount($user, $search)
    {
        $qb = $this->createQueryBuilder('c')
              ->select('COUNT(c)')
              ->leftJoin('c.moduleCourses', 'mc')
              ->leftJoin('mc.module', 'm')
              ->leftJoin('m.sessionFormationPathModules', 'sfm')
              ->leftJoin('sfm.session', 's')
              ->leftJoin('s.users', 'su')
              ->where('c.title LIKE  :search')
              ->orWhere('c.description LIKE  :search')
              ->andWhere('su.id = :user')
              ->andWhere('c.isValid = 1')
              ->setParameters(['search' => '%' . $search . '%',
                  'user' => $user->getId()
              ])
              ->getQuery()->getSingleScalarResult();
        
        $sessions = $this->createQueryBuilder('c')
              ->select('COUNT(s)')
              ->leftJoin('c.moduleCourses', 'mc')
              ->leftJoin('mc.module', 'm')
              ->leftJoin('m.sessionFormationPathModules', 'sfm')
              ->leftJoin('sfm.session', 's')
              ->leftJoin('s.users', 'su')
              ->where('c.title LIKE  :search')
              ->orWhere('c.description LIKE  :search')
              ->andWhere('su.id = :user')
              ->andWhere('c.isValid = 1')
              ->setParameters(['search' => '%' . $search . '%',
                  'user' => $user->getId()
              ])
              ->getQuery()->getSingleScalarResult();

        if($sessions == 0){
                return 0;
            }else{
                return $qb/$sessions;
            }
    }
}
