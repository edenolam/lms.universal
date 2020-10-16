<?php

namespace App\Entity\PlanningManagement;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PlanningManagement\SignatureRepository")
 */
class Signature
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
    private $code;


    /**
     * @ORM\Column(type="string", length=255)
     */
    private $ip;


    /**
     * @ORM\Column(type="text")
     */
    private $raison;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isValid;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\PlanningManagement\SessionFormationPathModule", inversedBy="signatures")
     */
    private $sessionFormationPathModule;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\PlanningManagement\Presentiel", mappedBy="signatures")
     */
    private $presentiels;

    public function __construct()
    {
        $this->presentiels = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }


    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }


    public function getRaison(): ?string
    {
        return $this->raison;
    }

    public function setRaison(string $raison): self
    {
        $this->raison = $raison;

        return $this;
    }

    public function getIsValid(): ?bool
    {
        return $this->isValid;
    }

    public function setIsValid(bool $isValid): self
    {
        $this->isValid = $isValid;

        return $this;
    }

    public function getSessionFormationPathModule(): ?SessionFormationPathModule
    {
        return $this->sessionFormationPathModule;
    }

    public function setSessionFormationPathModule(?SessionFormationPathModule $sessionFormationPathModule): self
    {
        $this->sessionFormationPathModule = $sessionFormationPathModule;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection|Presentiel[]
     */
    public function getPresentiels(): Collection
    {
        return $this->presentiels;
    }

    public function addPresentiel(Presentiel $presentiel): self
    {
        if (!$this->presentiels->contains($presentiel)) {
            $this->presentiels[] = $presentiel;
            $presentiel->addSignature($this);
        }

        return $this;
    }

    public function removePresentiel(Presentiel $presentiel): self
    {
        if ($this->presentiels->contains($presentiel)) {
            $this->presentiels->removeElement($presentiel);
            $presentiel->removeSignature($this);
        }

        return $this;
    }
}
