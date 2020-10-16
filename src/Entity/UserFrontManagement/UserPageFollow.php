<?php

namespace App\Entity\UserFrontManagement;

use App\Entity\FormationManagement\Course;
use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\Page;
use App\Entity\PlanningManagement\Session;
use App\Traits\{TrackingLearningTrait};
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserFrontManagement\UserPageFollowRepository")
 */
class UserPageFollow
{
    use TrackingLearningTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    //==================================Info utiles===========================

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FormationManagement\Page")
     * @ORM\JoinColumn(nullable=false)
     */
    private $page;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $refPage;

    //================================== Info navigation ===========================

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\PlanningManagement\Session")
     * @ORM\JoinColumn(nullable=false)
     */
    private $session;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FormationManagement\Module")
     * @ORM\JoinColumn(nullable=false)
     */
    private $module;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FormationManagement\Course")
     * @ORM\JoinColumn(nullable=false)
     */
    private $course;

    /**
     * @ORM\Column(type="text",nullable=true)
     */
    private $note;

    public function __construct()
    {
        $this->durationTotalSec= 0;
        $this->durationLastSessionSec = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(?Page $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function getRefPage(): ?string
    {
        return $this->refPage;
    }

    public function setRefPage(string $refPage): self
    {
        $this->refPage = $refPage;

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

    public function getModule(): ?Module
    {
        return $this->module;
    }

    public function setModule(?Module $module): self
    {
        $this->module = $module;

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    public function getNote()
    {
        return $this->note;
    }

    public function setNote($note)
    {
        $this->note = $note;
    }
}
