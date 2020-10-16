<?php

namespace App\Entity\TestManagement;

use App\Traits\ActorTrait;
use App\Traits\DateTrait;
use App\Traits\IsValidTrait;
use App\Traits\RevisionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\TestManagement\PoolRepository")
 */
class Pool
{
    use ActorTrait, RevisionTrait, DateTrait, IsValidTrait;

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
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="integer")
     */
    private $nbRequQuestions;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TestManagement\Test", inversedBy="pools")
     * @ORM\JoinColumn(nullable=false)
     */
    private $test;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TestManagement\Question", mappedBy="pool")
     */
    private $questions;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->isValid = true;
        $this->revision = -1;
    }

    public function getId(): ?int
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getNbRequQuestions(): ?int
    {
        return $this->nbRequQuestions;
    }

    public function setNbRequQuestions(int $nbRequQuestions): self
    {
        $this->nbRequQuestions = $nbRequQuestions;

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
            $question->setPool($this);
        }

        return $this;
    }

    public function removeQuestion(Question $question): self
    {
        if ($this->questions->contains($question)) {
            $this->questions->removeElement($question);
            // set the owning side to null (unless already changed)
            if ($question->getPool() === $this) {
                $question->setPool(null);
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
        $this->setRef('Tn°' . $this->getTest()->getId() . 'Pn°' . $this->getId()); //"Tn°".$test->getId()."_Pn°".$pool->getId()
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
