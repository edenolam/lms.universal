<?php

namespace App\Entity\ResultManagement;

use App\Entity\UserFrontManagement\UserModuleFollow;
use App\Entity\UserManagement\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ResultManagement\CertificatRepository")
 */
class Certificat
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
     * @ORM\Column(type="string", length=255)
     */
    private $moduleTitle;

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
    private $userLitteralFisrtname;

    /**
     * @ORM\Column(type="array")
     */
    private $result = [];

    /**
     * @ORM\Column(type="array")
     */
    private $validationMode = [];

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\UserFrontManagement\UserModuleFollow", inversedBy="certificat", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $userModuleFollow;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetime", nullable=false)
     */
    protected $createDate;

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
     * @ORM\Column(type="string", length=255)
     */
    private $moduleRef;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $userLaboratory;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $userLabLogUrl;

    public function __construct()
    {
        $this->user = new ArrayCollection();
    }

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

    public function getModuleTitle(): ?string
    {
        return $this->moduleTitle;
    }

    public function setModuleTitle(string $moduleTitle): self
    {
        $this->moduleTitle = $moduleTitle;

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

    public function getUserLitteralFisrtname(): ?string
    {
        return $this->userLitteralFisrtname;
    }

    public function setUserLitteralFisrtname(string $userLitteralFisrtname): self
    {
        $this->userLitteralFisrtname = $userLitteralFisrtname;

        return $this;
    }

    public function getResult(): ?array
    {
        return $this->result;
    }

    public function setResult(array $result): self
    {
        $this->result = $result;

        return $this;
    }

    public function getValidationMode(): ?array
    {
        return $this->validationMode;
    }

    public function setValidationMode(array $validationMode): self
    {
        $this->validationMode = $validationMode;

        return $this;
    }

    public function getUserModuleFollow(): ?UserModuleFollow
    {
        return $this->userModuleFollow;
    }

    public function setUserModuleFollow(UserModuleFollow $userModuleFollow): self
    {
        $this->userModuleFollow = $userModuleFollow;

        return $this;
    }

    /**
     * Set createDate
     *
     * @ORM\PrePersist
     */
    public function setCreateDate()
    {
        $this->createDate = new \DateTime();

        return $this;
    }

    /**
     * Get createDate
     *
     * @return \DateTime
     */
    public function getCreateDate(): ? \Datetime
    {
        return $this->createDate;
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

    public function getModuleRef(): ?string
    {
        return $this->moduleRef;
    }

    public function setModuleRef(string $moduleRef): self
    {
        $this->moduleRef = $moduleRef;

        return $this;
    }

    public function getUserLaboratory(): ?string
    {
        return $this->userLaboratory;
    }

    public function setUserLaboratory(?string $userLaboratory): self
    {
        $this->userLaboratory = $userLaboratory;

        return $this;
    }

    public function getUserLabLogUrl(): ?string
    {
        return $this->userLabLogUrl;
    }

    public function setUserLabLogUrl(?string $userLabLogUrl): self
    {
        $this->userLabLogUrl = $userLabLogUrl;

        return $this;
    }
}
