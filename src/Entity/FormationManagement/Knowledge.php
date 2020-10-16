<?php

namespace App\Entity\FormationManagement;

use App\Entity\TestManagement\Question;
use App\Traits\DateTrait;
use App\Traits\IsValidTrait;
use App\Traits\RevisionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\FormationManagement\KnowledgeRepository")
 */
class Knowledge
{
    use RevisionTrait, DateTrait, IsValidTrait;

    public const NUM_ITEMS = 10;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="App\Entity\FormationManagement\Knowledge",
     * )
     * @ORM\JoinColumn(name="parent_id", onDelete="CASCADE", nullable=true)
     */
    protected $parent;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @Gedmo\Slug(fields={"title", "id"})
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;

    /**
     * @ORM\ManyToMany(targetEntity="Page", mappedBy="knowledges", cascade={"persist"})
     */
    protected $pages;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\TestManagement\Question", mappedBy="knowledges", cascade={"persist"})
     */
    protected $questions;

    /**
     * @ORM\ManyToOne(targetEntity="Module", cascade={"persist"})
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="module_id", referencedColumnName="id")
     * })
     */
    protected $module;

    public function __construct()
    {
        $this->pages = new ArrayCollection();
        $this->questions = new ArrayCollection();
        $this->IsValid = true;
        $this->revision = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent(Knowledge $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * @return string $title
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string $slug
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @return \ArrayCollection $courses
     */
    public function getQuestions()
    {
        return $this->questions;
    }

    /**
     * @param Question $course
     */
    public function addQuestion(Question $question)
    {
        if ($this->questions->contains($question)) {
            return;
        }

        $this->questions[] = $question;
        $question->addKnowledge($this);
    }

    /**
     * @param Question $question
     */
    public function removeQuestion(Question $question)
    {
        if (!$this->questions->contains($question)) {
            return;
        }

        $this->questions->removeElement($question);
        $question->removeKnowledge($this);

        return;
    }

    /**
     * @param \ArrayCollection $courses
     * @return Module
     */
    public function setQuestions($questions)
    {
        $this->questions = $questions;

        return $this;
    }

    /**
     * @param Page $page
     */
    public function addPage(page $page)
    {
        if ($this->pages->contains($page)) {
            return;
        }

        $this->pages[] = $page;
        $page->addKnowledge($this);
    }

    /**
     * @param Page $page
     */
    public function removePage(Page $page)
    {
        if (!$this->pages->contains($page)) {
            return;
        }

        $this->pages->removeElement($page);
        $page->removeKnowledge($this);

        return;
    }

    /**
     * @return \ArrayCollection $pages
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * @param \ArrayCollection $Pages
     * @return Knowledge
     */
    public function setPages($pages)
    {
        $this->pages = $pages;

        return $this;
    }

    /**
     * @return Module
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @param Module $module
     *                       Set module & add page to module
     */
    public function setModule(Module $module)
    {
        $this->module = $module;
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
