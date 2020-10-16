<?php

namespace App\Event\UserFrontManagement;

use App\Entity\FormationManagement\Course;
use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\Page;
use App\Entity\PlanningManagement\Session;
use App\Entity\UserManagement\User;
use Symfony\Component\EventDispatcher\Event;

class PageEvent extends Event
{
    public const NAME = 'page.event';

    protected $page;
    protected $course;
    protected $module;
    protected $session;
    protected $user;

    /**
     * PageEvent constructor.
     * @param Page $page
     * @param Course $course
     * @param Module $module
     * @param Session $session
     */
    public function __construct(Page $page, Course $course, Module $module, Session $session, User $user)
    {
        $this->page = $page;
        $this->course = $course;
        $this->module = $module;
        $this->session = $session;
        $this->user = $user;
    }

    public function getPage()
    {
        return $this->page;
    }

    public function getCourse()
    {
        return $this->course;
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
