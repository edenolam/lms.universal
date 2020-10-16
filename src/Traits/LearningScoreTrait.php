<?php

namespace App\Traits;

/**
 * ActorTrait
 *
 * @ORM\HasLifecycleCallbacks()
 * @author Unkown <info@universalmedica.com>
 */
trait LearningScoreTrait
{
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $score;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $success;

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getSuccess(): ?bool
    {
        return $this->success;
    }

    public function setSuccess(?bool $success): self
    {
        $this->success = $success;

        return $this;
    }
}
