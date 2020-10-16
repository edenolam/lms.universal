<?php

namespace App\Entity\TestManagement;

use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\ModuleTest;
use App\Entity\LovManagement\TypeTest;
use App\Traits\ActorTrait;
use App\Traits\DateTrait;
use App\Traits\RevisionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\TestManagement\TestRepository")
 */
class Test
{
    use ActorTrait, RevisionTrait, DateTrait;

    public const NUM_ITEMS = 10;
    public const EVALUATION = 'eval';
    public const ENTRAINEMENT = 'training';
    public const PRE_TEST = 'pretest';
    public const SONDAGE = 'sondage';

    /**
     * @ORM\ManyToOne(
     *     targetEntity="App\Entity\TestManagement\Test",
     * )
     * @ORM\JoinColumn(name="parent_id", onDelete="CASCADE", nullable=true)
     */
    protected $parent;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\FormationManagement\ModuleTest", mappedBy="test", cascade={"persist"})
     */
    protected $moduleTests;
    /**
     * @var bool
     *
     * @ORM\Column(name="is_test_commune", type="boolean", nullable=false)
     */
    protected $isTestCommune = false;
    /**
     * @var bool
     *
     * @ORM\Column(name="is_test_presentiel", type="boolean", nullable=false)
     */
    protected $isTestPresentiel = false;
    /**
     * @ORM\ManyToOne(
     *     targetEntity="App\Entity\TestManagement\Test",
     * )
     * @ORM\JoinColumn(name="test_commune_id", onDelete="CASCADE", nullable=true)
     */
    protected $testCommune;
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
    private $slug;
    /**
     * @ORM\Column(type="string", length=255,nullable=true)
     */
    private $ref;
    /**
     * @ORM\Column(type="text")
     */
    private $title;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LovManagement\TypeTest")
     * @ORM\JoinColumn(nullable=false)
     */
    private $typeTest;
    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TestManagement\Question", mappedBy="test")
     */
    private $questions;
    /**
     * @ORM\Column(type="time", nullable=true)
     */
    private $theoricalDuration;

    private $module;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TestManagement\Pool", mappedBy="test")
     */
    private $pools;

    public function __construct()
    {
        $this->isTestCommune = false;
        $this->isTestPresentiel = false;
        $this->questions = new ArrayCollection();
        $this->moduleTests = new ArrayCollection();
        $this->theoricalDuration = new \DateTime('00:15:00');
        $this->pools = new ArrayCollection();
        $this->revision = -1;
    }

    /**
     * @return id
     */
    public function getId()
    {
        return $this->id;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent(Test $parent = null)
    {
        $this->parent = $parent;
    }

    public function getRef(): ?string
    {
        return $this->ref;
    }

    public function setRef(string $ref): self
    {
        $this->ref = $ref;

        return $this;
    }

    /**
     * @return string $slug
     */
    public function getSlug()
    {
        return $this->slug;
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

    public function getTypeTest(): ?TypeTest
    {
        return $this->typeTest;
    }

    public function setTypeTest(?TypeTest $typeTest): self
    {
        $this->typeTest = $typeTest;

        return $this;
    }

    /**
     * @return Collection|Question[]
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions[] = $question;
            $question->setTest($this);
        }

        return $this;
    }

    public function removeQuestion(Question $question): self
    {
        if ($this->questions->contains($question)) {
            $this->questions->removeElement($question);
            // set the owning side to null (unless already changed)
            if ($question->getTest() === $this) {
                $question->setTest(null);
            }
        }

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

    /**
     * @return \ArrayCollection $moduleTests
     */
    public function getModuleTests()
    {
        return $this->moduleTests;
    }

    /**
     * @param \ModuleTest $moduleTests
     * @return Test
     */
    public function setModuleTests($moduleTests)
    {
        $this->moduleTests = $moduleTests;

        return $this;
    }

    /**
     * @param ModuleCourse $moduleCourse
     * @return Test
     */
    public function addModuleTest(ModuleTest $moduleTest)
    {
        if ($this->moduleTests->contains($moduleTest)) {
            return;
        }

        $this->moduleTests[] = $moduleTest;

        return $this;
    }

    /**
     * @param ModuleTest $moduleTest
     * @return Module
     */
    public function removeModuleTests(ModuleTest $moduleTest)
    {
        if (!$this->moduleTests->contains($moduleTest)) {
            return;
        }

        $this->moduleTests->removeElement($moduleTest);

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

    /**
     * @return Collection|Pool[]
     */
    public function getPools(): Collection
    {
        return $this->pools;
    }

    public function addPool(Pool $pool): self
    {
        if (!$this->pools->contains($pool)) {
            $this->pools[] = $pool;
            $pool->setTest($this);
        }

        return $this;
    }

    public function removePool(Pool $pool): self
    {
        if ($this->pools->contains($pool)) {
            $this->pools->removeElement($pool);
            // set the owning side to null (unless already changed)
            if ($pool->getTest() === $this) {
                $pool->setTest(null);
            }
        }

        return $this;
    }

    /**
     * @ORM\PostPersist()
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        $this->setRef('Mn°' . $this->getModule()->getId() . 'Tn°' . $this->getId());
        $em->persist($this);
        $em->flush($this);
    }

    public function getModule(): ?Module
    {
        return $this->module;
    }

    public function setModule(?Module $module): self
    {
        $this->module = $module;

        return $this;
    }

    /**
     * functions for controllers or templates
     */
    public function checkActiveSessions()
    {
        foreach ($this->getModuleTests as $moduleTests) {
            if (!$moduleTests->getModule()->checkActiveSessions()) {
                return false;
            }
        }

        return true;
    }

    public function getTotalRequiredQuestion()
    {
        $total = 0;
        foreach ($this->pools as $pool) {
            if ($pool->getIsValid()) {
                $total += $pool->getNbRequQuestions();
            }
        }

        return $total;
    }

    public function getModuleTest()
    {
        foreach ($this->getModuleTests() as $moduleTests) {
            return $moduleTests;
        }

        return false;
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
