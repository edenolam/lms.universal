<?php

namespace App\Entity\UserManagement;

use App\Entity\PlanningManagement\Session;
use App\Model\LovManagement\Lov as baseLov;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserManagement\TeamRepository")
 * @ORM\Table(name="team")
 * @ORM\HasLifecycleCallbacks()*
 */
class Team extends baseLov
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Gedmo\Slug(fields={"title"})
     * @ORM\Column(length=128, unique=true)
     */
    protected $slug;

    /**
     * @var Laboratory
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\UserManagement\Laboratory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="laboratory_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $laboratory;

    /**
     * @var \Division
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\UserManagement\Division")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="division_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $division;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection()
     *
     * @ORM\OneToMany(targetEntity="App\Entity\UserManagement\User", mappedBy="team", cascade={"remove"}, orphanRemoval=true)
     */
    private $users;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\PlanningManagement\Session", mappedBy="teams", cascade={"persist"})
     */
    protected $sessions;

    public function __construct()
    {
        parent::__construct();
        $this->isValid = true;
        $this->revision = 0;
        $this->users = new ArrayCollection();
        $this->sessions = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return Laboratory
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return string $slug
     */
    public function getSlug()
    {
        return $this->slug;
    }

    public function getLaboratory(): ?Laboratory
    {
        return $this->laboratory;
    }

    public function setLaboratory(?Laboratory $laboratory): self
    {
        $this->laboratory = $laboratory;

        return $this;
    }

    public function getDivision(): ?Division
    {
        return $this->division;
    }

    public function setDivision(?Division $division): self
    {
        $this->division = $division;

        return $this;
    }

    public function __toString()
    {
        return $this->title;
    }

    /**
     * @return Session
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    /**
     * @param Session $session
     *                         Add module
     */
    public function addSession($session)
    {
        if ($this->sessions->contains($session)) {
            return;
        }

        $this->sessions[] = $session;
        $session->addTeam($this);
    }

    /**
     * @param Session $session
     */
    public function removeSession($session)
    {
        if (!$this->sessions->contains($session)) {
            return;
        }

        $this->sessions->removeElement($session);
        $session->removeTeam($this);
    }

    public function setSessions($sessions)
    {
        $this->sessions = $sessions;
    }

    /**
     * Add user
     *
     * @param User $user
     * @return Team
     */
    public function addUser(User $user)
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove user
     *
     * @param User $user
     */
    public function removeUser(Team $user)
    {
        $this->users->removeElement($user);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }
}
