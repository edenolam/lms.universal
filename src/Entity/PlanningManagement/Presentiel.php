<?php

namespace App\Entity\PlanningManagement;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PlanningManagement\PresentielRepository")
 */
class Presentiel
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\PlanningManagement\Animateur", inversedBy="presentiels")
     */
    private $animateurs;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\PlanningManagement\Signature", inversedBy="presentiels")
     */
    private $signatures;

    public function __construct()
    {
        $this->animateurs = new ArrayCollection();
        $this->signatures = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|Animateur[]
     */
    public function getAnimateurs(): Collection
    {
        return $this->animateurs;
    }

    public function addAnimateur(Animateur $animateur): self
    {
        if (!$this->animateurs->contains($animateur)) {
            $this->animateurs[] = $animateur;
        }

        return $this;
    }

    public function removeAnimateur(Animateur $animateur): self
    {
        if ($this->animateurs->contains($animateur)) {
            $this->animateurs->removeElement($animateur);
        }

        return $this;
    }

    /**
     * @return Collection|Signature[]
     */
    public function getSignatures(): Collection
    {
        return $this->signatures;
    }

    public function addSignature(Signature $signature): self
    {
        if (!$this->signatures->contains($signature)) {
            $this->signatures[] = $signature;
        }

        return $this;
    }

    public function removeSignature(Signature $signature): self
    {
        if ($this->signatures->contains($signature)) {
            $this->signatures->removeElement($signature);
        }

        return $this;
    }
}
