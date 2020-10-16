<?php

namespace App\Entity\UserFrontManagement;

use App\Entity\PlanningManagement\Session;
use App\Entity\TestManagement\Test;
use App\Entity\UserManagement\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserFrontManagement\UserTestRepository")
 */
class UserTest
{
    /**
     * @var \User
     * @ORM\ManyToOne(targetEntity="App\Entity\UserManagement\User")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $user;
    /**
     * @ORM\Column(type="string", length=255)
     * @ORM\JoinColumn(nullable=true)
     */
    protected $refFormation;
    /**
     * @ORM\Column(type="string", length=255)
     * @ORM\JoinColumn(nullable=true)
     */
    protected $refModule;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TestManagement\Test")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $test;
    /**
     * @ORM\Column(type="datetime",nullable=true)
     * @ORM\JoinColumn(nullable=true)
     */
    protected $datePass;
    /**
     * @ORM\Column(type="datetime")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $dateDown;
    /**
     * @ORM\Column(type="integer")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $score;
    /**
     * @ORM\Column(type="array")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $shuffleQuestions = [];
    /**
     * @ORM\Column(type="integer")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $lastIndexQuestion;
    /**
     * @ORM\Column(type="array")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $detail = [];
    /**
     * @ORM\Column(type="array")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $detailAnswer = [];
    /**
     * @ORM\Column(type="integer")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $duration;
    /**
     * @ORM\Column(type="time", nullable=true)
     * @ORM\JoinColumn()
     */
    protected $currentChrono;
    /**
     * @ORM\Column(type="integer")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $tentative;
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\PlanningManagement\Session")
     * @ORM\JoinColumn(nullable=false)
     */
    private $session;
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $numberTry;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $numberTryDescription;

    public function __construct()
    {
        $this->lastIndexQuestion = 0;
        $this->tentative = 1;
        $this->score = -1;
        $this->duration = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getRefFormation(): ?string
    {
        return $this->refFormation;
    }

    public function setRefFormation(string $refFormation): self
    {
        $this->refFormation = $refFormation;

        return $this;
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

    public function getTest(): ?Test
    {
        return $this->test;
    }

    public function setTest(?Test $test): self
    {
        $this->test = $test;

        return $this;
    }

    public function getDatePass(): ?\DateTimeInterface
    {
        return $this->datePass;
    }

    public function setDatePass(\DateTimeInterface $datePass): self
    {
        $this->datePass = $datePass;

        return $this;
    }

    public function getDateDown(): ?\DateTimeInterface
    {
        return $this->dateDown;
    }

    public function setDateDown(\DateTimeInterface $dateDown): self
    {
        $this->dateDown = $dateDown;

        return $this;
    }

    public function getShuffleQuestions()
    {
        return $this->shuffleQuestions;
    }

    public function setShuffleQuestions($shuffleQuestions)
    {
        $this->shuffleQuestions = $shuffleQuestions;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getDetail(): ?array
    {
        return $this->detail;
    }

    public function setDetail(array $detail): self
    {
        $this->detail = $detail;

        return $this;
    }

    public function getDetailAnswer(): ?array
    {
        return $this->detailAnswer;
    }

    public function setDetailAnswer(array $detailAnswer): self
    {
        $this->detailAnswer = $detailAnswer;

        return $this;
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    public function getTentative(): ?int
    {
        return $this->tentative;
    }

    public function setTentative(int $tentative): self
    {
        $this->tentative = $tentative;

        return $this;
    }

    public function getLastIndexQuestion()
    {
        return $this->lastIndexQuestion;
    }

    public function setLastIndexQuestion(int $lastIndexQuestion)
    {
        $this->lastIndexQuestion = $lastIndexQuestion;
    }

    public function getCurrentChrono()
    {
        return $this->currentChrono;
    }

    public function setCurrentChrono($currentChrono)
    {
        $this->currentChrono = $currentChrono;
    }

    public function getCurrentChronoSeconds()
    {
        $parsed = date_parse($this->currentChrono->format('H:i:s'));

        return $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];
    }

    public function setCurrentChronoSeconds($seconds)
    {
        $this->currentChrono = new \DateTime(gmdate('H:i:s', $seconds));
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

    public function getNumberTry(): ?int
    {
        return $this->numberTry;
    }

    public function setNumberTry(int $numberTry): self
    {
        $this->numberTry = $numberTry;

        return $this;
    }

    public function getNumberTryDescription(): ?string
    {
        return $this->numberTryDescription;
    }

    public function setNumberTryDescription(string $numberTryDescription): self
    {
        $this->numberTryDescription = $numberTryDescription;

        return $this;
    }
}
