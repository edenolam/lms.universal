<?php

namespace App\Entity\UserManagement;

use App\Entity\LovManagement\Civility;
use App\Entity\PlanningManagement\Session;
use App\Entity\PlanningManagement\VirtualClassRoom;
use App\Traits\ActorTrait;
use App\Traits\DateTrait;
use App\Traits\IsValidTrait;
use App\Traits\RevisionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserManagement\UserRepository")
 * @ORM\Table(name="fos_user")
 * @UniqueEntity(fields={"email"}, message="Cet email est déjà utilisé")
 * @UniqueEntity(fields={"username"}, message="Cet identifiant est déjà utilisé")
 * @ORM\HasLifecycleCallbacks()
 */
class User extends BaseUser
{
    use ActorTrait, DateTrait, IsValidTrait, RevisionTrait;

    public const NUM_ITEMS = 10;
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_APPRENANT = 'ROLE_APPRENANT';
    public const ROLE_TUTEUR = 'ROLE_TUTEUR';
    public const ROLE_CONCEPTEUR = 'ROLE_CONCEPTEUR';
    public const ROLE_RESPONSABLE_FORMATION = 'ROLE_RESPONSABLE_FORMATION';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public const DEFAULT_ROLE = self::ROLE_APPRENANT;
    public const DEFAULT_LANGUAGE = 'fr';
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToMany(targetEntity="User")
     * @ORM\JoinTable(name="user_supervisor",
     *   joinColumns={
     *      @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     *  }, inverseJoinColumns={
     *      @ORM\JoinColumn(name="supervisor_id", referencedColumnName="id")
     *  }
     * )
     */
    protected $supervisors;

    protected $image;

    /**
     * @var string
     *
     * @ORM\Column(name="photo",  type="string", length=255,  nullable=true)
     */
    protected $photo;

    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=100, nullable=false)
     * @Assert\Length(
     *      min = 2,
     *      max = 100,
     *      minMessage = "Your first name must be at least {{ limit }} characters long",
     *      maxMessage = "Your first name cannot be longer than {{ limit }} characters"
     * )
     * @ORM\OrderBy({"firstname" = "DESC"})
     */
    protected $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=100, nullable=false)
     * @Assert\Length(
     *      min = 2,
     *      max = 100,
     *      minMessage = "Your last name must be at least {{ limit }} characters long",
     *      maxMessage = "Your last name cannot be longer than {{ limit }} characters"
     * )
     * @ORM\OrderBy({"lastname" = "DESC"})
     */
    protected $lastname;

    /**
     * @var \Civility
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\LovManagement\Civility")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="civility_id", referencedColumnName="id", nullable=true)
     * })
     * @Assert\Valid()
     */
    protected $civility;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=50, nullable=true)
     * @Assert\Length(
     *      min = 2,
     *      max = 50,
     *      minMessage = "Your address must be at least {{ limit }} characters long",
     *      maxMessage = "Your address cannot be longer than {{ limit }} characters"
     * )
     */
    protected $address;

    /**
     * @var string
     *
     * @ORM\Column(name="address_bis", type="string", length=50, nullable=true)
     * @Assert\Length(
     *      min = 2,
     *      max = 50,
     *      minMessage = "Your address bis must be at least {{ limit }} characters long",
     *      maxMessage = "Your address bis cannot be longer than {{ limit }} characters"
     * )
     */
    protected $addressBis;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=50, nullable=true)
     * @Assert\Length(
     *      min = 2,
     *      max = 50,
     *      minMessage = "Your city must be at least {{ limit }} characters long",
     *      maxMessage = "Your city cannot be longer than {{ limit }} characters"
     * )
     */
    protected $city;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection()
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\LovManagement\Country")
     * @ORM\JoinColumn(nullable=true)
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
     * @var string
     *
     * @ORM\Column(name="function", type="string", length=50, nullable=true)
     * @Assert\Length(
     *      min = 2,
     *      max = 20,
     *      minMessage = "Your function must be at least {{ limit }} characters long",
     *      maxMessage = "Your function cannot be longer than {{ limit }} characters"
     * )
     */
    protected $function;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\UserManagement\Group", inversedBy="users")
     * @ORM\JoinTable(name="fos_user_group",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     * @Assert\Valid()
     */
    protected $groups;

    /**
     * @var \laboratory
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\UserManagement\Laboratory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="laboratory_id", referencedColumnName="id", nullable=true)
     * })
     * @Assert\Valid()
     */
    protected $laboratory;

    /**
     * @var \Division
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\UserManagement\Division")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="division_id", referencedColumnName="id", nullable=true)
     * })
     * @Assert\Valid()
     */
    protected $division;

    /**
     * @var \Team
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\UserManagement\Team")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="team_id", referencedColumnName="id", nullable=true)
     * })
     * @Assert\Valid()
     */
    protected $team;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_change_password", type="datetime", nullable=true)
     */
    protected $lastChangePassword;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\PlanningManagement\Session", mappedBy="users", cascade={"persist"})
     */
    protected $sessions;

    /**
     * @var string
     *
     * @ORM\Column(name="info_1", type="string", length=255, nullable=true)
     */
    protected $info1;

    /**
     * @var string
     *
     * @ORM\Column(name="info_2", type="string", length=255, nullable=true)
     */
    protected $info2;

    /**
     * @var string
     *
     * @ORM\Column(name="info_3", type="string", length=255, nullable=true)
     */
    protected $info3;

    /**
     * @var int
     *
     * @ORM\Column(name="hierarchy_Level", type="integer", nullable=false)
     */
    protected $hierarchyLevel;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection()
     *
     * @ORM\OneToMany(targetEntity="App\Entity\UserFrontManagement\UserTest", mappedBy="user", cascade={"remove"}, orphanRemoval=true)
     */
    private $usertests;

    /**
     * @var bool
     *
     * @ORM\Column(name="ldap_user", type="boolean", nullable=false)
     */
    protected $ldapUser = false;

    /**
     * @ORM\Column(name="user_dn", type="string", nullable=true)
     */
    protected $userDn; // 'uid=abarnes, dc=example,dc=com'

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\PlanningManagement\VirtualClassRoom", inversedBy="students", cascade={"persist"})
     */
    protected $studentVirtualClassRooms;

    public function __construct()
    {
        parent::__construct();
        $this->groups = new ArrayCollection();
        $this->lastChangePassword = null;
        $this->isValid = true;
        $this->revision = 0;
        $this->supervisors = new ArrayCollection();
        $this->sessions = new ArrayCollection();
        $this->usertests = new ArrayCollection();
        $this->studentVirtualClassRooms = new ArrayCollection();
        $this->hierarchyLevel = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * @return int
     */
    public function getHierarchyLevel(): int
    {
        return $this->hierarchyLevel;
    }

    /**
     * @param int $hierarchyLevel
     */
    public function setHierarchyLevel(int $hierarchyLevel): void
    {
        $this->hierarchyLevel = $hierarchyLevel;
    }

    public function getLaboratory(): ?Laboratory
    {
        return $this->laboratory;
    }

    public function setLaboratory(Laboratory $laboratory)
    {
        $this->laboratory = $laboratory;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getSupervisors(): Collection
    {
        return $this->supervisors;
    }

    public function addSupervisor(User $supervisor): self
    {
        if (!$this->supervisors->contains($supervisor)) {
            $this->supervisors[] = $supervisor;
        }

        return $this;
    }

    public function removeSupervisor(User $supervisor): self
    {
        if ($this->supervisors->contains($supervisor)) {
            $this->supervisors->removeElement($supervisor);
        }

        return $this;
    }

    /**
     * Get civility
     *
     * @return object
     */
    public function getCivility()
    {
        return $this->civility;
    }

    public function setCivility(Civility $civility)
    {
        $this->civility = $civility;

        return $this;
    }

    public function getDivision(): ?Division
    {
        return $this->division;
    }

    public function setDivision(?Division $division): self
    {
        $this->division = $division;

        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): self
    {
        $this->team = $team;

        return $this;
    }

    public function getRolesFromGroups()
    {
        $roles = [];
        foreach ($this->getGroups() as $group) {
            foreach ($group->getRoles() as $key => $role) {
                $roles[] = $role;
            }
        }

        return $roles;
    }

    public function getUserRoles()
    {
        return $this->roles;
    }

    /**
     * Set lastChangePassword
     *
     * @param \DateTime $lastChangePassword
     * @return User
     */
    public function setLastChangePassword($lastChangePassword)
    {
        $this->lastChangePassword = $lastChangePassword;

        return $this;
    }

    /**
     * Get lastChangePassword
     *
     * @return \DateTime
     */
    public function getLastChangePassword()
    {
        return $this->lastChangePassword;
    }

    /**
     * @return Session
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    /**
     * @param Session $session
     *                         Add module
     */
    public function addSession($session)
    {
        if ($this->sessions->contains($session)) {
            return;
        }

        $this->sessions[] = $session;
        $session->addUser($this);
    }

    /**
     * @param Session $session
     */
    public function removeSession($session)
    {
        if (!$this->sessions->contains($session)) {
            return;
        }

        $this->sessions->removeElement($session);
        $session->removeUser($this);
    }

    public function setSessions($sessions)
    {
        $this->sessions = $sessions;
    }

    /**
     * Set photo
     *
     * @param string $photo
     * @return User
     */
    public function setPhoto($photo)
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * Get photo
     *
     * @return string
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return User
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
     * @return User
     */
    public function setCountry(\App\Entity\LovManagement\Country $country = null)
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

    /**
     * Set function
     *
     * @param string $function
     * @return User
     */
    public function setFunction($function)
    {
        $this->function = $function;

        return $this;
    }

    /**
     * Get function
     *
     * @return string
     */
    public function getFunction()
    {
        return $this->function;
    }

    public function getInfo1(): ?string
    {
        return $this->info1;
    }

    public function setInfo1(string $info1): self
    {
        $this->info1 = $info1;

        return $this;
    }

    public function getInfo2(): ?string
    {
        return $this->info2;
    }

    public function setInfo2(string $info2): self
    {
        $this->info2 = $info2;

        return $this;
    }

    public function getInfo3(): ?string
    {
        return $this->info3;
    }

    public function setInfo3(string $info3): self
    {
        $this->info3 = $info3;

        return $this;
    }

    /**
     * Add usertest
     *
     * @param UserTest $usertest
     * @return User
     */
    public function addUserTest(User $usertest)
    {
        $this->usertests[] = $usertest;

        return $this;
    }

    /**
     * Remove usertest
     *
     * @param UserTest $usertest
     */
    public function removeUserTest(Team $usertest)
    {
        $this->usertests->removeElement($usertest);
    }

    /**
     * Get usertests
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserTests()
    {
        return $this->usertests;
    }

    public function setLdapUser(int $ldapUser)
    {
        $this->ldapUser = $ldapUser;

        return $this;
    }

    public function getLdapUser(): ?int
    {
        return $this->ldapUser;
    }

    public function setUserDn(string $userDn)
    {
        $this->userDn = $userDn;

        return $this;
    }

    public function getUserDn(): ?string
    {
        return $this->userDn;
    }

    public function getStudentVirtualClassRooms()
    {
        return $this->studentVirtualClassRooms;
    }

    public function addStudentVirtualClassRoom(VirtualClassRoom $studentVirtualClassRoom)
    {
        if ($this->studentVirtualClassRooms->contains($studentVirtualClassRoom)) {
            return;
        }

        $this->studentVirtualClassRooms[] = $studentVirtualClassRoom;
        $studentVirtualClassRoom->addStudent($this);
    }

    public function removeStudentVirtualClassRoom(VirtualClassRoom $studentVirtualClassRoom)
    {
        if (!$this->studentVirtualClassRooms->contains($studentVirtualClassRoom)) {
            return;
        }

        $this->studentVirtualClassRooms->removeElement($studentVirtualClassRoom);
        $studentVirtualClassRoom->removeStudent($this);
    }

    public function setStudentVirtualClassRooms($studentVirtualClassRooms)
    {
        $this->studentVirtualClassRooms = $studentVirtualClassRooms;
    }
}
