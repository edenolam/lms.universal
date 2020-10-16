<?php

namespace App\Entity\FormationManagement;

use App\Entity\PlanningManagement\Session;
use App\Traits\DateTrait;
use App\Traits\FileTrait;
use App\Traits\IsValidTrait;
use App\Traits\RevisionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\FormationManagement\FormationPathRepository")
 * @Vich\Uploadable
 */
class FormationPath
{
    use RevisionTrait, DateTrait, FileTrait, IsValidTrait;

    public const NUM_ITEMS = 10;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var text
     *
     * @ORM\Column(name="title", type="text", nullable=false)
     */
    protected $title;

    /**
     * @var text
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var \Time
     *
     * @ORM\Column(name="realisation_time", type="time", nullable=false)
     */
    protected $realisationTime;

    /**
     * @Gedmo\Slug(fields={"title", "id"})
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_modules_aleatoires", type="boolean", nullable=true)
     */
    protected $isModulesAleatoires;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="FormationPathModule", mappedBy="formationPath", cascade={"persist"})
     * @ORM\OrderBy({"sort" = "ASC"})
     */
    protected $formationPathModules;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\PlanningManagement\Session", mappedBy="formationPath", cascade={"persist"})
     */
    protected $sessions;

    protected $currentSession;

    /**
     * Constructor
     * Set modules as ArrayCollection
     */
    public function __construct()
    {
        $this->formationPathModules = new ArrayCollection();
        $this->sessions = new ArrayCollection();
        $this->realisationTime = new \DateTime('01:00:00');
        $this->isModulesAleatoires = true;
        $this->isValid = true;
        $this->revision = -1;
    }

    /**
     * @return id
     */
    public function getId()
    {
        return $this->id;
    }

    public function getCurrentSession()
    {
        return $this->currentSession;
    }

    public function setCurrentSession($currentSession)
    {
        $this->currentSession = $currentSession;
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
     * @return \DateTime
     */
    public function getRealisationTime()
    {
        return $this->realisationTime;
    }

    /**
     * @param \DateTime
     */
    public function setRealisationTime($realisationTime)
    {
        $this->realisationTime = $realisationTime;
    }

    /**
     * @return string $slug
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @return \ArrayCollection $modules
     */
    public function getFormationPathModules()
    {
        return $this->formationPathModules;
    }

    /**
     * @return \ArrayCollection $modules
     */
    public function getModules()
    {
        return $this->formationPathModules;
    }

    /**
     * @param Module $module
     * @return FormationPath
     */
    public function addFormationPathModule(FormationPathModule $formationPathModule)
    {
        if ($this->formationPathModules->contains($formationPathModule)) {
            return;
        }

        $this->formationPathModules[] = $formationPathModule;

        return $this;
    }

    /**
     * @param FormationPathModules $formationPathModule
     * @return FormationPath
     */
    public function removeFormationPathModules(FormationPathModule $formationPathModule)
    {
        if ($this->formationPathModules->contains($formationPathModule)) {
            $this->formationPathModules->removeElement($formationPathModule);
            if ($formationPathModule->getFormationPath() === $this) {
                $formationPathModule->setFormationPath(null);
                $formationPathModule->setModule(null);
            }
        }

        return $this;
    }

    /**
     * @param \ArrayCollection $modules
     * @return FormationPath
     */
    public function setFormationPathModules($formationPathModules)
    {
        $this->formationPathModules = $formationPathModules;

        return $this;
    }

    public function getSessions()
    {
        return $this->sessions;
    }

    public function addSession(Session $session)
    {
        $this->sessions[] = $session;

        return $this;
    }

    public function removeSession(Session $ssession)
    {
        $this->sessions->removeElement($session);

        return $this;
    }

    public function setSessions($sessions)
    {
        $this->sessions = $sessions;

        return $this;
    }

    /**
     * @return bool $isModulesAleatoires
     */
    public function getIsModulesAleatoires()
    {
        return $this->isModulesAleatoires;
    }

    /**
     * @param bool $isModulesAleatoires
     */
    public function setIsModulesAleatoires($isModulesAleatoires)
    {
        $this->isModulesAleatoires = $isModulesAleatoires;
    }

    public function __toString()
    {
        return $this->title;
    }

    /**
     * @ORM\PreUpdate()
     */
    public function preUpdate()
    {
        $this->revision = $this->revision + 1;

        return $this;
    }

    /**
     * ======================================
     *  functions for contollers and templates
     * =====================================
     */
    public function checkActiveSessions()
    {
        $today = new \DateTime();
        foreach ($this->sessions as $session) {
            if (($session->getOpeningDate()->getTimestamp() <= $today->getTimestamp())
                &&
                ($today->getTimestamp() <= $session->getClosingDate()->getTimestamp())
            ) {
                return true;
            }
        }

        return false;
    }

    public function isActiveSession()
    {
        return $this->checkActiveSessions();
    }

    public function listModules()
    {
        $modules =array();
        foreach ($this->formationPathModules as $formationModule) {
            $modules[] = $formationModule->getModule();
        }

        return $modules;
    }

    

}
