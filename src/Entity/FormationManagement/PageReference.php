<?php

namespace App\Entity\FormationManagement;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FormationManagement\PageReferenceRepository")
 */
class PageReference
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $title;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $sort;

    /**
     * @ORM\ManyToOne(targetEntity="Page", inversedBy="pageReferences", cascade={"persist"})
     * @ORM\JoinColumn(name="page_id", referencedColumnName="id", nullable=false)
     */
    protected $page;

    /**
     * @ORM\ManyToOne(targetEntity="Reference", inversedBy="pageReferences", cascade={"persist"})
     * @ORM\JoinColumn(name="reference_id", referencedColumnName="id", nullable=false)
     */
    protected $reference;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSort(): ?int
    {
        return $this->sort;
    }

    public function setSort(int $sort): self
    {
        $this->sort = $sort;

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
     * @return \Page $page
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param \Page $page
     */
    public function setPage(Page $page)
    {
        $page = $page->addPageReference($this);
        $this->page = $page;
    }

    /**
     * @return \Reference $reference
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @param \Reference $reference
     */
    public function setReference($reference)
    {
        $reference = $reference->addPageReference($this);
        $this->reference = $reference;
        $this->title = $reference->getTitle();
    }

    public function __construct()
    {
    }
}
