<?php

namespace App\Repository\LovManagement;

use App\Entity\LovManagement\AnswerType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Civility|null find($id, $lockMode = null, $lockVersion = null)
 * @method Civility|null findOneBy(array $criteria, array $orderBy = null)
 * @method Civility[] findAll()
 * @method Civility[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnswerTypeRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, AnswerType::class);
    }

    //fonction retournant le nombre d'utilisations d'un valeur de lov
    public function getCountUse($answerType)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery('SELECT COUNT(Q)
                FROM App\Entity\TestManagement\Question Q
                WHERE Q.answerType =:answerType')
                ->setParameter('answerType', $answerType)
                ->setMaxResults(1)
                ->getSingleScalarResult();

        return $query;
    }
}
