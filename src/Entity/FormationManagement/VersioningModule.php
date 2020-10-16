<?php

namespace App\Entity\FormationManagement;

use App\Entity\UserManagement\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FormationManagement\VersioningModuleRepository")
 */
class VersioningModule
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(type="integer")
     */
    private $moduleVersion;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $action;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $justification;

    /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\UserManagement\User")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $actor;

    /**
     * @ORM\Column(type="boolean")
     */
    private $hasRequiredRole;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FormationManagement\Module", inversedBy="versioningModules")
     * @ORM\JoinColumn(nullable=false)
     */
    private $module;

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    /**
     * Set actor
     *
     * @param App\Entity\UserManagement\User $createUser
     */
    public function setActor(User $actor)
    {
        $this->actor = $actor;

        return $this;
    }

    /**
     * Get actor
     *
     * @return App\Entity\UserManagement\User
     */
    public function getActor()
    {
        return $this->actor;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getModuleVersion(): ?int
    {
        return $this->moduleVersion;
    }

    public function setModuleVersion(int $moduleVersion): self
    {
        $this->moduleVersion = $moduleVersion;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getJustification(): ?string
    {
        return $this->justification;
    }

    public function setJustification(?string $justification): self
    {
        $this->justification = $justification;

        return $this;
    }

    public function getHasRequiredRole(): ?bool
    {
        return $this->hasRequiredRole;
    }

    public function setHasRequiredRole(bool $hasRequiredRole): self
    {
        $this->hasRequiredRole = $hasRequiredRole;

        return $this;
    }

    public function getModule(): ?Module
    {
        return $this->module;
    }

    public function setModule(?Module $module): self
    {
        $this->module = $module;

        return $this;
    }
}
