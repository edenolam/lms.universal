<?php

namespace App\Traits;

/**
 * ActorTrait
 *
 * @ORM\HasLifecycleCallbacks()
 * @author Unkown <info@universalmedica.com>
 */
trait LearningProgressTrait
{
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $percentage;

    public function getPercentage(): ?int
    {
        return $this->percentage;
    }

    public function setPercentage(int $percentage): self
    {
        $this->percentage = $percentage;

        return $this;
    }
}
