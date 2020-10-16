<?php

namespace App\Repository\FormationManagement;

use App\Entity\FormationManagement\Knowledge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Knowledge|null find($id, $lockMode = null, $lockVersion = null)
 * @method Knowledge|null findOneBy(array $criteria, array $orderBy = null)
 * @method Knowledge[] findAll()
 * @method Knowledge[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class KnowledgeRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, Knowledge::class);
    }

    public function getTotal()
    {
        $aResultTotal = $this->getEntityManager()
            ->createQuery('SELECT COUNT(K) FROM App:FormationManagement\Knowledge K')
            ->setMaxResults(1)
            ->getSingleScalarResult();

        return $aResultTotal;
    }

    public function createPaginator(Query $query, int $page): Pagerfanta
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query));
        $paginator->setMaxPerPage(Knowledge::NUM_ITEMS);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    public function findLatest(int $page = 1): Pagerfanta
    {
        $query = $this->getEntityManager()
            ->createQuery('
                SELECT k
                FROM App:FormationManagement\Knowledge k
                WHERE k.createDate <= :now
                ORDER BY k.createDate DESC
            ')
            ->setParameter('now', new \DateTime())
        ;

        return $this->createPaginator($query, $page);
    }

    public function findByCourseId($id)
    {
        return $this->createQueryBuilder('k')
            ->innerJoin('k.courses', 'c')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByQuestionId($id)
    {
        return $this->createQueryBuilder('k')
            ->innerJoin('k.questions', 'q')
            ->where('q.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * users keuwords search
     * @return Knowledge[]
     */
    public function findBySearchQuery(string $rawQuery, int $limit = Knowledge::NUM_ITEMS): array
    {
        $query = $this->sanitizeSearchQuery($rawQuery);
        $searchTerms = $this->extractSearchTerms($query);

        if (0 === count($searchTerms)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('k');

        foreach ($searchTerms as $key => $term) {
            $queryBuilder
        ->orWhere('k.title LIKE :t_' . $key)
        ->setParameter('t_' . $key, '%' . $term . '%')
      ;
        }

        return $queryBuilder
      ->orderBy('k.createDate', 'DESC')
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
}
