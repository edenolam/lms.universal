<?php

namespace App\Entity\ServiceManagement;

use App\Entity\UserManagement\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ServiceManagement\AuditRepository")
 */
class Audit
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $action;

    /**
     * @ORM\Column(name="entity_name", type="string", length=80)
     */
    private $entityName;

    /**
     * @ORM\Column(name="entity_id", type="integer")
     */
    private $entityId;

    /**
     * @ORM\Column(type="array")
     */
    private $curentValue;

    /**
     * @ORM\Column(type="array")
     */
    private $curentEntity;

    /**
     * @ORM\Column(type="array")
     */
    private $oldValue;

    /**
     * @ORM\Column(type="array")
     */
    private $oldEntity;

    /**
     * @ORM\Column(type="datetime")
     */
    private $datetime;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\UserManagement\User")
     * @ORM\JoinColumn(nullable=true)
     */
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getEntityName(): ?string
    {
        return $this->entityName;
    }

    public function setEntityName(string $entityName): self
    {
        $this->entityName = $entityName;

        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(int $entityId): self
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getCurentValue()
    {
        return $this->curentValue;
    }

    public function setCurentValue($curentValue): self
    {
        $this->curentValue = $curentValue;

        return $this;
    }

    public function getCurentEntity()
    {
        return $this->curentEntity;
    }

    public function setCurentEntity($curentEntity): self
    {
        $this->curentEntity = $curentEntity;

        return $this;
    }

    public function getOldValue()
    {
        return $this->oldValue;
    }

    public function setOldValue($oldValue): self
    {
        $this->oldValue = $oldValue;

        return $this;
    }

    public function getOldEntity()
    {
        return $this->oldEntity;
    }

    public function setOldEntity($oldEntity): self
    {
        $this->oldEntity = $oldEntity;

        return $this;
    }

    public function getDatetime(): ?\DateTimeInterface
    {
        return $this->datetime;
    }

    public function setDatetime(\DateTimeInterface $datetime): self
    {
        $this->datetime = $datetime;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): ?self
    {
        $this->user = $user;

        return $this;
    }
}
