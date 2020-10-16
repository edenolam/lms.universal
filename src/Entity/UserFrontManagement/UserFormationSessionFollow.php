<?php

namespace App\Entity\UserFrontManagement;

use App\Entity\FormationManagement\Course;
use App\Entity\FormationManagement\FormationPath;
use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\Page;
use App\Entity\PlanningManagement\Session;
use App\Entity\ResultManagement\Attestation;
use App\Traits\IsDeletedTrait;
use App\Traits\LearningProgressTrait;
use App\Traits\LearningScoreTrait;
use App\Traits\TrackingLearningTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserFrontManagement\UserFormationSessionFollowRepository")
 */
class UserFormationSessionFollow
{
    use TrackingLearningTrait, LearningProgressTrait, LearningScoreTrait, IsDeletedTrait;
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    //==================================Info utiles===========================

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\PlanningManagement\Session")
     * @ORM\JoinColumn(nullable=false)
     */
    private $session;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FormationManagement\FormationPath")
     * @ORM\JoinColumn(nullable=false)
     */
    private $formation;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $refSession;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $refFormation;

    //================================== Info progression ===========================

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FormationManagement\Module")
     * @ORM\JoinColumn(nullable=true)
     */
    private $lastModule;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FormationManagement\Course")
     * @ORM\JoinColumn(nullable=true)
     */
    private $lastCourse;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FormationManagement\Page")
     * @ORM\JoinColumn(nullable=true)
     */
    private $lastPage;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\ResultManagement\Attestation", mappedBy="UserSessionFollow", cascade={"persist", "remove"})
     */
    private $attestation;

    public function __construct()
    {
        $this->durationTotalSec= 0;
        $this->durationLastSessionSec = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRefFormation(): ?string
    {
        return $this->refFormation;
    }

    public function setRefFormation(?string $refFormation): self
    {
        $this->refFormation = $refFormation;

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

    public function getRefSession(): ?string
    {
        return $this->refSession;
    }

    public function setRefSession(string $refSession): self
    {
        $this->refSession = $refSession;

        return $this;
    }

    public function getFormation(): ?FormationPath
    {
        return $this->formation;
    }

    public function setFormation(?FormationPath $formation): self
    {
        $this->formation = $formation;

        return $this;
    }

    public function getLastModule(): ?Module
    {
        return $this->lastModule;
    }

    public function setLastModule(?Module $lastModule): self
    {
        $this->lastModule = $lastModule;

        return $this;
    }

    public function getLastCourse(): ?Course
    {
        return $this->lastCourse;
    }

    public function setLastCourse(?Course $lastCourse): self
    {
        $this->lastCourse = $lastCourse;

        return $this;
    }

    public function getLastPage(): ?Page
    {
        return $this->lastPage;
    }

    public function setLastPage(?Page $lastPage): self
    {
        $this->lastPage = $lastPage;

        return $this;
    }

    public function getPercentage()
    {
        return $this->percentage;
    }

    public function setPercentage($percentage)
    {
        $this->percentage = $percentage;
    }

    public function getAttestation(): ?Attestation
    {
        return $this->attestation;
    }

    public function setAttestation(Attestation $attestation): self
    {
        $this->attestation = $attestation;

        // set the owning side of the relation if necessary
        if ($this !== $attestation->getUserSessionFollow()) {
            $attestation->setUserSessionFollow($this);
        }

        return $this;
    }
}
