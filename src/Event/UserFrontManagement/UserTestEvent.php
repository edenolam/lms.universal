<?php

namespace App\Event\UserFrontManagement;

use App\Entity\FormationManagement\Module;
use App\Entity\PlanningManagement\Session;
use App\Entity\TestManagement\Test;
use App\Entity\UserFrontManagement\UserTest;
use App\Entity\UserManagement\User;
use Symfony\Component\EventDispatcher\Event;

class UserTestEvent extends Event
{
    public const NAME = 'user.test';

    protected $test;
    protected $userTest;
    protected $module;
    protected $session;
    protected $user;

    /**
     * UserTestEvent constructor.
     */
    public function __construct(Test $test, UserTest $userTest, Module $module, Session $session, User $user)
    {
        $this->test = $test;
        $this->userTest = $userTest;
        $this->module = $module;
        $this->session = $session;
        $this->user = $user;
    }

    public function getTest()
    {
        return $this->test;
    }

    public function getUserTest()
    {
        return $this->userTest;
    }

    public function getModule()
    {
        return $this->module;
    }

    public function getSession()
    {
        return $this->session;
    }

    public function getUser()
    {
        return $this->user;
    }
}
