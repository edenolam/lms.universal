<?php

namespace App\Entity\PlanningManagement;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PlanningManagement\AnimateurRepository")
 */
class Animateur
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
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $phoneNumber;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $fonction;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $organisation;

    /**
     * @ORM\Column(type="text")
     */
    private $indicationComplementaire;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\PlanningManagement\Presentiel", mappedBy="animateurs")
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


    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param mixed $firstName
     * @return Animateur
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param mixed $lastName
     * @return Animateur
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getFonction(): ?string
    {
        return $this->fonction;
    }

    public function setFonction(string $fonction): self
    {
        $this->fonction = $fonction;
        return $this;
    }

    public function getOrganisation(): ?string
    {
        return $this->organisation;
    }

    public function setOrganisation(string $organisation): self
    {
        $this->organisation = $organisation;
        return $this;
    }

    public function getIndicationComplementaire(): ?string
    {
        return $this->indicationComplementaire;
    }

    public function setIndicationComplementaire(?string $indicationComplementaire): self
    {
        $this->indicationComplementaire = $indicationComplementaire;

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
            $presentiel->addAnimateur($this);
        }

        return $this;
    }

    public function removePresentiel(Presentiel $presentiel): self
    {
        if ($this->presentiels->contains($presentiel)) {
            $this->presentiels->removeElement($presentiel);
            $presentiel->removeAnimateur($this);
        }

        return $this;
    }

}
