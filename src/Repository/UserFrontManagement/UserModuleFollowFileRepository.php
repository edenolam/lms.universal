<?php

namespace App\Repository\UserFrontManagement;

use App\Entity\UserFrontManagement\UserModuleFollowFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class UserModuleFollowFileRepository extends ServiceEntityRepository
{
    public function __construct(\Doctrine\Common\Persistence\ManagerRegistry $registry)
    {
        parent::__construct($registry, UserModuleFollowFile::class);
    }
}
