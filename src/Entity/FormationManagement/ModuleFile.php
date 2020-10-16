<?php

namespace App\Entity\FormationManagement;

use App\Traits\DateTrait;
use App\Traits\FileTrait;
use App\Traits\IsValidTrait;
use App\Traits\RevisionTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\FormationManagement\ModuleFileRepository")
 * @Vich\Uploadable
 */
class ModuleFile
{
    use RevisionTrait, DateTrait, FileTrait, IsValidTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var text
     *
     * @ORM\Column(name="name", type="text", nullable=false)
     */
    protected $name;

    /**
     * @Gedmo\Slug(fields={"name", "id"})
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_download", type="boolean", nullable=true)
     */
    protected $isDownload = true;

    /**
     * @ORM\ManyToOne(targetEntity="Module")
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="module_id", referencedColumnName="id")
     * })
     */
    protected $module;

    /**
     * @return id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string $slug
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @return bool $isDownload
     */
    public function getIsDownload(): ?bool
    {
        return $this->isDownload;
    }

    /**
     * @param bool $isDownload
     */
    public function setIsDownload(bool $isDownload): ?self
    {
        $this->isDownload = $isDownload;

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
     *                       Set module & add file to module
     */
    public function setModule(Module $module)
    {
        $module = $module->addModuleFile($this);
        $this->module = $module;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->IsValid = true;
        $this->isDownload = true;
        $this->revision = -1;
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
