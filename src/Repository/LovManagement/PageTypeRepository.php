<?php

namespace App\Repository\LovManagement;

use App\Entity\LovManagement\PageType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method PageType|null find($id, $lockMode = null, $lockVersion = null)
 * @method PageType|null findOneBy(array $criteria, array $orderBy = null)
 * @method PageType[] findAll()
 * @method PageType[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageTypeRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, PageType::class);
    }

    //fonction retournant le nombre d'utilisations d'un valeur de lov
    public function getCountUse($pageType)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery('SELECT COUNT(P)
      FROM App\Entity\FormationManagement\Page P
      WHERE P.pageType =:pageType')
      ->setParameter('pageType', $pageType)
      ->setMaxResults(1)
      ->getSingleScalarResult();

        return $query;
    }
}
