<?php

namespace App\Entity\UserFrontManagement;

use App\Traits\DateTrait;
use App\Traits\FileTrait;
use App\Traits\IsValidTrait;
use App\Traits\RevisionTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\UserFrontManagement\UserModuleFollowFileRepository")
 * @Vich\Uploadable
 */
class UserModuleFollowFile
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
     * @ORM\ManyToOne(targetEntity="UserModuleFollow")
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="user_module_follow_id", referencedColumnName="id")
     * })
     */
    protected $userModuleFollow;

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
     * @return UserModuleFollow
     */
    public function getUserModuleFollow()
    {
        return $this->userModuleFollow;
    }

    /**
     * @param UserModuleFollow $userModuleFollow
     */
    public function setUserModuleFollow(UserModuleFollow $userModuleFollow)
    {
        $userModuleFollow = $userModuleFollow->addUserModuleFollowFile($this);
        $this->userModuleFollow = $userModuleFollow;
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
