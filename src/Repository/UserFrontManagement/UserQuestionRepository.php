<?php

namespace App\Repository\UserFrontManagement;

use App\Entity\UserFrontManagement\UserQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method UserQuestion|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserQuestion|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserQuestion[] findAll()
 * @method UserQuestion[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserQuestionRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, UserQuestion::class);
    }

    public function findByUserANDTestANDTentative($userID, $testID, $tentative)
    {
        return $this->createQueryBuilder('uq')
            ->innerJoin('uq.user', 'u')
            ->innerJoin('uq.test', 't')
            ->where('u.id = :userID')
            ->andWhere('t.id = :testID')
            ->andWhere('uq.testTentative = :tentative')
            ->setParameter('userID', $userID)
            ->setParameter('testID', $testID)
            ->setParameter('tentative', $tentative)
            ->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?UserQuestion
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
