<?php

namespace App\Repository\UserManagement;

use App\Entity\UserManagement\LoggedMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Empty respository
 *
 * Class LoggedMessageRepository
 */
class LoggedMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoggedMessage::class);
    }

    /**
     *  get the history by filter and by page
     *
     * @param number $page page number
     * @param number $id ticket id
     * @param number $maxperpage maxmum page
     * @param string $filter
     *
     * @return array
     */
    public function getMailHistoryByPage($page = 1, $maxperpage = 10)
    {
        $query = $this->getEntityManager()
            ->createQuery(
                'SELECT L FROM App:UserManagement\LoggedMessage L ORDER BY L.date DESC'
            )
            ->setFirstResult(($page - 1) * $maxperpage)
            ->setMaxResults($maxperpage);

        try {
            $entities = $query->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $entities = null;
        }

        return $entities;
    }

    /**
     *  get the history number total by filter
     *
     * @param string $filter
     *
     * @return number
     */
    public function getMailHistoryTotal()
    {
        $aResultTotal = $this->getEntityManager()
                ->createQuery('SELECT COUNT(L) FROM App:UserManagement\LoggedMessage L')
                ->setMaxResults(1)
                ->getSingleScalarResult();

        return $aResultTotal;
    }
}
