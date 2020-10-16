<?php

namespace App\Entity\PlanningManagement;

use App\Entity\FormationManagement\Module;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PlanningManagement\SessionFormationPathModuleRepository")
 */
class SessionFormationPathModule
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", nullable=true)
     */
    private $id;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $openingDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $closingDate;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FormationManagement\Module", inversedBy="sessionFormationPathModules")
     *
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="module_id", referencedColumnName="id")
     * })
     */
    protected $module;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\PlanningManagement\Session", inversedBy="sessionFormationPathModules")
     *
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     * })
     */
    protected $session;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $openingDateEvaluation;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $closingDateEvaluation;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lieu;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $formation;


    /**
     * @ORM\OneToMany(targetEntity="App\Entity\PlanningManagement\Animateur", mappedBy="sessionFormationPathModule")
     */
    private $animateurs;


    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $codeClient;


    /**
     * @ORM\OneToMany(targetEntity="App\Entity\PlanningManagement\Signature", mappedBy="sessionFormationPathModule")
     */
    private $signatures;


    public function __construct()
    {
        $this->animateurs = new ArrayCollection();
        $this->signatures = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOpeningDate(): ?\DateTimeInterface
    {
        return $this->openingDate;
    }

    public function setOpeningDate(\DateTimeInterface $openingDate): self
    {
        $this->openingDate = $openingDate;

        return $this;
    }

    public function getClosingDate(): ?\DateTimeInterface
    {
        return $this->closingDate;
    }

    public function setClosingDate(\DateTimeInterface $closingDate): self
    {
        $this->closingDate = $closingDate;

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

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): self
    {
        $this->session = $session;

        return $this;
    }

    public function getOpeningDateEvaluation(): ?\DateTimeInterface
    {
        return $this->openingDateEvaluation;
    }

    public function setOpeningDateEvaluation(\DateTimeInterface $openingDateEvaluation): self
    {
        $this->openingDateEvaluation = $openingDateEvaluation;

        return $this;
    }

    public function getClosingDateEvaluation(): ?\DateTimeInterface
    {
        return $this->closingDateEvaluation;
    }

    public function setClosingDateEvaluation(\DateTimeInterface $closingDateEvaluation): self
    {
        $this->closingDateEvaluation = $closingDateEvaluation;

        return $this;
    }

    /**
 * @return Collection|Animateur[]
 */
    public function getAnimateurs(): Collection
    {
        return $this->animateurs;
    }

    public function addAnimateur(Animateur $animateur): self
    {
        if (!$this->animateurs->contains($animateur)) {
            $this->animateurs[] = $animateur;
            $animateur->setSessionFormationPathModule($this);
        }

        return $this;
    }

    public function removeAnimateur(Animateur $animateur): self
    {
        if ($this->animateurs->contains($animateur)) {
            $this->animateurs->removeElement($animateur);
            // set the owning side to null (unless already changed)
            if ($animateur->getSessionFormationPathModule() === $this) {
                $animateur->setSessionFormationPathModule(null);
            }
        }

        return $this;
    }


    public function getCodeClient(): ?string
    {
        return $this->codeClient;
    }

    public function setCodeClient(string $codeClient): self
    {
        $this->codeClient = $codeClient;

        return $this;
    }

    /**
     * @return Collection|Signature[]
     */
    public function getSignatures(): Collection
    {
        return $this->signatures;
    }

    public function addSignature(Signature $signature): self
    {
        if (!$this->signatures->contains($signature)) {
            $this->signatures[] = $signature;
            $signature->setSessionFormationPathModule($this);
        }

        return $this;
    }

    public function removeSignature(Signature $signature): self
    {
        if ($this->signatures->contains($signature)) {
            $this->signatures->removeElement($signature);
            // set the owning side to null (unless already changed)
            if ($signature->getSessionFormationPathModule() === $this) {
                $signature->setSessionFormationPathModule(null);
            }
        }

        return $this;
    }

}
