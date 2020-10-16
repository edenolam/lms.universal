<?php

namespace App\Entity\ResultManagement;

use App\Entity\PlanningManagement\Session;
use App\Entity\UserFrontManagement\UserFormationSessionFollow;
use App\Entity\UserManagement\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ResultManagement\AttestationRepository")
 */
class Attestation
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $serialCode;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $formationTitle;

    /**
     * @ORM\Column(type="datetime")
     */
    private $startDate;

    /**
     * @ORM\Column(type="datetime")
     */
    private $endDate;

    /**
     * @ORM\Column(type="array")
     */
    private $moduleDetails = [];

    /**
     * @ORM\Column(type="datetime")
     */
    private $createDate;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\UserFrontManagement\UserFormationSessionFollow", inversedBy="attestation", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $UserSessionFollow;

    /**
     * @ORM\Column(type="datetime")
     */
    private $validationDate;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $userLitteralLastname;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $userLitteralFirstName;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\PlanningManagement\Session")
     * @ORM\JoinColumn(nullable=false)
     */
    private $session;

    /**
     * @ORM\Column(type="integer")
     */
    private $ownDownload;

    /**
     * @ORM\Column(type="integer")
     */
    private $managerDownload;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\UserManagement\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $userlaboratory;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $userLabLogoUrl;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $durationSessionFormation;

    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSerialCode(): ?string
    {
        return $this->serialCode;
    }

    public function setSerialCode(string $serialCode): self
    {
        $this->serialCode = $serialCode;

        return $this;
    }

    public function getFormationTitle(): ?string
    {
        return $this->formationTitle;
    }

    public function setFormationTitle(string $formationTitle): self
    {
        $this->formationTitle = $formationTitle;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getModuleDetails(): ?array
    {
        return $this->moduleDetails;
    }

    public function setModuleDetails(array $moduleDetails): self
    {
        $this->moduleDetails = $moduleDetails;

        return $this;
    }

    public function getCreateDate(): ?\DateTimeInterface
    {
        return $this->createDate;
    }

    public function setCreateDate(\DateTimeInterface $createDate): self
    {
        $this->createDate = $createDate;

        return $this;
    }

    public function getUserSessionFollow(): ?UserFormationSessionFollow
    {
        return $this->UserSessionFollow;
    }

    public function setUserSessionFollow(UserFormationSessionFollow $UserSessionFollow): self
    {
        $this->UserSessionFollow = $UserSessionFollow;

        return $this;
    }

    public function getValidationDate(): ?\DateTimeInterface
    {
        return $this->validationDate;
    }

    public function setValidationDate(\DateTimeInterface $validationDate): self
    {
        $this->validationDate = $validationDate;

        return $this;
    }

    public function getUserLitteralLastname(): ?string
    {
        return $this->userLitteralLastname;
    }

    public function setUserLitteralLastname(string $userLitteralLastname): self
    {
        $this->userLitteralLastname = $userLitteralLastname;

        return $this;
    }

    public function getUserLitteralFirstName(): ?string
    {
        return $this->userLitteralFirstName;
    }

    public function setUserLitteralFirstName(string $userLitteralFirstName): self
    {
        $this->userLitteralFirstName = $userLitteralFirstName;

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): self
    {
        $this->session = $session;

        return $this;
    }

    public function getOwnDownload(): ?int
    {
        return $this->ownDownload;
    }

    public function setOwnDownload(int $ownDownload): self
    {
        $this->ownDownload = $ownDownload;

        return $this;
    }

    public function getManagerDownload(): ?int
    {
        return $this->managerDownload;
    }

    public function setManagerDownload(int $managerDownload): self
    {
        $this->managerDownload = $managerDownload;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUserLaboratory(): ?string
    {
        return $this->userlaboratory;
    }

    public function setUserLaboratory(string $userlaboratory): self
    {
        $this->userlaboratory = $userlaboratory;

        return $this;
    }

    public function getUserLabLogoUrl(): ?string
    {
        return $this->userLabLogoUrl;
    }

    public function setUserLabLogoUrl(string $userLabLogoUrl): self
    {
        $this->userLabLogoUrl = $userLabLogoUrl;

        return $this;
    }

    public function getDurationSessionFormation(): ?string
    {
        return $this->durationSessionFormation;
    }

    public function setDurationSessionFormation(string $durationSessionFormation): self
    {
        $this->durationSessionFormation = $durationSessionFormation;

        return $this;
    }
}
