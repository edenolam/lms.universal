<?php

namespace App\Entity\FormationManagement;

use App\Entity\LovManagement\ModuleType;
use App\Entity\LovManagement\ValidationMode;
use App\Entity\TestManagement\Test;
use App\Traits\DateTrait;
use App\Traits\FileTrait;
use App\Traits\IsArchivedTrait;
use App\Traits\IsValidTrait;
use App\Traits\RevisionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\FormationManagement\ModuleRepository")
 * @Vich\Uploadable
 */
class Module
{
    use RevisionTrait, DateTrait, FileTrait, IsValidTrait, IsArchivedTrait;

    public const NUM_ITEMS = 10;
    /**
     * @var type
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\LovManagement\ModuleType")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="id", nullable=true)
     */
    protected $type;
    /**
     * @var text
     *
     * @ORM\Column(name="reference", type="string", length=50, nullable=true)
     */
    protected $reference;
    /**
     * @var text
     *
     * @ORM\Column(name="regulatory_ref", type="string", length=50, nullable=false)
     */
    protected $regulatoryRef;
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
     * @var bool
     *
     * @ORM\Column(name="last_module_requirement", type="boolean", nullable=true)
     */
    protected $lastModuleRequirement;
    /**
     * @var \Time
     *
     * @ORM\Column(name="realisation_time", type="time", nullable=false)
     */
    protected $realisationTime;
    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="ModuleCourse", mappedBy="module", cascade={"persist"})
     * @ORM\OrderBy({"sort" = "ASC"})
     */
    protected $moduleCourses;
    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="ModuleTest", mappedBy="module", cascade={"persist"})
     */
    protected $moduleTests;
    /**
     * @var text
     *
     * @ORM\Column(name="prerequisites", type="text", nullable=true)
     */
    protected $prerequisites;
    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="ModuleFile", mappedBy="module", cascade={"persist"})
     */
    protected $files;
    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="FormationPathModule", mappedBy="module", cascade={"persist"})
     * @ORM\OrderBy({"sort" = "ASC"})
     */
    protected $formationPathModules;
    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\PlanningManagement\SessionFormationPathModule", mappedBy="module")
     */
    protected $sessionFormationPathModules;
    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\LovManagement\ValidationMode")
     * @ORM\JoinTable(name="module_validation_mode",
     *  joinColumns={@ORM\JoinColumn(name="module_id", referencedColumnName="id")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="validation_mode_id", referencedColumnName="id")}
     * )
     */
    protected $validationModes;
    /**
     * @var bool
     *
     * @ORM\Column(name="is_scorm", type="boolean", nullable=false)
     */
    protected $isScorm = false;
    /**
     * @var text
     *
     * @ORM\Column(name="scorm_path", type="string", length=255, nullable=true)
     */
    protected $scormPath;
    protected $scormZip;
    /**
     * @var text
     *
     * @ORM\Column(name="lieu_formation", type="string", length=255, nullable=true)
     */
    protected $lieuFormation;
    /**
     * @var text
     *
     * @ORM\Column(name="nom_animateur", type="string", length=255, nullable=true)
     */
    protected $nomAnimateur;
    /**
     * @var text
     *
     * @ORM\Column(name="code_client", type="string", length=255, nullable=true)
     */
    protected $codeClient;
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @Gedmo\Slug(fields={"title", "reference"})
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;
    /**
     * @ORM\OneToOne(targetEntity="App\Entity\FormationManagement\Scorm", mappedBy="module", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $scorm;

    /**
     * @var int
     *
     * @ORM\Column(name="version", type="integer", nullable=false)
     */
    private $version;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\FormationManagement\VersioningModule", mappedBy="module")
     */
    private $versioningModules;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_default_module", type="boolean", nullable=false)
     */
    protected $isDefaultModule;

    /**
     * Constructor
     * Set courses as ArrayCollection
     */
    public function __construct()
    {
        $this->moduleCourses = new ArrayCollection();
        $this->moduleTests = new ArrayCollection();
        $this->files = new ArrayCollection();
        $this->isScorm = false;
        $this->isValid = false;
        $this->formationPathModules = new ArrayCollection();
        $this->sessionFormationPathModules = new ArrayCollection();
        $this->validationModes = new ArrayCollection();
        $this->realisationTime = new \DateTime('01:00:00');
        $this->IsArchived = false;
        $this->revision = -1;
        $this->version = 1;
        $this->versioningModules = new ArrayCollection();
        $this->isDefaultModule = false;
    }

    /**
     * @return id
     */
    public function getId()
    {
        return $this->id;
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
     * @return string $slug
     */
    public function getSlug()
    {
        return $this->slug;
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
     * @return bool $lastModuleRequirement
     */
    public function getLastModuleRequirement()
    {
        return $this->lastModuleRequirement;
    }

    /**
     * @param bool $lastModuleRequirement
     */
    public function setLastModuleRequirement($lastModuleRequirement)
    {
        $this->lastModuleRequirement = $lastModuleRequirement;
    }

    /**
     * @return string $prerequisites
     */
    public function getPrerequisites()
    {
        return $this->prerequisites;
    }

    /**
     * @param string $prerequisites
     */
    public function setPrerequisites($prerequisites)
    {
        $this->prerequisites = $prerequisites;
    }

    /**
     * @return \ArrayCollection $moduleCourses
     */
    public function getModuleCourses()
    {
        return $this->moduleCourses;
    }

    /**
     * @param \ModuleCourse $moduleCourses
     * @return Module
     */
    public function setModuleCourses($moduleCourses)
    {
        $this->moduleCourses = $moduleCourses;

        return $this;
    }

    /**
     * @param ModuleCourse $moduleCourse
     * @return Module
     */
    public function addModuleCourse(ModuleCourse $moduleCourse)
    {
        if ($this->moduleCourses->contains($moduleCourse)) {
            return;
        }

        $this->moduleCourses[] = $moduleCourse;

        return $this;
    }

    /**
     * @param ModuleCourse $moduleCourse
     * @return Module
     */
    public function removeModuleCourses(ModuleCourse $moduleCourse)
    {
        if (!$this->moduleCourses->contains($moduleCourse)) {
            return;
        }

        $this->moduleCourses->removeElement($moduleCourse);

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
     * @return Module
     */
    public function setModuleTests($moduleTests)
    {
        $this->moduleTests = $moduleTests;

        return $this;
    }

    /**
     * @param ModuleCourse $moduleCourse
     * @return Module
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

    /**
     * @return \ArrayCollection $files
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param \ArrayCollection $files
     * @return Module
     */
    public function setFiles($files)
    {
        $this->files = $files;

        return $this;
    }

    /**
     * @param ModuleFile $file
     * @return Module
     */
    public function addModuleFile(ModuleFile $file)
    {
        $this->files[] = $file;

        return $this;
    }

    /**
     * @param ModuleFile $file
     * @return Module
     */
    public function removeFile(ModuleFile $file)
    {
        $this->files->removeElement($file);
    }

    public function addValidationMode($validationMode)
    {
        if ($this->validationModes->contains($validationMode)) {
            return;
        }

        $this->validationModes[] = $validationMode;
    }

    public function removeValidationMode($validationMode)
    {
        if (!$this->validationModes->contains($validationMode)) {
            return;
        }
        $this->validationModes->removeElement($validationMode);
    }

    /**
     * @return string $reference
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @param string $reference
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
    }

    public function getRegulatoryRef()
    {
        return $this->regulatoryRef;
    }

    public function setRegulatoryRef($regulatoryRef)
    {
        $this->regulatoryRef = $regulatoryRef;
    }

    public function getIsScorm()
    {
        return $this->isScorm;
    }

    public function setIsScorm($isScorm)
    {
        $this->isScorm = $isScorm;

        return $this;
    }

    public function getIsDefaultModule()
    {
        return $this->isDefaultModule;
    }

    public function setIsDefaultModule($isDefaultModule)
    {
        $this->isDefaultModule = $isDefaultModule;

        return $this;
    }

    public function getScormPath()
    {
        return $this->scormPath;
    }

    public function setScormPath($scormPath)
    {
        $this->scormPath = $scormPath;

        return $this;
    }

    public function getScormZip()
    {
        return $this->scormZip;
    }

    public function setScormZip(File $scormZip = null)
    {
        $this->scormZip = $scormZip;
    }

    /**
     * Get scorm
     *
     * @return Scorm
     */
    public function getScorm()
    {
        return $this->scorm;
    }

    /**
     * Set scorm
     *
     * @param Scorm $scorm
     * @return Module
     */
    public function setScorm(Scorm $scorm = null)
    {
        $this->scorm = $scorm;

        return $this;
    }

    /**
     * @return \ArrayCollection $modules
     */
    public function getFormationPathModules()
    {
        return $this->formationPathModules;
    }

    /**
     * @param \ArrayCollection $modules
     * @return Module
     */
    public function setFormationPathModules($formationPathModules)
    {
        $this->formationPathModules = $formationPathModules;

        return $this;
    }

    /**
     * @param Module $module
     * @return Module
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
     * @return Module
     */
    public function removeFormationPathModules(FormationPathModule $formationPathModule)
    {
        if ($this->formationPathModules->contains($formationPathModule)) {
            $this->formationPathModules->removeElement($formationPathModule);
            if ($formationPathModule->getModule() === $this) {
                $formationPathModule->setModule(null);
                $formationPathModule->setFormationPath(null);
            }
        }

        return $this;
    }

    /**
     * @return \ArrayCollection $sessionFormationPathModules
     */
    public function getSessionFormationPathModules()
    {
        return $this->sessionFormationPathModules;
    }

    /**
     * @return string $codeClient
     */
    public function getCodeClient()
    {
        return $this->codeClient;
    }

    /**
     * @param string $codeClient
     */
    public function setCodeClient($codeClient)
    {
        $this->codeClient = $codeClient;
    }

    /**
     * @return string $nomAnimateur
     */
    public function getNomAnimateur()
    {
        return $this->nomAnimateur;
    }

    /**
     * @param string $nomAnimateur
     */
    public function setNomAnimateur($nomAnimateur)
    {
        $this->nomAnimateur = $nomAnimateur;
    }

    /**
     * @return string $lieuFormation
     */
    public function getLieuFormation()
    {
        return $this->lieuFormation;
    }

    /**
     * @param string $lieuFormation
     */
    public function setLieuFormation($lieuFormation)
    {
        $this->lieuFormation = $lieuFormation;
    }

    public function getValidationModes()
    {
        return $this->validationModes;
    }

    public function setValidationModes($validationModes)
    {
        $this->validationModes = $validationModes;
    }

    /**
     * @return \App\Entity\LovManagement\ModuleType
     */
    public function getType(): ?ModuleType
    {
        return $this->type;
    }

    /**
     * @param \App\Entity\LovManagement\ModuleType $type
     *
     * @return Module
     */
    public function setType(?ModuleType $type = null)
    {
        $this->type = $type;

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
        foreach ($this->formationPathModules as $formationPathModule) {
            if ($formationPathModule->getFormationPath()->checkActiveSessions()) {
                return true;
            }
        }

        return false;
    }

    public function isActiveSession()
    {
        return $this->checkActiveSessions();
    }

    public function getNbFormationLinked()
    {
        $formations = [];
        foreach ($this->getFormationPathModules() as $formationPathModule) {
            if (!in_array($formationPathModule->getFormationPath()->getId(), $formations)) {
                array_push($formations, $formationPathModule->getFormationPath()->getId());
            }
        }

        return count($formations);
    }

    public function getFormationPath()
    {
        foreach ($this->getFormationPathModules() as $formationPathModule) {
            return $formationPathModule->getFormationPath();
        }

        return null;
    }

    public function getPreTestNonValid() //validationMode
    {
        foreach ($this->validationModes as $validationMode) {
            if ($validationMode->getConditional() == ValidationMode::PreTestNonValid) {
                return true;
            }
        }

        return false;
    }

    public function getPreTestValid() //validationMode
    {
        foreach ($this->validationModes as $validationMode) {
            if ($validationMode->getConditional() == ValidationMode::PreTestValid) {
                return true;
            }
        }

        return false;
    }

    public function getEvaluation() //validationMode
    {
        foreach ($this->validationModes as $validationMode) {
            if ($validationMode->getConditional() == ValidationMode::Evaluation) {
                return true;
            }
        }

        return false;
    }

    public function getLectureComplete() //validationMode
    {
        foreach ($this->validationModes as $validationMode) {
            if ($validationMode->getConditional() == ValidationMode::LectureComplete) {
                return true;
            }
        }

        return false;
    }

    public function getModuleTrainingId() // moduleTest.test.id
    {
        foreach ($this->moduleTests as $moduleTest) {
            if ($moduleTest->getTest()->getTypeTest()->getConditional() == Test::ENTRAINEMENT) {
                return $moduleTest->getTest()->getId();
            }
        }

        return false;
    }

    public function getModulePreTestId() // moduleTest.test.id
    {
        foreach ($this->moduleTests as $moduleTest) {
            if ($moduleTest->getTest()->getTypeTest()->getConditional() == Test::PRE_TEST) {
                return $moduleTest->getTest()->getId();
            }
        }

        return false;
    }

    public function getModuleEvaluationId() // moduleTest.test.id
    {
        foreach ($this->moduleTests as $moduleTest) {
            if ($moduleTest->getTest()->getTypeTest()->getConditional() == Test::EVALUATION) {
                return $moduleTest->getTest()->getId();
            }
        }

        return false;
    }

    public function getModulePreTest() // moduleTest.test
    {
        foreach ($this->moduleTests as $moduleTest) {
            if ($moduleTest->getTest()->getTypeTest()->getConditional() == Test::PRE_TEST) {
                return $moduleTest->getTest();
            }
        }

        return false;
    }

    public function getModuleTraining() // moduleTest.test
    {
        foreach ($this->moduleTests as $moduleTest) {
            if ($moduleTest->getTest()->getTypeTest()->getConditional() == Test::ENTRAINEMENT) {
                return $moduleTest->getTest();
            }
        }

        return false;
    }

    public function getModuleSondage() // moduleTest.test
    {
        foreach ($this->moduleTests as $moduleTest) {
            if ($moduleTest->getTest()->getTypeTest()->getConditional() == Test::SONDAGE) {
                return $moduleTest->getTest();
            }
        }

        return false;
    }

    public function getModuleEvaluation() // moduleTest.test
    {
        foreach ($this->moduleTests as $moduleTest) {
            if ($moduleTest->getTest()->getTypeTest()->getConditional() == Test::EVALUATION) {
                return $moduleTest->getTest();
            }
        }

        return false;
    }

    public function IsCanPublish() // for module edit template
    {
        $avialableModulePreTest = $avialableModuleEval = true;
        if (($this->getPreTestNonValid() || $this->getPreTestValid()) && !$this->getModulePreTestId()) {
            $avialableModulePreTest = false;
        }
        if ($this->getEvaluation() && !$this->getModuleEvaluationId()) {
            $avialableModuleEval = false;
        }

        return ($avialableModulePreTest && $avialableModuleEval);
    }

    public function getModuleStart()
    {
        foreach ($this->sessionFormationPathModules as $sessionFormationPathModule) {
            if ($sessionFormationPathModule->getModule()->getId() == $this->getId()) {
                return $sessionFormationPathModule->getOpeningDate();
            }
        }

        return null;
    }

    public function getModuleEnd()
    {
        foreach ($this->sessionFormationPathModules as $sessionFormationPathModule) {
            if ($sessionFormationPathModule->getModule()->getId() == $this->getId()) {
                return $sessionFormationPathModule->getClosingDate();
            }
        }

        return null;
    }

    public function getNbPages()
    {
        $nbPage = 0;
        foreach ($this->moduleCourses as $moduleCourse) {
            foreach($moduleCourse->getCourse()->getPages() as $page){
                if ($page->getIsValid()) {
                    $nbPage ++ ;
                }
            }
        }

        return $nbPage;
    }

    public function getIsModulesFill()
    {
        $result = true;
        $course = 0;
        foreach ($this->moduleCourses as $moduleCourse) {
            $nbPage = 0;
            if($moduleCourse->getCourse()->getIsValid()){
                
                foreach($moduleCourse->getCourse()->getPages() as $page){
                    if ($page->getIsValid()) {
                        $nbPage ++ ;
                    }
                }
                if($nbPage == 0){
                    $result = false;
                }else{
                    $course ++;
                }
            } 
        }
        if($course == 0 && $this->getType()->getConditional() != ModuleType::NotFollow){
            $result = false;
        }

        return $result;
    }

    public function getlastValidCourses()
    {
        $course = null;
        foreach ($this->moduleCourses as $moduleCourse) {
            $nbPage = 0;
            if($moduleCourse->getCourse()->getIsValid()){
                $course = $moduleCourse->getCourse();
            } 
        }

        return $course;
    }

    public function getIsTestsGood()
    {
        $result = true;
        if ($this->getModulePretest()) {
            $preTest = $this->getModulePretest();
            $nbQ = 0;
            foreach($preTest->getQuestions() as $question){
                if ($question->getIsValid()) {
                    $nbQ ++ ;
                }
            }
            if($nbQ < $preTest->getTotalRequiredQuestion() or $nbQ == 0){
                $result = false;
            }
        }
        
        if ($this->getModuleTraining() && $result == true){
            $trainning = $this->getModuleTraining();
            $nbQ = 0;
            foreach($trainning->getQuestions() as $question){
                if ($question->getIsValid()) {
                    $nbQ ++ ;
                }
            }
            if($nbQ < $trainning->getTotalRequiredQuestion() or $nbQ == 0){
                $result = false;
            }
        }   
        if($this->getModuleEvaluation() && $result == true){
            if($this->getModuleEvaluation()->getIsTestCommune()){
                $eval = $this->getModuleEvaluation()->getTestCommune();
            }else{
                $eval = $this->getModuleEvaluation();
            }
            $nbQ = 0;
            foreach($eval->getQuestions() as $question){
                if ($question->getIsValid()) {
                    $nbQ ++ ;
                }
            }
            if( ($nbQ < $eval->getTotalRequiredQuestion() or $nbQ == 0 ) && !$eval->getIsTestPresentiel()){
                $result = false;
            }
        }     
        return $result;
    }

    public function checkModuleValid()
    {
        $preVal = $pre = $eval = $evalPres = $lecture = $presence = false;
        foreach ($this->getValidationModes() as $valMode) {
            if ($valMode->getConditional() == ValidationMode::PreTestValid) {
                $preVal = true;
            } elseif ($valMode->getConditional() == ValidationMode::PreTestNonValid) {
                $pre = true;
            } elseif ($valMode->getConditional() == ValidationMode::Evaluation) {
                $eval = true;
            } elseif ($valMode->getConditional() == ValidationMode::EvaluationPresentielle) {
                $evalPres = true;
            } elseif ($valMode->getConditional() == ValidationMode::Presence) {
                $presence = true;
            } elseif ($valMode->getConditional() == ValidationMode::LectureComplete) {
                $lecture = true;
            }
        }

        if ($this->getType()->getConditional() == ModuleType::Standard || $this->getType()->getConditional() == ModuleType::Scorm) {
            if ($eval === false && $lecture === false) {
                return false;
            }
        } elseif ($this->getType()->getConditional() == ModuleType::Presentiel) {
            if ($eval === false && $presence === false && $evalPres === false) {
                return false;
            }
        }

        return true;
    }

    public function getMaxSort()
    {
        $maxSort = 0;
        if (count($this->getModuleCourses()) !== 1) {
            foreach ($this->getModuleCourses() as $moduleCourse) {
                if ($moduleCourse->getSort() >= $maxSort) {
                    $maxSort = $moduleCourse->getSort() + 2;
                }
            }
        } else {
            $maxSort = 1;
        }

        return $maxSort;
    }

    /**
     * @ORM\PostPersist()
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        $this->setReference('Mn°' . $this->getId());
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

    /**
     * Set version
     *
     * @param int $version
     * @return self
     */
    public function setVersion(int $version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return int
     */
    public function getVersion(): ?int
    {
        return $this->version;
    }

    /**
     * @return Collection|VersioningModule[]
     */
    public function getVersioningModules(): Collection
    {
        return $this->versioningModules;
    }

    public function addVersioningModule(VersioningModule $versioningModule): self
    {
        if (!$this->versioningModules->contains($versioningModule)) {
            $this->versioningModules[] = $versioningModule;
            $versioningModule->setModule($this);
        }

        return $this;
    }

    public function removeVersioningModule(VersioningModule $versioningModule): self
    {
        if ($this->versioningModules->contains($versioningModule)) {
            $this->versioningModules->removeElement($versioningModule);
            // set the owning side to null (unless already changed)
            if ($versioningModule->getModule() === $this) {
                $versioningModule->setModule(null);
            }
        }

        return $this;
    }
}
