<?php

namespace App\Entity\FormationManagement;

use App\Entity\UserFrontManagement\UserPageFollow;
use App\Entity\UserManagement\User;
use App\Traits\AudioFileTrait;
use App\Traits\DateTrait;
use App\Traits\FileTrait;
use App\Traits\IsDeletedTrait;
use App\Traits\IsValidTrait;
use App\Traits\RevisionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\FormationManagement\PageRepository")
 * @Vich\Uploadable
 */
class Page
{
    use RevisionTrait, DateTrait, FileTrait, AudioFileTrait, IsValidTrait, IsDeletedTrait;

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
     * @var bool
     *
     * @ORM\Column(name="in_summary", type="boolean", nullable=true)
     */
    protected $inSummary = true;
    /**
     * @var text
     *
     * @ORM\Column(name="sub_title", type="text", nullable=true)
     */
    protected $subTitle;
    /**
     * @ORM\Column(name="sort", type="integer", nullable=false)
     */
    protected $sort;
    /**
     * @var text
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;
    /**
     * @var text
     *
     * @ORM\Column(name="content_code", type="text", nullable=true)
     */
    protected $contentCode;
    /**
     * @var text
     *
     * @ORM\Column(name="js_code", type="text", nullable=true)
     */
    protected $jsCode;
    /**
     * @var text
     *
     * @ORM\Column(name="textual_content", type="text", nullable=true)
     */
    protected $textualContent;
    /**
     * @var text
     *
     * @ORM\Column(name="author_last_name", type="text", nullable=false)
     */
    protected $authorLastName;
    /**
     * @var text
     *
     * @ORM\Column(name="author_first_name", type="text", nullable=false)
     */
    protected $authorFirstName;
    /**
     * @var array
     *
     * @ORM\Column(name="right_fields", type="array", nullable=true)
     */
    protected $rightFields;
    /**
     * @var array
     *
     * @ORM\Column(name="left_fields", type="array", nullable=true)
     */
    protected $leftFields;
    /**
     * @ORM\ManyToOne(targetEntity="Course", cascade={"persist"})
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="course_id", referencedColumnName="id")
     * })
     */
    protected $course;
    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="PageReference", mappedBy="page", cascade={"persist"})
     * @ORM\OrderBy({"sort" = "ASC"})
     */
    protected $pageReferences;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LovManagement\PageType")
     * @ORM\JoinColumn(name="page_type_id", referencedColumnName="id")
     */
    protected $pageType;
    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Knowledge", inversedBy="pages", cascade={"persist"})
     * @ORM\JoinTable(name="page_knowledge",
     *  joinColumns={
     *      @ORM\JoinColumn(name="page_id", referencedColumnName="id")
     *  }, inverseJoinColumns={
     *      @ORM\JoinColumn(name="knowledge_id", referencedColumnName="id")
     *  }
     * )
     */
    protected $knowledges;
    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Download", inversedBy="pages", cascade={"persist"})
     * @ORM\JoinTable(name="page_download",
     *  joinColumns={
     *      @ORM\JoinColumn(name="page_id", referencedColumnName="id")
     *  }, inverseJoinColumns={
     *      @ORM\JoinColumn(name="download_id", referencedColumnName="id")
     *  }
     * )
     */
    protected $downloads;
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
    private $userPageFollow;

    /**
     * Constructor
     * Set createAt & updatedAt to current time & date
     */
    public function __construct()
    {
        $this->pageReferences = new ArrayCollection();
        $this->knowledges = new ArrayCollection();
        $this->downloads = new ArrayCollection();
        $this->IsValid = true;
        $this->InSummary = true;
        $this->revision = -1;
    }

    public function getUserPageFollow()
    {
        return $this->userPageFollow;
    }

    public function setUserPageFollow(UserPageFollow $userPageFollow)
    {
        $this->userPageFollow = $userPageFollow;
    }

    /**
     * @return $id
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
     * @return string $subTitle
     */
    public function getSubTitle()
    {
        return $this->subTitle;
    }

    /**
     * @param string $subTitle
     */
    public function setSubTitle($subTitle)
    {
        $this->subTitle = $subTitle;
    }

    /**
     * @return string $slug
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @return int $sort
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param int $sort
     */
    public function setSort($sort)
    {
        $this->sort = $sort;
    }

    /**
     * @return bool $inSummary
     */
    public function getInSummary()
    {
        return $this->inSummary;
    }

    /**
     * @param bool $inSummary
     */
    public function setInSummary($inSummary)
    {
        $this->inSummary = $inSummary;
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
     * @return string $contentCode
     */
    public function getContentCode()
    {
        return $this->contentCode;
    }

    /**
     * @param string $contentCode
     */
    public function setContentCode($contentCode)
    {
        $this->contentCode = $contentCode;
    }

    public function getJsCode()
    {
        return $this->jsCode;
    }

    public function setJsCode($jsCode)
    {
        $this->jsCode = $jsCode;
    }

    public function getTextualContent()
    {
        return $this->textualContent;
    }

    public function setTextualContent($textualContent)
    {
        $this->textualContent = $textualContent;
    }

    /**
     * @return string $authorLastName
     */
    public function getAuthorLastName()
    {
        return $this->authorLastName;
    }

    public function setAuthor(User $author)
    {
        $this->authorLastName = $author->getLastname();
        $this->authorFirstName = $author->getFirstname();
    }

    /**
     * @param string $authorLastName
     */
    public function setAuthorLastName($authorLastName)
    {
        $this->authorLastName = $authorLastName;
    }

    /**
     * @return string $authorFirstName
     */
    public function getAuthorFirstName()
    {
        return $this->authorFirstName;
    }

    /**
     * @param string $authorFirstName
     */
    public function setAuthorFirstName($authorFirstName)
    {
        $this->authorFirstName = $authorFirstName;
    }

    /**
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @param Course $course
     *                       Set course & add page to course
     */
    public function setCourse($course)
    {
        $course = $course->addPage($this);
        $this->course = $course;
    }

    /**
     * @return \ArrayCollection $modules
     */
    public function getPageReferences()
    {
        return $this->pageReferences;
    }

    /**
     * @param \ArrayCollection $modules
     * @return FormationPath
     */
    public function setPageReferences($pageReferences)
    {
        $this->pageReferences = $pageReferences;

        return $this;
    }

    /**
     * @param Module $module
     * @return FormationPath
     */
    public function addPageReference(PageReference $pageReferences)
    {
        if ($this->pageReferences->contains($pageReferences)) {
            return;
        }

        $this->pageReferences[] = $pageReferences;

        return $this;
    }

    /**
     * @param PageReferences $module
     * @return FormationPath
     */
    public function removePageReference(PageReference $pageReference)
    {
        if (!$this->pageReferences->contains($pageReference)) {
            return;
        }

        $this->pageReferences->removeElement($pageReference);

        return $this;
    }

    public function getPageType()
    {
        return $this->pageType;
    }

    public function setPageType($pageType)
    {
        $this->pageType = $pageType;
    }

    public function getRightFields()
    {
        return $this->rightFields;
    }

    public function setRightFields($rightFields)
    {
        $this->rightFields = $rightFields;
    }

    public function getLeftFields()
    {
        return $this->leftFields;
    }

    public function setLeftFields($leftFields)
    {
        $this->leftFields = $leftFields;
    }

    /**
     * @return Page
     */
    public function getKnowledges()
    {
        return $this->knowledges;
    }

    /**
     * @param Knowledge $knowledge
     */
    public function addKnowledge($knowledge)
    {
        if ($this->knowledges->contains($knowledge)) {
            return;
        }

        $this->knowledges[] = $knowledge;
        $knowledge->addPage($this);

        return;
    }

    /**
     * @param Knowledge $knowledge
     */
    public function removeKnowledge($knowledge)
    {
        if (!$this->knowledges->contains($knowledge)) {
            return;
        }

        $this->knowledges->removeElement($knowledge);
        $knowledge->removePage($this);
    }

    /**
     * @return Page
     */
    public function getDownloads()
    {
        return $this->downloads;
    }

    /**
     * @param Download $download
     */
    public function addDownload(Download $download)
    {
        if ($this->downloads->contains($download)) {
            return $this;
        }

        $this->downloads[] = $download;
        $download->addPage($this);

        return;
    }

    /**
     * @param Download $download
     */
    public function removeDownload($download)
    {
        if (!$this->downloads->contains($download)) {
            return;
        }

        $this->downloads->removeElement($download);
        $download->removePage($this);

        return;
    }

    public function checkActiveSessions()
    {
        if ($this->course->checkActiveSessions()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set createDate
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->setSort($this->getCourse()->getMaxSort());
    }

    /**
     * @ORM\PostPersist()
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        $this->setReference('UPn°' . $this->getCourse()->getId() . 'Cn°' . $this->getId());
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

    public function getMaxReferenceSort()
    {
        $maxSort = 0;
        if (count($this->getPageReferences()) !== 1) {
            foreach ($this->getPageReferences() as $pageReference) {
                if ($pageReference->getSort() >= $maxSort) {
                    $maxSort = $pageReference->getSort() + 1;
                }
            }
        } else {
            $maxSort = 1;
        }

        return $maxSort;
    }
}
