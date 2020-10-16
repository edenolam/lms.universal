<?php

namespace App\Repository\LovManagement;

use App\Entity\LovManagement\Country;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Civility|null find($id, $lockMode = null, $lockVersion = null)
 * @method Civility|null findOneBy(array $criteria, array $orderBy = null)
 * @method Civility[] findAll()
 * @method Civility[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CountryRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, Country::class);
    }

    //fonction retournant le nombre d'utilisations d'un valeur de lov
    public function getCountUse($country)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery('SELECT COUNT(C)
                FROM App\Entity\UserManagement\User C
                WHERE C.country =:country')
                ->setParameter('country', $country)
                ->setMaxResults(1)
                ->getSingleScalarResult()
                +
                $entityManager->createQuery('SELECT COUNT(L)
                FROM App\Entity\UserManagement\Laboratory L
                WHERE L.country =:country')
                ->setParameter('country', $country)
                ->setMaxResults(1)
                ->getSingleScalarResult();

        return $query;
    }
}
