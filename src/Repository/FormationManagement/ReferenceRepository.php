<?php

namespace App\Repository\FormationManagement;

use App\Entity\FormationManagement\Reference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Reference|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reference|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reference[] findAll()
 * @method Reference[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReferenceRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, Reference::class);
    }

    public function getTotal()
    {
        $aResultTotal = $this->getEntityManager()
            ->createQuery('SELECT COUNT(R) FROM App:FormationManagement\Reference R')
            ->setMaxResults(1)
            ->getSingleScalarResult();

        return $aResultTotal;
    }

    public function createPaginator(Query $query, int $page): Pagerfanta
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query));
        $paginator->setMaxPerPage(Reference::NUM_ITEMS);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    public function findLatest(int $page = 1): Pagerfanta
    {
        $query = $this->getEntityManager()
            ->createQuery('
                SELECT r
                FROM App:FormationManagement\Reference r
                WHERE r.createDate <= :now
                ORDER BY r.createDate DESC
            ')
            ->setParameter('now', new \DateTime())
        ;

        return $this->createPaginator($query, $page);
    }

    /**
     * users keuwords search
     * @return Reference[]
     */
    public function findBySearchQuery(string $rawQuery, int $limit = Reference::NUM_ITEMS): array
    {
        $query = $this->sanitizeSearchQuery($rawQuery);
        $searchTerms = $this->extractSearchTerms($query);

        if (0 === count($searchTerms)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('r');

        foreach ($searchTerms as $key => $term) {
            $queryBuilder
        ->orWhere('r.title LIKE :t_' . $key)
        ->orWhere('r.author LIKE :t_' . $key)
        ->orWhere('r.supportIndication LIKE :t_' . $key)
        ->orWhere('r.edition LIKE :t_' . $key)
        ->orWhere('r.location LIKE :t_' . $key)
        ->orWhere('r.editor LIKE :t_' . $key)
        ->orWhere('r.publicationTitle LIKE :t_' . $key)
        ->orWhere('r.informations LIKE :t_' . $key)
        ->orWhere('r.url LIKE :t_' . $key)
        ->setParameter('t_' . $key, '%' . $term . '%')
      ;
        }

        return $queryBuilder
      ->orderBy('r.createDate', 'DESC')
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
