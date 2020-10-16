<?php

namespace App\Entity\PlanningManagement;

use App\Entity\FormationManagement\FormationPath;
use App\Entity\UserManagement\Division;
use App\Entity\UserManagement\Team;
use App\Entity\UserManagement\User;
use App\Traits\DateTrait;
use App\Traits\IsValidTrait;
use App\Traits\RevisionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\PlanningManagement\SessionRepository")
 */
class Session
{
    use RevisionTrait, DateTrait, IsValidTrait;

    public const NUM_ITEMS = 10;
    public const PRE_VUE = 1;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", nullable=true)
     */
    private $id;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $openingDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $closingDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @Gedmo\Slug(fields={"title", "reference"})
     * @ORM\Column(length=128, unique=true, nullable=true)
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $reference;

    /**
     * @ORM\Column(name="session_number", type="integer", nullable=true)
     */
    protected $sessionNumber;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FormationManagement\FormationPath", cascade={"persist"}, inversedBy="sessions")
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="formation_path_id", referencedColumnName="id")
     * })
     */
    protected $formationPath;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\UserManagement\User", inversedBy="sessions")
     * @ORM\JoinTable(name="session_user",
     *  joinColumns={
     *      @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     *  }, inverseJoinColumns={
     *      @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     *  }
     * )
     */
    protected $users;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\UserManagement\Team", inversedBy="sessions")
     * @ORM\JoinTable(name="session_team",
     *  joinColumns={
     *      @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     *  }, inverseJoinColumns={
     *      @ORM\JoinColumn(name="team_id", referencedColumnName="id")
     *  }
     * )
     */
    protected $teams;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\UserManagement\Division", inversedBy="sessions")
     * @ORM\JoinTable(name="session_division",
     *  joinColumns={
     *      @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     *  }, inverseJoinColumns={
     *      @ORM\JoinColumn(name="division_id", referencedColumnName="id")
     *  }
     * )
     */
    protected $divisions;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\PlanningManagement\SessionFormationPathModule", mappedBy="session")
     */
    private $sessionFormationPathModules;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->teams = new ArrayCollection();
        $this->divisions = new ArrayCollection();
        $this->isValid = true;
        $this->revision = -1;
        $this->sessionFormationPathModules = new ArrayCollection();
        $now = new \DateTime();
        $this->openingDate = new \DateTime();
        $this->closingDate = $now->modify('+1 week');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOpeningDate(): ?\DateTimeInterface
    {
        return $this->openingDate;
    }

    public function setOpeningDate(\DateTimeInterface $openingDate): self
    {
        $this->openingDate = $openingDate;

        return $this;
    }

    public function getClosingDate(): ?\DateTimeInterface
    {
        return $this->closingDate;
    }

    public function setClosingDate(\DateTimeInterface $closingDate): self
    {
        $this->closingDate = $closingDate;

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

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function getSessionNumber()
    {
        return $this->sessionNumber;
    }

    public function setSessionNumber($sessionNumber)
    {
        $this->sessionNumber = $sessionNumber;
    }

    public function getFormationPath()
    {
        return $this->formationPath;
    }

    public function setFormationPath(FormationPath $formationPath)
    {
        $formationPath = $formationPath->addSession($this);
        $this->formationPath = $formationPath;
    }

    /**
     * @return \ArrayCollection $users
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param Users $user
     */
    public function addUser(User $user)
    {
        if ($this->users->contains($user)) {
            return;
        }

        $this->users[] = $user;
        $user->addSession($this);
    }

    /**
     * @param User $user
     */
    public function removeUser(User $user)
    {
        if (!$this->users->contains($user)) {
            return;
        }

        $this->users->removeElement($user);
        $user->removeSession($this);
    }

    /**
     * @param \ArrayCollection $users
     */
    public function setUsers($users)
    {
        $this->users = $users;

        return $this;
    }

    /**
     * @return \ArrayCollection $teams
     */
    public function getTeams()
    {
        return $this->teams;
    }

    /**
     * @param Team $team
     */
    public function addTeam(Team $team)
    {
        if ($this->teams->contains($team)) {
            return;
        }

        $this->teams[] = $team;
        $team->addSession($this);
    }

    /**
     * @param Team $team
     */
    public function removeTeam(Team $team)
    {
        if (!$this->teams->contains($team)) {
            return;
        }

        $this->teams->removeElement($team);
        $team->removeSession($this);
    }

    /**
     * @param \ArrayCollection $teams
     */
    public function setTeams($teams)
    {
        $this->teams = $teams;

        return $this;
    }

    /**
     * @return \ArrayCollection $divisions
     */
    public function getDivisions()
    {
        return $this->divisions;
    }

    /**
     * @param Division $division
     */
    public function addDivision(Division $division)
    {
        if ($this->divisions->contains($division)) {
            return;
        }

        $this->divisions[] = $division;
        $division->addSession($this);
    }

    /**
     * @param Division $division
     */
    public function removeDivision(Division $division)
    {
        if (!$this->divisions->contains($division)) {
            return;
        }

        $this->divisions->removeElement($division);
        $division->removeSession($this);
    }

    /**
     * @param \ArrayCollection $divisions
     */
    public function setDivisions($divisions)
    {
        $this->divisions = $divisions;

        return $this;
    }

    //RETURN FALSE IF SESSION IS OPEN | TRUE IF NOT OPEN
    public function checkActiveSessions()
    {
        $test = true;
        $today = new \DateTime();
        if (($today->getTimestamp() > $this->openingDate->getTimestamp())
            &&
            ($today->getTimestamp() < $this->closingDate->getTimestamp())
        ) {
            $test = false;
        }

        return $test;
    }

    /**
     * @return Collection|SessionFormationPathModule[]
     */
    public function getSessionFormationPathModules(): Collection
    {
        return $this->sessionFormationPathModules;
    }

    public function addSessionFormationPathModule(SessionFormationPathModule $sessionFormationPathModule): self
    {
        if (!$this->sessionFormationPathModules->contains($sessionFormationPathModule)) {
            $this->sessionFormationPathModules[] = $sessionFormationPathModule;
            $sessionFormationPathModule->setSession($this);
        }

        return $this;
    }

    public function removeSessionFormationPathModule(SessionFormationPathModule $sessionFormationPathModule): self
    {
        if ($this->sessionFormationPathModules->contains($sessionFormationPathModule)) {
            $this->sessionFormationPathModules->removeElement($sessionFormationPathModule);
            // set the owning side to null (unless already changed)
            if ($sessionFormationPathModule->getSession() === $this) {
                $sessionFormationPathModule->setSession(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->title;
    }    

    /**
     * @ORM\PostPersist()
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        $this->setReference('Sn°' . $this->getId());
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
