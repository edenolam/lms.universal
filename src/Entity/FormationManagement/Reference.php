<?php

namespace App\Entity\FormationManagement;

use App\Traits\DateTrait;
use App\Traits\IsValidTrait;
use App\Traits\RevisionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\FormationManagement\ReferenceRepository")
 */
class Reference
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
     * @ORM\Column(type="string", length=255)
     */
    private $author;

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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $supportIndication;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $edition;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $location;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $editor;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $publicationTitle;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $numerotation;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $normaliseNumber;

    /**
     * @ORM\Column(type="boolean" , nullable=true)
     */
    private $disponibility;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $informations;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="PageReference", mappedBy="reference", cascade={"persist"})
     * @ORM\OrderBy({"sort" = "ASC"})
     */
    protected $pageReferences;

    /**
     * @ORM\ManyToOne(targetEntity="Module", cascade={"persist"})
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="module_id", referencedColumnName="id")
     * })
     */
    protected $module;

    public function __construct()
    {
        $this->pageReferences = new ArrayCollection();
        $this->IsValid = true;
        $this->revision = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

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

    public function getSupportIndication(): ?string
    {
        return $this->supportIndication;
    }

    public function setSupportIndication(string $supportIndication): self
    {
        $this->supportIndication = $supportIndication;

        return $this;
    }

    public function getEdition(): ?string
    {
        return $this->edition;
    }

    public function setEdition(string $edition): self
    {
        $this->edition = $edition;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getEditor(): ?string
    {
        return $this->editor;
    }

    public function setEditor(string $editor): self
    {
        $this->editor = $editor;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getPublicationTitle(): ?string
    {
        return $this->publicationTitle;
    }

    public function setPublicationTitle(string $publicationTitle): self
    {
        $this->publicationTitle = $publicationTitle;

        return $this;
    }

    public function getNumerotation(): ?string
    {
        return $this->numerotation;
    }

    public function setNumerotation(string $numerotation): self
    {
        $this->numerotation = $numerotation;

        return $this;
    }

    public function getNormaliseNumber(): ?string
    {
        return $this->normaliseNumber;
    }

    public function setNormaliseNumber(string $normaliseNumber): self
    {
        $this->normaliseNumber = $normaliseNumber;

        return $this;
    }

    public function getDisponibility()
    {
        return $this->disponibility;
    }

    public function setDisponibility($disponibility)
    {
        $this->disponibility = $disponibility;

        return $this;
    }

    public function getInformations(): ?string
    {
        return $this->informations;
    }

    public function setInformations(string $informations): self
    {
        $this->informations = $informations;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return \ArrayCollection $modules
     */
    public function getPageReferences()
    {
        return $this->pageReferences;
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
    public function removePageReferences(PageReference $pageReference)
    {
        if (!$this->pageReferences->contains($pageReference)) {
            return;
        }

        $this->pageReferences->removeElement($pageReference);

        return $this;
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
    public function setModule($module)
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
