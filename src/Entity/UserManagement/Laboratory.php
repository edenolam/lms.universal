<?php

namespace App\Entity\UserManagement;

use App\Model\LovManagement\Lov as baseLov;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserManagement\LaboratoryRepository")
 * @ORM\Table(name="laboratory")
 * @ORM\HasLifecycleCallbacks()
 */
class Laboratory extends baseLov
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Gedmo\Slug(fields={"title"})
     * @ORM\Column(length=128, unique=true)
     */
    protected $slug;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection()
     *
     * @ORM\OneToMany(targetEntity="App\Entity\UserManagement\Division", mappedBy="laboratory", cascade={"remove"}, orphanRemoval=true)
     */
    private $divisions;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection()
     *
     * @ORM\OneToMany(targetEntity="App\Entity\UserManagement\User", mappedBy="laboratory", cascade={"remove"}, orphanRemoval=true)
     */
    private $users;

    protected $image;

    /**
     * @var string
     *
     * @ORM\Column(name="logo", type="text", nullable=true)
     */
    private $logo;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="text", nullable=true)
     * @Assert\Url()
     */
    protected $url;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=80, nullable=true)
     * @Assert\Length(min=2)
     * @Assert\Length(max=80)
     */
    protected $city;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=155, nullable=true)
     * @Assert\Length(
     *      min = 2,
     *      max = 155,
     *      minMessage = "Your address must be at least {{ limit }} characters long",
     *      maxMessage = "Your address cannot be longer than {{ limit }} characters"
     * )
     */
    protected $address;

    /**
     * @var string
     *
     * @ORM\Column(name="address_bis", type="string", length=155, nullable=true)
     * @Assert\Length(
     *      min = 2,
     *      max = 155,
     *      minMessage = "Your address bis must be at least {{ limit }} characters long",
     *      maxMessage = "Your address bis cannot be longer than {{ limit }} characters"
     * )
     */
    protected $addressBis;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection()
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\LovManagement\Country")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $country;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=20, nullable=true)
     * @Assert\Length(
     *      min = 6,
     *      max = 20,
     *      minMessage = "Your phone must be at least {{ limit }} characters long",
     *      maxMessage = "Your phone cannot be longer than {{ limit }} characters"
     * )
     */
    protected $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=50, nullable=true)
     * @Assert\Email(
     *     message = "The email '{{ value }}' is not a valid email.",
     *     checkMX = false
     * )
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="zip_code", type="string", length=20, nullable=true)
     * @Assert\Length(
     *      min = 2,
     *      max = 20,
     *      minMessage = "Your zip code must be at least {{ limit }} characters long",
     *      maxMessage = "Your zip code cannot be longer than {{ limit }} characters"
     * )
     */
    protected $zipCode;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\UserManagement\LdapServer", mappedBy="laboratory", cascade={"remove"})
     * @ORM\JoinColumn(name="ladp_server_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    private $ldapServer;

    public function __construct()
    {
        parent::__construct();
        $this->isValid = true;
        $this->revision = 0;
        $this->divisions = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return Laboratory
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return string $slug
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Add division
     *
     * @param Division $division
     * @return Laboratory
     */
    public function addDivision(Division $division)
    {
        $this->divisions[] = $division;

        return $this;
    }

    /**
     * Remove division
     *
     * @param Division $division
     */
    public function removeDivision(Division $division)
    {
        $this->divisions->removeElement($division);
    }

    /**
     * Get divisions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDivisions()
    {
        return $this->divisions;
    }

    /**
     * Add user
     *
     * @param User $user
     * @return Laboratory
     */
    public function addUser(User $user)
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove user
     *
     * @param User $user
     */
    public function removeUser(User $user)
    {
        $this->users->removeElement($user);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }
    /**
     * Set logo
     *
     * @param string $logo
     * @return Laboratory
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo
     *
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return Laboratory
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return Laboratory
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set Country
     *
     * @param string $country
     * @return Laboratory
     */
    public function setCountry(\App\Entity\LovManagement\Country $country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return App\Entity\LovManagement\Country
     */
    public function getCountry()
    {
        return $this->country;
    }

    public function setImage(File $file = null)
    {
        $this->image = $file;
    }

    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set address
     *
     * @param string $address
     * @return Company
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set addressBis
     *
     * @param string $addressBis
     * @return Laboratory
     */
    public function setAddressBis($addressBis)
    {
        $this->addressBis = $addressBis;

        return $this;
    }

    /**
     * Get addressBis
     *
     * @return string
     */
    public function getAddressBis()
    {
        return $this->addressBis;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return Laboratory
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Laboratory
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set zipCode
     *
     * @param string $zipCode
     * @return Laboratory
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * Get zipCode
     *
     * @return string
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    public function __toString()
    {
        return $this->title;
    }

    /**
     * Set ldapServer
     *
     * @param LdapServer $ldapServer
     * @return Laboratory
     */
    public function setLdapServer(LdapServer $ldapServer = null)
    {
        $this->ldapServer = $ldapServer;

        return $this;
    }

    /**
     * Get ldapServer
     *
     * @return LdapServer
     */
    public function getLdapServer()
    {
        return $this->ldapServer;
    }
}
