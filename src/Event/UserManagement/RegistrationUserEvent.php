<?php

namespace App\Event\UserManagement;

use App\Entity\UserManagement\User;
use Symfony\Component\EventDispatcher\Event;

class RegistrationUserEvent extends Event
{
    public const NAME = 'registration.user.event';

    /**
     * @var User
     */
    protected $user;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var User
     */
    protected $creator;

    /**
     * RegistrationUserEvent constructor.
     * @param User $user
     */
    public function __construct(User $user, User $creator, $password)
    {
        $this->user = $user;
        $this->creator = $creator;
        $this->password = $password;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return creator
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @return password
     */
    public function getPassword()
    {
        return $this->password;
    }
}
