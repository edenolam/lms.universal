<?php

namespace App\Entity\UserManagement;

use App\Model\LovManagement\Lov as baseLov;
use App\Traits\{UuidTrait};
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserManagement\LdapServerRepository")
 * @ORM\Table(name="ldap_servers")
 * @ORM\HasLifecycleCallbacks()
 */
class LdapServer extends baseLov
{
    use UuidTrait;

    /**
     * The unique auto incremented primary key.
     *
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned": true})
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * [host: my-server, port: 389, base_dn]
     * ['connection_string' => 'ldaps://my-server:636']
     *
     * @ORM\Column(type="string", nullable=false)
     */
    protected $url; //ldap://localhost:389

    /**
     * [search_dn: "cn=admin,dc=example,dc=com"]
     *
     * @ORM\Column(type="string", nullable=false)
     */
    protected $bindDn; // cn=admin,dc=example,dc=com

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $password;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $userBaseDn; // 'ou=People,dc=example,dc=com'

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $userObjectClassFilter; // objectClass=person

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $userAttributes;

    /**
     * [uid_key: uid]
     *
     * @ORM\Column(type="string", nullable=false)
     */
    protected $usernameAttribute;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $mailAttribute; // mail_attribute: mail

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\UserManagement\Laboratory", inversedBy="ldapServer", cascade={"remove"})
     * @ORM\JoinColumn(name="laboratory_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $laboratory;

    public function __construct(UuidInterface $uuid)
    {
        $this->uuid = $uuid;
        $this->title = $uuid;
        $this->isValid = true;
        $this->revision = 0;
        $this->isDeleted = false;
    }

    public function __toString(): ?string
    {
        return $this->title;
    }

    /**
     * Returns the primary key identifier.
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    public function getLaboratory(): Laboratory
    {
        return $this->laboratory;
    }

    public function setLaboratory(Laboratory $laboratory)
    {
        $laboratory = $laboratory->setLdapServer($this);
        $this->laboratory = $laboratory;

        return $this;
    }

    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setBindDn($bindDn)
    {
        $this->bindDn = $bindDn;

        return $this;
    }

    public function getBindDn()
    {
        return $this->bindDn;
    }

    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setUserBaseDn($userBaseDn)
    {
        $this->userBaseDn = $userBaseDn;

        return $this;
    }

    public function getUserBaseDn()
    {
        return $this->userBaseDn;
    }

    public function setUserObjectClassFilter($userObjectClassFilter)
    {
        $this->userObjectClassFilter = $userObjectClassFilter;

        return $this;
    }

    public function getUserObjectClassFilter()
    {
        return $this->userObjectClassFilter;
    }

    public function setUserAttributes($userAttributes)
    {
        $this->userAttributes = $userAttributes;

        return $this;
    }

    public function getUserAttributes()
    {
        return $this->userAttributes;
    }

    public function setUsernameAttribute($usernameAttribute)
    {
        $this->usernameAttribute = $usernameAttribute;

        return $this;
    }

    public function getUsernameAttribute()
    {
        return $this->usernameAttribute;
    }

    public function setMailAttribute($mailAttribute)
    {
        $this->mailAttribute = $mailAttribute;

        return $this;
    }

    public function getMailAttribute()
    {
        return $this->mailAttribute;
    }
}
