<?php

namespace App\Entity\UserFrontManagement;

use App\Entity\FormationManagement\Course;
use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\Page;
use App\Entity\PlanningManagement\Session;
use App\Traits\LearningProgressTrait;
use App\Traits\TrackingLearningTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserFrontManagement\UserCourseFollowRepository")
 */
class UserCourseFollow
{
    use TrackingLearningTrait;
    use LearningProgressTrait;
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    //==================================Info utiles===========================

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FormationManagement\Course")
     * @ORM\JoinColumn(nullable=false)
     */
    private $course;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $refCourse;

    //================================== Info progression ===========================

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FormationManagement\Page")
     * @ORM\JoinColumn(nullable=true)
     */
    private $lastPage;

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

    public function __construct()
    {
        $this->durationTotalSec= 0;
        $this->durationLastSessionSec = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLastPage(): ?Page
    {
        return $this->lastPage;
    }

    public function setLastPage(Page $lastPage): self
    {
        $this->lastPage = $lastPage;

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

    public function getRefCourse(): ?string
    {
        return $this->refCourse;
    }

    public function setRefCourse(string $refCourse): self
    {
        $this->refCourse = $refCourse;

        return $this;
    }
}
