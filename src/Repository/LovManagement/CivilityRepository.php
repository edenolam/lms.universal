<?php

namespace App\Repository\LovManagement;

use App\Entity\LovManagement\Civility;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Civility|null find($id, $lockMode = null, $lockVersion = null)
 * @method Civility|null findOneBy(array $criteria, array $orderBy = null)
 * @method Civility[] findAll()
 * @method Civility[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CivilityRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, Civility::class);
    }

    //fonction retournant le nombre d'utilisations d'un valeur de lov
    public function getCountUse($civility)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery('SELECT COUNT(C)
                FROM App\Entity\UserManagement\User C
                WHERE C.civility =:civility')
                ->setParameter('civility', $civility)
                ->setMaxResults(1)
                ->getSingleScalarResult();

        return $query;
    }
}
