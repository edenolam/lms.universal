<?php

namespace App\Entity\LovManagement;

use App\Model\LovManagement\Lov as baseLov;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LovManagement\CivilityRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Civility extends baseLov
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    public function getId()
    {
        return $this->id;
    }
}
