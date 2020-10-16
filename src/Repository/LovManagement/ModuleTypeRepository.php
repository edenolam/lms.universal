<?php

namespace App\Repository\LovManagement;

use App\Entity\LovManagement\ModuleType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class ModuleTypeRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, ModuleType::class);
    }

    public function getCountUse($moduleType)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery('SELECT COUNT(M)
                FROM App\Entity\FormationManagement\Module M
                WHERE M.type =:moduleType')
                ->setParameter('moduleType', $moduleType)
                ->setMaxResults(1)
                ->getSingleScalarResult();

        return $query;
    }
}
