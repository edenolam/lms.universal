<?php

namespace App\EventListener\PlanningManagement;

use App\Event\PlanningManagement\SessionEvent;
use App\Manager\UserFormationSessionFollowManager;
use App\Manager\UserModuleFollowManager;

/**
 * SessionListener
 *
 * @author null
 */
class SessionListener
{
    /**
     * @var UserModuleFollowManager
     */
    private $userModuleFollowManager;
    private $userFormationSessionFollowManager;

    /**
     * SessionListener constructor.
     * @param UserModuleFollowManager $userModuleFollowManager
     */
    public function __construct(UserModuleFollowManager $userModuleFollowManager, UserFormationSessionFollowManager $userFormationSessionFollowManager)
    {
        $this->userModuleFollowManager = $userModuleFollowManager;
        $this->userFormationSessionFollowManager = $userFormationSessionFollowManager;
    }

    /**
     * @param SessionEvent $event
     */
    public function onSessionEvent(SessionEvent $event): void
    {
        // createUserModule
        $this->userModuleFollowManager->createUserModule($event->getSession());
        $this->userFormationSessionFollowManager->createUserSessionsFormation($event->getSession());
    }
}
