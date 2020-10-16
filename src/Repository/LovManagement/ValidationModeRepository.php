<?php

namespace App\Repository\LovManagement;

use App\Entity\LovManagement\ValidationMode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Civility|null find($id, $lockMode = null, $lockVersion = null)
 * @method Civility|null findOneBy(array $criteria, array $orderBy = null)
 * @method Civility[] findAll()
 * @method Civility[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ValidationModeRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, ValidationMode::class);
    }

    //fonction retournant le nombre d'utilisations d'un valeur de lov
    public function getCountUse($validationMode)
    {
        $id = $validationMode->getId();
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery('SELECT COUNT(M)
                FROM App\Entity\FormationManagement\Module M
                JOIN App\Entity\LovManagement\ValidationMode VM
                WHERE VM.id = :id')
                ->setParameter('id', $id)
                ->setMaxResults(1)
                ->getSingleScalarResult();

        return $query;
    }
}
