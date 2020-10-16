<?php

namespace App\Entity\UserManagement;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\Group as BaseGroup;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_group")
 * @UniqueEntity(fields={"name"}, message=" Ce nom de groupe est déjà utilisé ")
 * @ORM\HasLifecycleCallbacks()
 */
class Group extends BaseGroup
{
    public const TUTOR_GROUP = 3;
    public const BASIC_GROUP = 4;
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @ORM\Column(name="isValid", type="boolean", nullable=false)
     */
    private $isValid;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\UserManagement\User", mappedBy="groups")
     */
    private $users;

    public function __construct($name, $roles = [])
    {
        parent::__construct($name, $roles = []);
        $this->isValid = 1;
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }


    /**
     * Set description
     *
     * @param string $description
     * @return AnswerTypes
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function getIsValid(): ?bool
    {
        return $this->isValid;
    }

    public function setIsValid(bool $isValid): self
    {
        $this->isValid = $isValid;

        return $this;
    }

    /**
     * Add users
     *
     * @param user $user
     * @return Group
     */
    public function addUser($user)
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove user
     *
     * @param user $user
     */
    public function removeUser($user)
    {
        $this->users->removeElement($presentations);
    }

    /**
     * Get $presentations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }
}
