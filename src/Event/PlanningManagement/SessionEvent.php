<?php

namespace App\Event\PlanningManagement;

use App\Entity\PlanningManagement\Session;
use Symfony\Component\EventDispatcher\Event;

class SessionEvent extends Event
{
    public const NAME = 'session.event';

    /**
     * @var Session
     */
    protected $session;

    /**
     * SessionEvent constructor.
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }
}
