<?php

namespace App\Entity\FormationManagement;

use App\Entity\UserFrontManagement\UserCourseFollow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FormationManagement\ModuleCourseRepository")
 */
class ModuleCourse
{
    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $title;
    /**
     * @ORM\ManyToOne(targetEntity="Course", inversedBy="moduleCourses", cascade={"persist"})
     * @ORM\JoinColumn(name="course_id", referencedColumnName="id", nullable=false)
     */
    protected $course;
    /**
     * @ORM\ManyToOne(targetEntity="Module", inversedBy="moduleCourses", cascade={"persist"})
     * @ORM\JoinColumn(name="module_id", referencedColumnName="id", nullable=false)
     */
    protected $module;
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $sort;
    private $userCourseFollow;

    public function __construct()
    {
    }

    public function getUserCourseFollow()
    {
        return $this->userCourseFollow;
    }

    public function setUserCourseFollow(UserCourseFollow $userCourseFollow)
    {
        $this->userCourseFollow = $userCourseFollow;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return int $sort
     */
    public function getSort(): ?int
    {
        return $this->sort;
    }

    /**
     * @param int $sort
     */
    public function setSort(int $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * @return \Course $course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @param \Course $course
     */
    public function setCourse(Course $course)
    {
        $course = $course->addModuleCourse($this);
        $this->course = $course;
        $this->title = $course->getTitle();
    }

    /**
     * @return \Module $module
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @param \Module $module
     */
    public function setModule($module)
    {
        $module = $module->addModuleCourse($this);
        $this->module = $module;
    }
}
