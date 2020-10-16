<?php

namespace App\Repository\LovManagement;

use App\Entity\LovManagement\TypeTest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Civility|null find($id, $lockMode = null, $lockVersion = null)
 * @method Civility|null findOneBy(array $criteria, array $orderBy = null)
 * @method Civility[] findAll()
 * @method Civility[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeTestRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeTest::class);
    }

    //fonction retournant le nombre d'utilisations d'un valeur de lov
    public function getCountUse($typeTest)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery('SELECT COUNT(T)
                FROM App\Entity\TestManagement\Test T
                WHERE T.typeTest =:typeTest')
                ->setParameter('typeTest', $typeTest)
                ->setMaxResults(1)
                ->getSingleScalarResult();

        return $query;
    }
}
