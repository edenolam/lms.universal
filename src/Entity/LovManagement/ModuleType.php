<?php

namespace App\Entity\LovManagement;

use App\Model\LovManagement\Lov as baseLov;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LovManagement\ModuleTypeRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ModuleType extends baseLov
{
    public const Standard = 'standard';
    public const Scorm = 'scorm';
    public const Presentiel = 'presentiel';
    public const NotFollow = 'notFollow';
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
