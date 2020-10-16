<?php

namespace App\Entity\LovManagement;

use App\Model\LovManagement\Lov as baseLov;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LovManagement\ValidationModeRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ValidationMode extends baseLov
{
    public const PreTestValid = 'pre-test-valid';
    public const PreTestNonValid = 'pre-test-non-valid';
    public const Evaluation = 'eval';
    public const LectureComplete = 'lecture';
    public const NoValidation = 'noValidation';
    public const Presence = 'presence';
    public const EvaluationPresentielle = 'eval-presentiel';
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

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
