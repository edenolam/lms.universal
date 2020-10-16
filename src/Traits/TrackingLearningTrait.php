<?php

namespace App\Traits;

use App\Entity\UserManagement\User;

/**
 * ActorTrait
 *
 * @ORM\HasLifecycleCallbacks()
 * @author null
 */
trait TrackingLearningTrait
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\UserManagement\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $startDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $endDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $LastConnexion;

    /**
     * @ORM\Column(type="time", nullable=true)
     */
    private $durationTotal;

    /**
     * @ORM\Column(type="time", nullable=true)
     */
    private $durationLastSession;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $durationTotalSec;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $durationLastSessionSec;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getLastConnexion(): ?\DateTimeInterface
    {
        return $this->LastConnexion;
    }

    public function setLastConnexion(?\DateTimeInterface $LastConnexion): self
    {
        $this->LastConnexion = $LastConnexion;

        return $this;
    }

    public function getDurationTotal(): ?\DateTimeInterface
    {
        return $this->durationTotal;
    }

    public function setDurationTotal(?\DateTimeInterface $durationTotal): self
    {
        $this->durationTotal = $durationTotal;

        return $this;
    }

    public function getDurationLastSession(): ?\DateTimeInterface
    {
        return $this->durationLastSession;
    }

    public function setDurationLastSession(?\DateTimeInterface $durationLastSession): self
    {
        $this->durationLastSession = $durationLastSession;

        return $this;
    }

    public function getDurationTotalSec(): ?int
    {
        return $this->durationTotalSec;
    }

    public function setDurationTotalSec(?int $durationTotalSec): self
    {
        $this->durationTotalSec = $durationTotalSec;

        return $this;
    }

    public function getDurationLastSessionSec(): ?int
    {
        return $this->durationLastSessionSec;
    }

    public function setDurationLastSessionSec(?int $durationLastSessionSec): self
    {
        $this->durationLastSessionSec = $durationLastSessionSec;

        return $this;
    }
}
