<?php

namespace App\Entity\LovManagement;

use App\Model\LovManagement\Lov as baseLov;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LovManagement\CountryRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Country extends baseLov
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
