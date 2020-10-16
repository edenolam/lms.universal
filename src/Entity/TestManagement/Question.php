<?php

namespace App\Entity\TestManagement;

use App\Entity\FormationManagement\Knowledge;
use App\Entity\FormationManagement\Page;
use App\Entity\LovManagement\AnswerType;
use App\Traits\ActorTrait;
use App\Traits\DateTrait;
use App\Traits\IsDeletedTrait;
use App\Traits\IsValidTrait;
use App\Traits\RevisionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\TestManagement\QuestionRepository")
 */
class Question
{
    use ActorTrait, RevisionTrait, DateTrait, IsValidTrait, IsDeletedTrait;

    public const NUM_ITEMS = 10;
    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\FormationManagement\Knowledge", inversedBy="questions")
     * @ORM\JoinTable(name="question_knowledge",
     *  joinColumns={
     *      @ORM\JoinColumn(name="question_id", referencedColumnName="id")
     *  }, inverseJoinColumns={
     *      @ORM\JoinColumn(name="knowledge_id", referencedColumnName="id")
     *  }
     * )
     */
    protected $knowledges;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LovManagement\AnswerType")
     * @ORM\JoinColumn(name="answer_type_id", referencedColumnName="id")
     */
    protected $answerType;
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ref;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $title;
    /**
     * @Gedmo\Slug(fields={"ref", "id"})
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;
    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $weight;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $comment;
    /**
     * @ORM\Column(type="boolean")
     */
    private $required;
    /**
     * @ORM\Column(type="time", nullable=true)
     */
    private $theoricalDuration;
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\FormationManagement\Page", inversedBy="Questions")
     * @ORM\JoinColumn(nullable=true)
     */
    private $lessonPage;
    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TestManagement\Answer", mappedBy="question")
     */
    private $answers;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TestManagement\Test", inversedBy="questions")
     * @ORM\JoinColumn(nullable=true)
     */
    private $test;

    /**
     * @ORM\Column(type="text")
     */
    private $question;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TestManagement\Pool", inversedBy="questions")
     * @ORM\JoinColumn(nullable=true)
     */
    private $pool;

    public function __construct()
    {
        $this->answers = new ArrayCollection();
        $this->knowledges = new ArrayCollection();
        $this->isDeleted = false;
        $this->isValid = true;
        $this->weight = 1;
        $this->revision = -1;
    }

    public function getId()
    {
        return $this->id;
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

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Module
     */
    public function getKnowledges()
    {
        return $this->knowledges;
    }

    /**
     * @param ArrayCollection $knowledges
     */
    public function setKnowledges($knowledges)
    {
        $this->knowledges = $knowledges;
    }

    /**
     * @param Knowledge $knowledge
     *                             Add knowledge
     */
    public function addKnowledge(Knowledge $knowledge)
    {
        if ($this->knowledges->contains($knowledge)) {
            return;
        }

        $this->knowledges[] = $knowledge;
        $knowledge->addQuestion($this);
    }

    /**
     * @param Knowledge $knowledge
     */
    public function removeKnowledge($knowledge)
    {
        if (!$this->knowledges->contains($knowledge)) {
            return;
        }

        $this->knowledges->removeElement($knowledge);
        $knowledge->removeQuestion($this);
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getRequired(): ?bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): self
    {
        $this->required = $required;

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

    public function getLessonPage(): ?Page
    {
        return $this->lessonPage;
    }

    public function setLessonPage(?Page $lessonPage): self
    {
        $this->lessonPage = $lessonPage;

        return $this;
    }

    public function getAnswerType(): ?AnswerType
    {
        return $this->answerType;
    }

    public function setAnswerType(?AnswerType $answerType): self
    {
        $this->answerType = $answerType;

        return $this;
    }

    /**
     * @return Collection|Answer[]
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addAnswer(Answer $answer): self
    {
        if (!$this->answers->contains($answer)) {
            $this->answers[] = $answer;
            $answer->setQuestion($this);
        }

        return $this;
    }

    public function removeAnswer(Answer $answer): self
    {
        if ($this->answers->contains($answer)) {
            $this->answers->removeElement($answer);
            // set the owning side to null (unless already changed)
            if ($answer->getQuestion() === $this) {
                $answer->setQuestion(null);
            }
        }

        return $this;
    }

    public function getTest(): ?Test
    {
        return $this->test;
    }

    public function setTest(Test $test): self
    {
        $this->test = $test;

        return $this;
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

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment(?string $comment)
    {
        $this->comment = $comment;
    }

    public function getPool(): ?Pool
    {
        return $this->pool;
    }

    public function setPool(Pool $pool): self
    {
        $this->pool = $pool;

        return $this;
    }

    /**less codes in controllers and tesmplates**/

    /**
     * @ORM\PostPersist()
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        $this->setRef('Tn°' . $this->getTest()->getId() . 'Qn°' . $this->getId());
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
}
