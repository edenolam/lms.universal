<?php

namespace App\Entity\UserFrontManagement;

use App\Entity\TestManagement\Question;
use App\Entity\TestManagement\Test;
use App\Entity\UserManagement\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserFrontManagement\UserQuestionRepository")
 */
class UserQuestion
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\UserManagement\User")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\UserFrontManagement\UserTest")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $userTest;

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
     * @ORM\Column(type="integer")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $testTentative;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TestManagement\Question")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $question;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TestManagement\Test")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $test;

    /**
     * @ORM\Column(type="array")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $userAnswers = [];

    /**
     * @ORM\Column(type="text",nullable=true)
     * @ORM\JoinColumn(nullable=true)
     */
    protected $verbalQuestion;

    /**
     * @ORM\Column(type="array")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $verbalAnswers = [];

    /**
     * @ORM\Column(type="boolean")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $scored;

    /**
     * @ORM\Column(type="array")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $questionDetails = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getRefFormation()
    {
        return $this->refFormation;
    }

    public function setRefFormation(string $refFormation)
    {
        $this->refFormation = $refFormation;
    }

    public function getRefModule()
    {
        return $this->refModule;
    }

    public function setRefModule(string $refModule)
    {
        $this->refModule = $refModule;
    }

    public function getTestTentative()
    {
        return $this->testTentative;
    }

    public function setTestTentative($testTentative)
    {
        $this->testTentative = $testTentative;
    }

    public function getQuestion()
    {
        return $this->question;
    }

    public function setQuestion(Question $question)
    {
        $this->question = $question;
    }

    public function getUserAnswers()
    {
        return $this->userAnswers;
    }

    public function setUserAnswers($userAnswers)
    {
        $this->userAnswers = $userAnswers;
    }

    public function getScored()
    {
        return $this->scored;
    }

    public function setScored($scored)
    {
        $this->scored = $scored;
    }

    public function getTest()
    {
        return $this->test;
    }

    public function setTest(Test $test)
    {
        $this->test = $test;
    }

    public function getVerbalQuestion()
    {
        return $this->verbalQuestion;
    }

    public function setVerbalQuestion($verbalQuestion)
    {
        $this->verbalQuestion = $verbalQuestion;
    }

    public function getVerbalAnswers()
    {
        return $this->verbalAnswers;
    }

    public function setVerbalAnswers($verbalAnswers)
    {
        $this->verbalAnswers = $verbalAnswers;
    }

    public function getUserTest()
    {
        return $this->userTest;
    }

    public function setUserTest($userTest)
    {
        $this->userTest = $userTest;
    }

    public function getQuestionDetails()
    {
        return $this->questionDetails;
    }

    public function setQuestionDetails($questionDetails)
    {
        $this->questionDetails = $questionDetails;
    }

    public function __construct()
    {
    }
}
