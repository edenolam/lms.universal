<?php

namespace App\EventListener\UserFrontManagement;

use App\Event\UserFrontManagement\PageEvent;
use App\Manager\UserCourseFollowManager;
use App\Manager\UserFormationSessionFollowManager;
use App\Manager\UserModuleFollowManager;
use App\Manager\UserPageFollowManager;

/**
 * PageListener
 *
 * @author null
 */
class PageListener
{
    private $userFormationSessionFollowManager;
    private $userModuleFollowManager;
    private $userCourseFollowManager;
    private $userPageFollowManager;

    public function __construct(UserFormationSessionFollowManager $userFormationSessionFollowManager, UserModuleFollowManager $userModuleFollowManager, UserCourseFollowManager $userCourseFollowManager, UserPageFollowManager $userPageFollowManager)
    {
        $this->userFormationSessionFollowManager = $userFormationSessionFollowManager;
        $this->userModuleFollowManager = $userModuleFollowManager;
        $this->userCourseFollowManager = $userCourseFollowManager;
        $this->userPageFollowManager = $userPageFollowManager;
    }

    /**
     * @param PageEvent $event
     */
    public function onPageEvent(PageEvent $event): void
    {
        $today = new \DateTime();

        if ($event->getSession()->getOpeningDate() <= $today && $event->getSession()->getClosingDate() >= $today) {
            $this->userPageFollowManager->updateUserPageFollow($event->getSession(), $event->getModule(), $event->getCourse(), $event->getPage(), $event->getUser());
            $this->userCourseFollowManager->updateUserCourse($event->getSession(), $event->getModule(), $event->getCourse(), $event->getPage(), $event->getUser());
            $this->userModuleFollowManager->updateUserModule($event->getSession(), $event->getModule(), $event->getCourse(), $event->getPage(), $event->getUser());
            $this->userFormationSessionFollowManager->updateUserSessionsFormation($event->getSession(), $event->getModule(), $event->getCourse(), $event->getPage(), $event->getUser());
        }
    }
}
