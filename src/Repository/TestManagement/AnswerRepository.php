<?php

namespace App\Repository\TestManagement;

use App\Entity\TestManagement\Answer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Answer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Answer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Answer[] findAll()
 * @method Answer[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnswerRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, Answer::class);
    }

    public function getTotal()
    {
        $aResultTotal = $this->getEntityManager()
            ->createQuery('SELECT COUNT(A) FROM App:TestManagement\Answer A')
            ->where('A.isValid == 1')
            ->andWhere('A.isDeleted == 0')
            ->setMaxResults(1)
            ->getSingleScalarResult();

        return $aResultTotal;
    }

    public function createPaginator(Query $query, int $page): Pagerfanta
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query));
        $paginator->setMaxPerPage(Answer::NUM_ITEMS);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    public function findLatest(int $page = 1): Pagerfanta
    {
        $query = $this->getEntityManager()
            ->createQuery('
                SELECT a
                FROM App:TestManagement\Answer a
                WHERE a.createDate <= :now
                ORDER BY a.createDate DESC
            ')
            ->setParameter('now', new \DateTime())
        ;

        return $this->createPaginator($query, $page);
    }

    public function getExpedtedNumberOfTrue($questionId)
    {
        $qb = $this->_em->createQueryBuilder()
                ->select('COUNT(A)')
                ->from('App:TestManagement\Answer', 'A')
                ->leftJoin('A.question', 'Q')
                ->Where('Q.id = :questionId')
                ->setParameter('questionId', $questionId)
                ->andWhere('A.status = :statut ')
                ->setParameter('statut', true);

        $q = $qb->getQuery()->setMaxResults(1)->getSingleScalarResult();

        return $q;
    }

//    /**
//     * @return Answer[] Returns an array of Answer objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Answer
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
