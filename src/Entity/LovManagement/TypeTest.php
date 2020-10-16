<?php

namespace App\Entity\LovManagement;

use App\Model\LovManagement\Lov as baseLov;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LovManagement\TypeTestRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class TypeTest extends baseLov
{
    public const PreTest = 'pretest';
    public const Entrainement = 'training';
    public const Sondage = 'sondage';
    public const Evaluation = 'eval';
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
