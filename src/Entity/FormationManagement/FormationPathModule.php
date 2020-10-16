<?php

namespace App\Entity\FormationManagement;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FormationManagement\FormationPathModuleRepository")
 */
class FormationPathModule
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", nullable=true)
     */
    protected $title;

    /**
     * @ORM\Column(name="sort", type="integer", nullable=true)
     */
    protected $sort;

    /**
     * @ORM\ManyToOne(targetEntity="FormationPath", inversedBy="formationPathModules", cascade={"persist"})
     * @ORM\JoinColumn(name="formation_path_id", referencedColumnName="id", nullable=true)
     */
    protected $formationPath;

    /**
     * @ORM\ManyToOne(targetEntity="Module", inversedBy="formationPathModules", cascade={"persist"})
     * @ORM\JoinColumn(name="module_id", referencedColumnName="id", nullable=true)
     */
    protected $module;

    /**
     * @return int $id
     */
    public function getId(): ?int
    {
        return $this->id;
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
     * @return int $sort
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param int $sort
     */
    public function setSort($sort)
    {
        $this->sort = $sort;
    }

    /**
     * @return \FormationPath $formationPath
     */
    public function getFormationPath()
    {
        return $this->formationPath;
    }

    /**
     * @param \FormationPath $formationPath
     */
    public function setFormationPath($formationPath)
    {
        // $formationPath = $formationPath->addFormationPathModule($this);
        $this->formationPath = $formationPath;
    }

    /**
     * @return \Module $module
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @param \Module $module
     */
    public function setModule($module)
    {
        //$module = $module->addFormationPathModule($this);
        $this->module = $module;
        // $this->title = $module->getTitle();
    }

    //CHECK IF MODULE IN PROGRESS
    public function checkActiveSessions()
    {
        if ($this->openingDate) {
            $test = true;
            $today = new \DateTime();
            if (($today->getTimestamp() > $this->openingDate->getTimestamp())
                &&
                ($today->getTimestamp() < $this->closingDate->getTimestamp())
            ) {
                $test = false;
            }
        } else {
            $test = true;
        }

        return $test;
    }
}
