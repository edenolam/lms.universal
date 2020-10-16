<?php

namespace App\Repository\FormationManagement;

use App\Entity\FormationManagement\Page;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Page|null find($id, $lockMode = null, $lockVersion = null)
 * @method Page|null findOneBy(array $criteria, array $orderBy = null)
 * @method Page[] findAll()
 * @method Page[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    public function getTotal()
    {
        $aResultTotal = $this->getEntityManager()
            ->createQuery('SELECT COUNT(P) FROM App:FormationManagement\Page P')
            ->setMaxResults(1)
            ->getSingleScalarResult();

        return $aResultTotal;
    }

    public function createPaginator(Query $query, int $page): Pagerfanta
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query));
        $paginator->setMaxPerPage(Page::NUM_ITEMS);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    public function findLatest(int $page = 1): Pagerfanta
    {
        $query = $this->getEntityManager()
            ->createQuery('
                SELECT p
                FROM App:FormationManagement\Page p
                WHERE p.createDate <= :now
                ORDER BY p.createDate DESC
            ')
            ->setParameter('now', new \DateTime())
        ;

        return $this->createPaginator($query, $page);
    }

    /**
     * @return Page[] Returns a [] Page object which the 0 index is the first of the specified course
     * @Param Course chapter
     */
    public function findFirstPage($chapter = null)
    {
        $firstPage = null;

        // No module, no page
        if ($chapter == null) {
            return $firstPage;
        } else {
            // Query
            $qb = $this->_em->createQueryBuilder();
            $qb->select('p')
               ->from('App:FormationManagement\Page', 'p')
               ->leftJoin('p.course', 'c')
               ->andWhere('p.isValid = 1')
               ->andWhere('p.course = :course')
               ->andWhere('c.isValid = 1')
               ->orderBy('p.sort', 'ASC')
               ->setParameters(['course' => $chapter])
               ;

            $firstPage = $qb->getQuery()->getResult();
        }

        return $firstPage;
    }

    /**
     * @return Page[] Returns a [] Page object which the last index  specified course
     * @Param Course chapter
     */
    public function findLastPage($chapter = null)
    {
        $firstPage = null;
        // No module, no page
        if ($chapter == null) {
            return $firstPage;
        } else {
            // Query
            $qb = $this->_em->createQueryBuilder();
            $qb->select('p')
               ->from('App:FormationManagement\Page', 'p')
               ->andWhere('p.isValid = 1')
               ->andWhere('p.course = :course')
               ->orderBy('p.sort', 'DESC')
               ->setParameters(['course' => $chapter])
               ;

            $firstPage = $qb->getQuery()->getResult();
        }

        return $firstPage;
    }

    /**
     * @return Page Returns a Page object which is the next of the specified course
     * @Param FormationPath formation
     * @Param Module module
     * @Param Course chapter
     * @Param Page page
     */
    public function findNextPage($formation = null, $module = null, $chapter = null, $page = null)
    {
        $nextPage = null;

        // No module, no page
        if ($page == null || $chapter == null || $module == null || $formation == null) {
            return $nextPage;
        } else {
            // Query
            $qb = $this->_em->createQueryBuilder();
            $qb->select('p')
               ->from('App:FormationManagement\Page', 'p')
               ->leftJoin('p.course', 'c')
               ->andWhere('p.isValid = 1')
               ->andWhere('p.course = :course')
               ->andWhere('c.isValid = 1')
               ->andWhere('p.sort > :sort')
               ->orderBy('p.sort', 'ASC')
               ->setParameters(['course' => $chapter,
                                     'sort' => $page->getSort()])
                ->setMaxResults(1)
               ;

            $page = $qb->getQuery()->getOneOrNullResult();

            //If empty, need to check next course
            if ($page == null) {
                if ($module->getSlug() != null) {
                    $currentModule = $this->_em->getRepository('App\Entity\FormationManagement\Module')->findBy(['slug' => $module->getSlug()]);

                    $currentModuleCourse = $this->_em->getRepository('App\Entity\FormationManagement\ModuleCourse')->findByModuleANDCourseId($currentModule[0]->getId(), $chapter->getId());

                    // $currentChapterSort = $currentModuleCourse[0]->getSort();

                    $nextChapter = $this->_em->getRepository('App\Entity\FormationManagement\Course')->findNextChapter($currentModuleCourse[0], $currentModule[0]);
                    //var_dump($nextChapter);
                    //Has next Chapter?

                    if ($nextChapter) {
                        $page = self::findFirstPage($nextChapter);
                        $nextPage = [$formation->getSlug(), $module->getSlug(), $page[0]->getCourse()->getSlug(), $page[0]->getSlug()];
                    }
                }
            } else {
                $nextPage = [$formation->getSlug(), $module->getSlug(), $chapter->getSlug(), $page->getSlug()];
            }
        }

        return $nextPage;
    }

    /**
     * @return Page Returns a Page object which is the prev of the specified course
     * @Param FormationPath formation
     * @Param Module module
     * @Param Course chapter
     * @Param Page page
     */
    public function findPrevPage($formation = null, $module = null, $chapter = null, $page = null)
    {
        $prevPage = null;

        // No module, no page
        if ($page == null || $chapter == null || $formation == null) {
            return $prevPage;
        } else {
            // Query
            $qb = $this->_em->createQueryBuilder();
            $qb->select('p')
               ->from('App:FormationManagement\Page', 'p')
               ->leftJoin('p.course', 'c')
               ->andWhere('p.isValid = 1')
               ->andWhere('p.course = :course')
               ->andWhere('p.sort < :sort')
               ->andWhere('c.isValid = 1')
               ->orderBy('p.sort', 'DESC')
               ->setParameters(['course' => $chapter,
                                     'sort' => $page->getSort()])
                                     ->setMaxResults(1)
               ;

            $page = $qb->getQuery()->getResult();

            //If empty, need to check prev course
            if ($page == null) {
                if ($module->getSlug() != null) {
                    $currentModule = $this->_em->getRepository('App\Entity\FormationManagement\Module')->findBy(['slug' => $module->getSlug()]);

                    $currentModuleCourse = $this->_em->getRepository('App\Entity\FormationManagement\ModuleCourse')->findByModuleANDCourseId($currentModule[0]->getId(), $chapter->getId());

                    //$currentChapterSort = $currentModuleCourse[0]->getSort();

                    $prevChapter = $this->_em->getRepository('App\Entity\FormationManagement\Course')->findPrevChapter($currentModuleCourse[0], $currentModule[0]);
                    //Has prev Chapter?

                    if ($prevChapter) {
                        $page = self::findLastPage($prevChapter);
                        $prevPage = [$formation->getSlug(), $module->getSlug(), $page[0]->getCourse()->getSlug(), $page[0]->getSlug()];
                    }
                }
            } else {
                $prevPage = [$formation->getSlug(), $module->getSlug(), $chapter->getSlug(), $page[0]->getSlug()];
            }
        }

        return $prevPage;
    }

    /**
     * @return Page Returns a Page object which is the prev of the specified course
     * @Param FormationPath formation
     * @Param Module module
     * @Param Course chapter
     * @Param Page page
     */
    public function findInSummaryPrevPage($formation = null, $module = null, $chapter = null, $page = null)
    {
        $prevPage = null;

        // No module, no page
        if ($page == null || $chapter == null) {
            return $prevPage;
        } else {
            // Query
            $qb = $this->_em->createQueryBuilder();
            $qb->select('p')
               ->from('App:FormationManagement\Page', 'p')
               ->leftJoin('p.course', 'c')
               ->andWhere('p.isValid = 1')
               ->andWhere('p.course = :course')
               ->andWhere('p.sort < :sort')
               ->andWhere('c.isValid = 1')
               ->orderBy('p.sort', 'DESC')
               ->setParameters(['course' => $chapter,
                                     'sort' => $page->getSort()]);

            $pages = $qb->getQuery()->getResult();

            //If empty, need to check prev course
            if ($pages) {
                foreach ($pages as $page) {
                    if ($page->getInSummary()) {
                        $prevPage = $page;
                    }
                }
            }
        }

        return $prevPage;
    }

    public function findByCourseId($id)
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.course', 'c')
            ->where('c.id = :id')
            ->orderBy('p.sort', 'ASC')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * users keuwords search
     * @return Page[]
     */
    public function findBySearchQuery(string $rawQuery, int $limit = Page::NUM_ITEMS): array
    {
        $query = $this->sanitizeSearchQuery($rawQuery);
        $searchTerms = $this->extractSearchTerms($query);

        if (0 === count($searchTerms)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('p')
        ->leftjoin('p.course', 'c');

        foreach ($searchTerms as $key => $term) {
            $queryBuilder
        ->orWhere('p.title LIKE :t_' . $key)
        ->orWhere('p.subTitle LIKE :t_' . $key)
        ->orWhere('c.title LIKE :t_' . $key)
        ->orWhere('c.reference LIKE :t_' . $key)
        ->orWhere('c.description LIKE :t_' . $key)
        ->orWhere('p.description LIKE :t_' . $key)
        ->orWhere('p.contentCode LIKE :t_' . $key)
        ->orWhere('p.authorFirstName LIKE :t_' . $key)
        ->orWhere('p.authorLastName LIKE :t_' . $key)
        ->setParameter('t_' . $key, '%' . $term . '%')
      ;
        }

        return $queryBuilder
      ->orderBy('p.createDate', 'DESC')
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

    public function filterPage($course, $pageType)
    {
        $qb = $this->createQueryBuilder('p');

        if ($course != null) {
            $qb->innerJoin('p.course', 'c')
          ->andWhere('c.id = :courseId')
          ->setParameter('courseId', $course->getId());
        }
        if ($pageType != null) {
            $qb->innerJoin('p.pageType', 'pt')
          ->andWhere('pt.id = :pageTypeId')
          ->setParameter('pageTypeId', $pageType->getId());
        }

        return $qb->getQuery()->getResult();
    }

    public function findPageModule($module)
    {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.course', 'c')
            ->innerJoin('c.moduleCourses', 'm')
            ->andWhere('m.module = :module')
            ->setParameter('module', $module);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param App\Entity\UserManagement\User
     */
    public function searchPageSession($user, $search, $page, $max)
    {
        $firstResult = intval($page) * $max;

        $qb = $this->createQueryBuilder('p')
                    ->leftJoin('p.course', 'c')
                    ->leftJoin('c.moduleCourses', 'mc')
                    ->leftJoin('mc.module', 'm')
                    ->leftJoin('m.sessionFormationPathModules', 'sfm')
                    ->leftJoin('sfm.session', 's')
                    ->leftJoin('s.users', 'su')
                    ->where('p.title LIKE  :search')
                    ->orWhere('p.description LIKE  :search')
                    ->orWhere('p.subTitle LIKE  :search')
                    ->orWhere('p.textualContent LIKE  :search')
                    ->andWhere('su.id = :user')
                    ->andWhere('p.isValid = 1')
                    ->setParameters(['search' => '%' . $search . '%',
                        'user' => $user->getId()
                    ])
                    ->orderBy('p.title', 'ASC')
                    ->getQuery()
                    ->setFirstResult($firstResult)
                    ->setMaxResults($max)
                    ->getResult();

        return $qb;
    }

    /**
     * @param App\Entity\UserManagement\User
     */
    public function searchPageSessionCount($user, $search)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p)')
            ->leftJoin('p.course', 'c')
            ->leftJoin('c.moduleCourses', 'mc')
            ->leftJoin('mc.module', 'm')
            ->leftJoin('m.sessionFormationPathModules', 'sfm')
            ->leftJoin('sfm.session', 's')
            ->leftJoin('s.users', 'su')
            ->where('p.title LIKE  :search')
            ->orWhere('p.description LIKE  :search')
            ->orWhere('p.subTitle LIKE  :search')
            ->orWhere('p.textualContent LIKE  :search')
            ->andWhere('su.id = :user')
            ->andWhere('p.isValid = 1')
            ->setParameters(['search' => '%' . $search . '%',
                'user' => $user->getId()
            ])
            ->getQuery()->getSingleScalarResult();

        $sessions = $this->createQueryBuilder('p')
            ->select('COUNT(s)')
            ->leftJoin('p.course', 'c')
            ->leftJoin('c.moduleCourses', 'mc')
            ->leftJoin('mc.module', 'm')
            ->leftJoin('m.sessionFormationPathModules', 'sfm')
            ->leftJoin('sfm.session', 's')
            ->leftJoin('s.users', 'su')
            ->where('p.title LIKE  :search')
            ->orWhere('p.description LIKE  :search')
            ->orWhere('p.subTitle LIKE  :search')
            ->orWhere('p.textualContent LIKE  :search')
            ->andWhere('su.id = :user')
            ->andWhere('p.isValid = 1')
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
