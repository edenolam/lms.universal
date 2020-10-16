<?php

namespace App\Repository\UserManagement;

use App\Entity\UserManagement\Laboratory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class LaboratoryRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, Laboratory::class);
    }

    /**
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getUsersLab($lab)
    {
        $aResultTotal = $this->getEntityManager()
                        ->createQuery('
                            SELECT COUNT(U) 
                            FROM App:UserManagement\User U 
                            WHERE U.laboratory = :labid')
                        ->setParameter('labid', $lab)
                        ->getSingleScalarResult();

        return $aResultTotal;
    }
}
