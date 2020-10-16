<?php

namespace App\Entity\UserManagement;

use App\Entity\PlanningManagement\Session;
use App\Model\LovManagement\Lov as baseLov;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserManagement\DivisionRepository")
 * @ORM\Table(name="division")
 * @ORM\HasLifecycleCallbacks()
 */
class Division extends baseLov
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;

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
    protected $laboratory;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection()
     *
     * @ORM\OneToMany(targetEntity="App\Entity\UserManagement\Team", mappedBy="division", cascade={"remove"}, orphanRemoval=true)
     */
    protected $teams;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\PlanningManagement\Session", mappedBy="divisions", cascade={"persist"})
     */
    protected $sessions;

    public function __construct()
    {
        parent::__construct();
        $this->isValid = true;
        $this->revision = 0;
        $this->teams = new ArrayCollection();
        $this->sessions = new ArrayCollection();
    }

    /**
     * Get id
     */
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

    /**
     * Add team
     *
     * @param Team $team
     * @return Division
     */
    public function addTeam(Team $team)
    {
        $this->teams[] = $team;

        return $this;
    }

    /**
     * Remove team
     *
     * @param Team $team
     */
    public function removeTeam(Team $team)
    {
        $this->teams->removeElement($team);
    }

    /**
     * Get teams
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTeams()
    {
        return $this->teams;
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
     * @param Session $sessions
     *                          Add module
     */
    public function addSession($sessions)
    {
        if ($this->sessions->contains($sessions)) {
            return;
        }

        $this->sessions[] = $sessions;
        $sessions->addDivision($this);
    }

    /**
     * @param Session $sessions
     */
    public function removeSession($sessions)
    {
        if (!$this->sessions->contains($sessions)) {
            return;
        }

        $this->sessions->removeElement($sessions);
        $sessions->removeDivision($this);
    }
}
