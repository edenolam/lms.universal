<?php

namespace App\Repository\UserManagement;

use App\Entity\UserManagement\Team;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Team|null find($id, $lockMode = null, $lockVersion = null)
 * @method Team|null findOneBy(array $criteria, array $orderBy = null)
 * @method Team[] findAll()
 * @method Team[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TeamRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    public function findBySessionId($id)
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.sessions', 's')
            ->where('s.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findBySupervisorTeam(array $user)
    {
        $users = [];
        foreach ($user as $us) {
            $users[] = $us->getId();
        }
        $qb = $this->createQueryBuilder('t')
            ->join('t.users', 'u')
            ->andWhere('u.id IN (:userId)')
            ->setParameters(['userId' => $users]);

        return $qb->getQuery()->getResult();
    }

    /*
        public function resultTeam($team){
            $qb = $this->createQueryBuilder('t')
                ->innerJoin('t.division', 'd')
                ->addSelect('d')
                ->leftJoin('t.users', 'u')
                ->addSelect('u')
                ->where("d.id = :team")
                //->andWhere("u.id = :team")
                ->setParameter('team', $team->getId());
            return $qb->getQuery()->getResult();
        }*/
}
