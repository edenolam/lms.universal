<?php

namespace App\Entity\FormationManagement;

use App\Traits\UuidTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FormationManagement\ScoRepository")
 * @ORM\Table(name="scorm_sco")
 *
 * @author Free
 */
class Sco
{
    use UuidTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(
     *      targetEntity="App\Entity\FormationManagement\Scorm",
     *      inversedBy="scos",
     *      cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="scorm_id", onDelete="CASCADE", nullable=false)
     */
    protected $scorm;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="App\Entity\FormationManagement\Sco",
     *     inversedBy="scoChildren"
     * )
     * @ORM\JoinColumn(name="sco_parent_id", onDelete="CASCADE", nullable=true)
     */
    protected $scoParent;

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\FormationManagement\Sco",
     *     mappedBy="scoParent"
     * )
     */
    protected $scoChildren;

    /**
     * @ORM\Column(name="entry_url", nullable=true)
     */
    protected $entryUrl;

    /**
     * @ORM\Column(nullable=false)
     */
    protected $identifier;

    /**
     * @ORM\Column(nullable=false)
     */
    protected $title;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $visible;

    /**
     * @ORM\Column(name="sco_parameters", type="text", nullable=true)
     */
    protected $parameters;

    /**
     * @ORM\Column(name="launch_data", type="text", nullable=true)
     */
    protected $launchData;

    /**
     * @ORM\Column(name="max_time_allowed", nullable=true)
     */
    protected $maxTimeAllowed;

    /**
     * @ORM\Column(name="time_limit_action", nullable=true)
     */
    protected $timeLimitAction;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $block;

    /**
     * Score to pass for Scorm 1.2.
     *
     * @ORM\Column(name="score_int", type="integer", nullable=true)
     */
    protected $scoreToPassInt;

    /**
     * Score to pass for Scorm .
     *
     * @ORM\Column(name="score_decimal", type="decimal", precision=10, scale=7, nullable=true)
     */
    protected $scoreToPassDecimal;

    /**
     * For Scorm only.
     *
     * @ORM\Column(name="completion_threshold", type="decimal", precision=10, scale=7, nullable=true)
     */
    protected $completionThreshold;

    /**
     * @ORM\Column(name="sequencing",type="json_array", nullable=true)
     */
    protected $sequencing;

    /**
     * @ORM\Column(name="objectives",type="json_array", nullable=true)
     */
    protected $objectives;

    /**
     * For Scorm 1.2 only.
     *
     * @ORM\Column(nullable=true)
     */
    protected $prerequisites;

    public function __construct()
    {
        $this->refreshUuid();
        $this->scoChildren = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getScorm()
    {
        return $this->scorm;
    }

    public function setScorm(Scorm $scorm)
    {
        $this->scorm = $scorm;
    }

    public function getScoParent()
    {
        return $this->scoParent;
    }

    public function setScoParent(Sco $scoParent = null)
    {
        $this->scoParent = $scoParent;
    }

    public function getScoChildren()
    {
        return $this->scoChildren;
    }

    public function setScoChildren($scoChildren)
    {
        $this->scoChildren = $scoChildren;
    }

    public function getEntryUrl()
    {
        return $this->entryUrl;
    }

    public function setEntryUrl($entryUrl)
    {
        $this->entryUrl = $entryUrl;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function isVisible()
    {
        return $this->visible;
    }

    public function setVisible($visible)
    {
        $this->visible = $visible;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    public function getLaunchData()
    {
        return $this->launchData;
    }

    public function setLaunchData($launchData)
    {
        $this->launchData = $launchData;
    }

    public function getMaxTimeAllowed()
    {
        return $this->maxTimeAllowed;
    }

    public function setMaxTimeAllowed($maxTimeAllowed)
    {
        $this->maxTimeAllowed = $maxTimeAllowed;
    }

    public function getTimeLimitAction()
    {
        return $this->timeLimitAction;
    }

    public function setTimeLimitAction($timeLimitAction)
    {
        $this->timeLimitAction = $timeLimitAction;
    }

    public function isBlock()
    {
        return $this->block;
    }

    public function setBlock($block)
    {
        $this->block = $block;
    }

    public function getScoreToPass()
    {
        return $this->scoreToPassInt;
    }

    public function setScoreToPass($scoreToPass)
    {
        $this->setScoreToPassInt($scoreToPass);
    }

    public function getScoreToPassInt()
    {
        return $this->scoreToPassInt;
    }

    public function setScoreToPassInt($scoreToPassInt)
    {
        $this->scoreToPassInt = $scoreToPassInt;
    }

    public function getScoreToPassDecimal()
    {
        return $this->scoreToPassDecimal;
    }

    public function setScoreToPassDecimal($scoreToPassDecimal)
    {
        $this->scoreToPassDecimal = $scoreToPassDecimal;
    }

    public function getCompletionThreshold()
    {
        return $this->completionThreshold;
    }

    public function setCompletionThreshold($completionThreshold)
    {
        $this->completionThreshold = $completionThreshold;
    }

    public function getPrerequisites()
    {
        return $this->prerequisites;
    }

    public function setPrerequisites($prerequisites)
    {
        $this->prerequisites = $prerequisites;
    }

    public function getSequencing()
    {
        return $this->sequencing;
    }

    public function setSequencing($sequencing)
    {
        $this->sequencing = $sequencing;
    }

    public function getObjectives()
    {
        return $this->objectives;
    }

    public function setObjectives($objectives)
    {
        $this->objectives = $objectives;
    }
}
