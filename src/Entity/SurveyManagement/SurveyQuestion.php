<?php

namespace App\Entity\SurveyManagement;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SurveyManagement\SurveyQuestionRepository")
 */
class SurveyQuestion
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $question;

    /**
     * @ORM\OneToMany(targetEntity="SurveyAnswer", mappedBy="question")
     */
    private $answers;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getAnswers()
    {
        return $this->answers;
    }

    public function addAnswer($answer)
    {
        $this->answers[] = $answer;

        return $this;
    }

    public function removeAnswer($answer)
    {
        $this->answers->removeElement($answer);

        return $this;
    }

    public function setAnswers($answers)
    {
        $this->answers = $answers;
    }

    public function __construct()
    {
        $this->answers = new ArrayCollection();
    }
}
