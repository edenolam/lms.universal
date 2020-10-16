<?php

namespace App\Entity\PlanningManagement;

use App\Entity\UserManagement\User;
use App\Traits\DateTrait;
use App\Traits\IsValidTrait;
use App\Traits\RevisionTrait;
use App\Traits\UuidTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PlanningManagement\VirtualClassRoomRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class VirtualClassRoom
{
    use RevisionTrait, DateTrait, IsValidTrait, UuidTrait;
    public const NOW = 1;
    public const FUTURE = 2;
    public const PASSED = 3;
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=150)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=150)
     */
    private $classId;

    /**
     * @ORM\Column(type="datetime")
     */
    private $openingDate;

    /**
     * @ORM\Column(type="datetime")
     */
    private $closingDate;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\UserManagement\User")
     *  @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * })
     */
    private $teacher;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\UserManagement\User", inversedBy="studentVirtualClassRooms")
     * @ORM\JoinTable(name="virtual_class_room_user",
     *  joinColumns={
     *      @ORM\JoinColumn(name="virtual_class_room_id", referencedColumnName="id")
     *  }, inverseJoinColumns={
     *      @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     *  }
     * )
     */
    private $students;

    public function __construct()
    {
        $this->refreshUuid();
        $this->students = new ArrayCollection();
        $this->isValid = true;
        $this->revision = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getClassId(): ?string
    {
        return $this->classId;
    }

    public function setClassId(string $classId): self
    {
        $this->classId = $classId;

        return $this;
    }

    public function getOpeningDate(): ?\DateTimeInterface
    {
        return $this->openingDate;
    }

    public function setOpeningDate(\DateTimeInterface $openingDate): self
    {
        $this->openingDate = $openingDate;

        return $this;
    }

    public function getClosingDate(): ?\DateTimeInterface
    {
        return $this->closingDate;
    }

    public function setClosingDate(\DateTimeInterface $closingDate): self
    {
        $this->closingDate = $closingDate;

        return $this;
    }

    public function getTeacher(): ?User
    {
        return $this->teacher;
    }

    public function setTeacher(User $teacher): self
    {
        $this->teacher = $teacher;

        return $this;
    }

    public function getStudents()
    {
        return $this->students;
    }

    public function addStudent(User $student)
    {
        if ($this->students->contains($student)) {
            return $this;
        }
        $this->students[] = $student;

        return $this;
    }

    public function removeStudent(User $student)
    {
        if (!$this->students->contains($student)) {
            return $this;
        }

        $this->students->removeElement($student);

        return $this;
    }

    public function setStudents($students)
    {
        $this->students = $students;

        return $this;
    }
}
