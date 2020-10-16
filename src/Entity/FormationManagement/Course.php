<?php

namespace App\Entity\FormationManagement;

use App\Entity\TestManagement\Question;
use App\Traits\DateTrait;
use App\Traits\IsDeletedTrait;
use App\Traits\IsValidTrait;
use App\Traits\RevisionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\FormationManagement\CourseRepository")
 */
class Course
{
    use RevisionTrait, DateTrait, IsValidTrait, IsDeletedTrait;

    public const NUM_ITEMS = 10;
    /**
     * @var text
     *
     * @ORM\Column(name="reference", type="text", nullable=true)
     */
    protected $reference;
    /**
     * @var text
     *
     * @ORM\Column(name="title", type="text", nullable=false)
     */
    protected $title;
    /**
     * @var text
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;
    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Page", mappedBy="course", cascade={"remove", "persist"}, orphanRemoval=true)
     * @ORM\OrderBy({"sort" = "ASC"})
     */
    protected $pages;
    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="ModuleCourse", mappedBy="course", cascade={"persist"})
     * @ORM\OrderBy({"sort" = "ASC"})
     */
    protected $moduleCourses;
    // /**
    //  * @ORM\OneToMany(targetEntity="App\Entity\TestManagement\Question", mappedBy="course")
    //  */
    // protected $questions;
    protected $file;
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @Gedmo\Slug(fields={"title", "reference"})
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;

    /**
     * Constructor
     * Set $pages as ArrayCollection
     */
    public function __construct()
    {
        $this->pages = new ArrayCollection();
        $this->moduleCourses = new ArrayCollection();
        $this->questions = new ArrayCollection();
        $this->isValid = true;
        $this->revision = -1;
    }

    /**
     * @return id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string $reference
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @param string $reference
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
    }

    /**
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string $slug
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @return string $description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return \ArrayCollection $pages
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * @param \ArrayCollection $pages
     * @return Course
     */
    public function setPages($pages)
    {
        $this->pages = $pages;

        return $this;
    }

    /**
     * @param Page $page
     * @return Course
     */
    public function addPage(Page $page)
    {
        $this->pages[] = $page;

        return $this;
    }

    /**
     * @param Page $page
     * @return Course
     */
    public function removePage(Page $page)
    {
        $this->pages->removeElement($page);

        return $this;
    }

    /**
     * @return \ArrayCollection $moduleCourses
     */
    public function getModuleCourses()
    {
        return $this->moduleCourses;
    }

    /**
     * @param \ModuleCourse $moduleCourses
     * @return Module
     */
    public function setModuleCourses($moduleCourses)
    {
        $this->moduleCourses = $moduleCourses;

        return $this;
    }

    /**
     * @param ModuleCourse $moduleCourse
     * @return Module
     */
    public function addModuleCourse(ModuleCourse $moduleCourse)
    {
        if ($this->moduleCourses->contains($moduleCourse)) {
            return;
        }

        $this->moduleCourses[] = $moduleCourse;

        return $this;
    }

    /**
     * @param ModuleCourse $moduleCourse
     * @return Module
     */
    public function removeModuleCourses(ModuleCourse $moduleCourse)
    {
        if (!$this->moduleCourses->contains($moduleCourse)) {
            return;
        }

        $this->moduleCourses->removeElement($moduleCourse);

        return $this;
    }

    // public function getQuestions()
    // {
    //     return $this->questions;
    // }

    // public function setQuestions($questions)
    // {
    //     $this->questions = $questions;
    // }

    // public function addQuestion(Question $question)
    // {
    //     $this->questions[] = $question;

    //     return $this;
    // }

    // public function removeQuestion(Question $question)
    // {
    //     $this->questions->removeElement($question);

    //     return $this;
    // }

    public function checkActiveSessions()
    {
        $test = true;
        foreach ($this->moduleCourses as $moduleCourse) {
            if (!$moduleCourse->getModule()->checkActiveSessions()) {
                $test = false;
            }
        }

        return $test;
    }

    /**
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param File $file
     *                   update $updatedAt
     */
    public function setFile(File $file = null)
    {
        $this->file = $file;

        if ($file) {
            $this->uploadAt = new \DateTime('now');
        }
    }

    /**
     * ======================================
     *  function for contoller and templates
     * =====================================
     */
    public function getMaxSort()
    {
        $maxSort = 0;
        if (count($this->getPages()) !== 1) {
            foreach ($this->getPages() as $coursePage) {
                if ($coursePage->getIsDeleted() == false) {
                    if ($coursePage->getSort() >= $maxSort) {
                        $maxSort = $coursePage->getSort() + 1;
                    }
                }
            }
        } else {
            $maxSort = 1;
        }

        return $maxSort;
    }

    /**
     * @ORM\PostPersist()
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        $this->setReference('UPn' . $this->getId());
        $em->persist($this);
        $em->flush($this);
    }

    /**
     * @ORM\PreUpdate()
     */
    public function preUpdate()
    {
        $this->revision = $this->revision + 1;

        return $this;
    }
}
