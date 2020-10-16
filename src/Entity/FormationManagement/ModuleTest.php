<?php

namespace App\Entity\FormationManagement;

use App\Entity\LovManagement\TypeTest;
use App\Entity\TestManagement\Test;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FormationManagement\ModuleTestRepository")
 */
class ModuleTest
{
    /**
     * @ORM\ManyToOne(targetEntity="Module", inversedBy="moduleTests", cascade={"persist"})
     * @ORM\JoinColumn(name="module_id", referencedColumnName="id", nullable=false)
     */
    protected $module;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TestManagement\Test", inversedBy="moduleTests", cascade={"persist"})
     * @ORM\JoinColumn(name="test_id", referencedColumnName="id", nullable=false)
     */
    protected $test;
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $score;
    /**
     * @ORM\Column(type="integer")
     */
    private $numberTry;
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $openingDate;
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $closingDate;
    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $chronoQuestion;
    /**
     * @ORM\Column(type="time", nullable=true)
     */
    private $chronoQuestionTime;
    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $chronoTest;
    /**
     * @ORM\Column(type="time", nullable=true)
     */
    private $chronoTestTime;
    /**
     * test entity property: title, typeTest, theoricalDuration, isTestCommune, isTestPresentiel
     */
    protected $title;
    protected $typeTest;
    protected $theoricalDuration;
    protected $isTestCommune = false;
    protected $isTestPresentiel = false;
    protected $testCommune;

    public function __construct()
    {
        $this->score = 80;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getNumberTry(): ?int
    {
        return $this->numberTry;
    }

    public function setNumberTry(int $numberTry): self
    {
        $this->numberTry = $numberTry;

        return $this;
    }

    public function getOpeningDate()
    {
        return $this->openingDate;
    }

    public function setOpeningDate($openingDate)
    {
        $this->openingDate = $openingDate;

        return $this;
    }

    public function getClosingDate()
    {
        return $this->closingDate;
    }

    public function setClosingDate($closingDate)
    {
        $this->closingDate = $closingDate;

        return $this;
    }

    public function getChronoQuestion()
    {
        return $this->chronoQuestion;
    }

    public function setChronoQuestion($chronoQuestion)
    {
        $this->chronoQuestion = $chronoQuestion;
    }

    public function getChronoTest()
    {
        return $this->chronoTest;
    }

    public function setChronoTest($chronoTest)
    {
        $this->chronoTest = $chronoTest;
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
        //$module = $module->addModuleTest($this);
        $this->module = $module;
    }

    public function getTest()
    {
        return $this->test;
    }

    public function setTest($test)
    {
        //$test = $test->addModuleTest($this);
        $this->test = $test;
        $this->setTitle($test->getTitle());
        $this->setIsTestCommune($test->getIsTestCommune());
        $this->setIsTestPresentiel($test->getIsTestPresentiel());
        $this->setTheoricalDuration($test->getTheoricalDuration());
    }

    public function getChronoTestTime()
    {
        return $this->chronoTestTime;
    }

    public function setChronoTestTime($chronoTestTime)
    {
        $this->chronoTestTime = $chronoTestTime;
    }

    public function getChronoQuestionTime()
    {
        return $this->chronoQuestionTime;
    }

    public function setChronoQuestionTime($chronoQuestionTime)
    {
        $this->chronoQuestionTime = $chronoQuestionTime;
    }

    public function getChronoQuestionTimeSeconds()
    {
        if ($this->chronoQuestionTime != null) {
            $parsed = date_parse($this->chronoQuestionTime->format('H:i:s'));

            return $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];
        } else {
            return null;
        }
    }

    public function setChronoQuestionTimeSeconds($seconds)
    {
        $this->chronoQuestionTime = new \DateTime(gmdate('H:i:s', $seconds));
    }

    /**
     * test entity data: title, typeTest, theoricalDuration, isTestCommune, isTestPresentiel
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTypeTest(): ?TypeTest
    {
        return $this->typeTest;
    }

    public function setTypeTest(?TypeTest $typeTest): self
    {
        $this->typeTest = $typeTest;

        return $this;
    }

    public function getTheoricalDuration(): ?\DateTimeInterface
    {
        return $this->theoricalDuration;
    }

    public function setTheoricalDuration(?\DateTimeInterface $theoricalDuration): self
    {
        $this->theoricalDuration = $theoricalDuration;

        return $this;
    }

    public function getIsTestCommune(): ?bool
    {
        return $this->isTestCommune;
    }

    public function setIsTestCommune(?bool $isTestCommune): self
    {
        $this->isTestCommune = $isTestCommune;

        return $this;
    }

    public function getIsTestPresentiel(): ?bool
    {
        return $this->isTestPresentiel;
    }

    public function setIsTestPresentiel(?bool $isTestPresentiel): self
    {
        $this->isTestPresentiel = $isTestPresentiel;

        return $this;
    }

    public function getTestCommune(): ?Test
    {
        return $this->testCommune;
    }

    public function setTestCommune(?Test $testCommune = null): self
    {
        $this->testCommune = $testCommune;

        return $this;
    }
}
