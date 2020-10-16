<?php

namespace App\EventListener\UserFrontManagement;

use App\Event\UserFrontManagement\UserTestEvent;
use App\Manager\UserFormationSessionFollowManager;
use App\Manager\UserModuleFollowManager;

/**
 * UserTestListener
 *
 * @author null
 */
class UserTestListener
{
    private $userModuleFollowManager;

    public function __construct(UserFormationSessionFollowManager $userFormationSessionFollowManager, UserModuleFollowManager $userModuleFollowManager)
    {
        $this->userFormationSessionFollowManager = $userFormationSessionFollowManager;
        $this->userModuleFollowManager = $userModuleFollowManager;
    }

    /**
     * @param UserTestEvent $event
     */
    public function onUserTestEvent(UserTestEvent $event): void
    {
        $today = new \DateTime();
        if ($event->getSession()->getOpeningDate() <= $today && $event->getSession()->getClosingDate() >= $today) {
            $this->userModuleFollowManager->checkValidationTest($event->getSession(), $event->getModule(), $event->getUserTest(), $event->getTest(), $event->getUser());

            $this->userFormationSessionFollowManager->updateUserSessionsFormation($event->getSession(), $event->getModule(), null, null, $event->getUser());
        }
    }
}
