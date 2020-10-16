<?php

namespace App\Repository\UserManagement;

use App\Entity\UserManagement\Division;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Division|null find($id, $lockMode = null, $lockVersion = null)
 * @method Division|null findOneBy(array $criteria, array $orderBy = null)
 * @method Division[] findAll()
 * @method Division[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DivisionRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, Division::class);
    }

    /**
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getUsersDiv($div)
    {
        $aResultTotal = $this->getEntityManager()
                        ->createQuery('
                            SELECT COUNT(U) 
                            FROM App:UserManagement\User U 
                            WHERE U.division = :div')
                        ->setParameter('div', $div)
                        ->getSingleScalarResult();

        return $aResultTotal;
    }

    public function findBySessionId($id)
    {
        return $this->createQueryBuilder('d')
            ->innerJoin('d.sessions', 's')
            ->where('s.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findBySupervisorDivision(array $user)
    {
        $users = [];
        foreach ($user as $us) {
            $users[] = $us->getId();
        }

        $qb = $this->createQueryBuilder('d')
            ->join('d.teams', 't')
            ->join('t.users', 'u')
            ->andWhere('u.id IN (:userId)')
            ->setParameters(['userId' => $users]);

        return $qb->getQuery()->getResult();
    }

    /* public function resultDivision($division){
         $qb = $this->createQueryBuilder('d')
             ->innerJoin('d.teams', 't')
             ->addSelect('t')
             ->leftJoin('t.users', 'u')
             ->addSelect('u')
             ->where("t.id = :division")
             ->setParameter('division', $division->getId());
         return $qb->getQuery()->getResult();
     }*/
}
