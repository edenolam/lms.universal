<?php

namespace App\Entity\UserFrontManagement;

use App\Entity\FormationManagement\Course;
use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\Page;
use App\Entity\PlanningManagement\Session;
use App\Entity\ResultManagement\Certificat;
use App\Traits\IsDeletedTrait;
use App\Traits\LearningProgressTrait;
use App\Traits\LearningScoreTrait;
use App\Traits\TrackingLearningTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\UserFrontManagement\UserModuleFollowRepository")
 */
class UserModuleFollow
{
    use TrackingLearningTrait, LearningProgressTrait, LearningScoreTrait, IsDeletedTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    //==================================Info utiles===========================

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FormationManagement\Module")
     * @ORM\JoinColumn(nullable=false)
     */
    private $module;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $refModule;

    //================================== Info progression ===========================

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FormationManagement\Course")
     * @ORM\JoinColumn(nullable=true)
     */
    private $lastCourse;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FormationManagement\Page")
     * @ORM\JoinColumn(nullable=true)
     */
    private $lastPage;

    //================================== Info navigation ===========================

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\PlanningManagement\Session")
     * @ORM\JoinColumn(nullable=false)
     */
    private $session;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $preTestDone;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $lectureDone;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\ResultManagement\Certificat", mappedBy="userModuleFollow", cascade={"persist", "remove"})
     */
    private $certificat;

    private $userLastPage;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $validationDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lectureDate;
    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="UserModuleFollowFile", mappedBy="userModuleFollow", cascade={"persist"})
     */
    protected $files;

    /**
     * @var int
     *
     * @ORM\Column(name="module_version", type="integer", nullable=false)
     */
    private $moduleVersion;

    public function __contruct()
    {
        $this->files = new ArrayCollection();
        $this->preTestDone = false;
        $this->lectureDone = false;
        $this->isDeleted = false;
        $this->durationTotalSec= 0;
        $this->durationLastSessionSec = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRefModule(): ?string
    {
        return $this->refModule;
    }

    public function setRefModule(string $refModule): self
    {
        $this->refModule = $refModule;

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): self
    {
        $this->session = $session;

        return $this;
    }

    public function getLastCourse(): ?Course
    {
        return $this->lastCourse;
    }

    public function setLastCourse(?Course $lastCourse): self
    {
        $this->lastCourse = $lastCourse;

        return $this;
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

    public function getLastPage(): ?Page
    {
        return $this->lastPage;
    }

    public function setLastPage(?Page $lastPage): self
    {
        $this->lastPage = $lastPage;

        return $this;
    }

    public function getPreTestDone()
    {
        return $this->preTestDone;
    }

    public function setPreTestDone($preTestDone)
    {
        $this->preTestDone = $preTestDone;
    }

    public function getLectureDone()
    {
        return $this->lectureDone;
    }

    public function setLectureDone($lectureDone)
    {
        $this->lectureDone = $lectureDone;
    }

    public function getPercentage()
    {
        return $this->percentage;
    }

    public function setPercentage($percentage)
    {
        $this->percentage = $percentage;
    }

    public function getCertificat(): ?Certificat
    {
        return $this->certificat;
    }

    public function setCertificat(Certificat $certificat): self
    {
        $this->certificat = $certificat;

        // set the owning side of the relation if necessary
        if ($this !== $certificat->getUserModuleFollow()) {
            $certificat->setUserModuleFollow($this);
        }

        return $this;
    }

    public function getUserLastPage()
    {
        return $this->userLastPage;
    }

    public function setUserLastPage(UserPageFollow $userLastPage)
    {
        $this->userLastPage = $userLastPage;
    }

    public function getValidationDate(): ?\DateTimeInterface
    {
        return $this->validationDate;
    }

    public function setValidationDate(?\DateTimeInterface $validationDate): self
    {
        $this->validationDate = $validationDate;

        return $this;
    }

    public function getLectureDate(): ?\DateTimeInterface
    {
        return $this->lectureDate;
    }

    public function setLectureDate(?\DateTimeInterface $lectureDate): self
    {
        $this->lectureDate = $lectureDate;

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
     * @return UserModuleFollow
     */
    public function setFiles($files)
    {
        $this->files = $files;

        return $this;
    }

    /**
     * @param UserModuleFollowFile $file
     * @return UserModuleFollow
     */
    public function addUserModuleFollowFile(UserModuleFollowFile $file)
    {
        $this->files[] = $file;

        return $this;
    }

    /**
     * @param UserModuleFollowFile $file
     * @return UserModuleFollow
     */
    public function removeFile(UserModuleFollowFile $file)
    {
        $this->files->removeElement($file);
    }

    /**
     * ======================================
     *  functions for contollers and templates
     * =====================================
     */
    private $userPreTest = null;
    private $userEvaluation = null;
    private $userTraining = null;

    public function getUserPreTest(): ?UserTest
    {
        return $this->userPreTest;
    }

    public function setUserPreTest(?UserTest $userPreTest)
    {
        $this->userPreTest = $userPreTest;

        return $this;
    }

    public function getUserEvaluation(): ?UserTest
    {
        return $this->userEvaluation;
    }

    public function setUserEvaluation(?UserTest $userEvaluation)
    {
        $this->userEvaluation = $userEvaluation;

        return $this;
    }

    public function getUserTraining(): ?UserTest
    {
        return $this->userTraining;
    }

    public function setUserTraining(?UserTest $userTraining)
    {
        $this->userTraining = $userTraining;

        return $this;
    }

    /**
     * Set moduleVersion
     *
     * @param int $moduleVersion
     * @return self
     */
    public function setModuleVersion(int $moduleVersion)
    {
        $this->moduleVersion = $moduleVersion;

        return $this;
    }

    /**
     * Get moduleVersion
     *
     * @return int
     */
    public function getModuleVersion(): ?int
    {
        return $this->moduleVersion;
    }
}
