<?php

namespace App\Manager;

use App\Entity\FormationManagement\Course;
use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\Page;
use App\Entity\PlanningManagement\Session;
use App\Entity\UserFrontManagement\UserPageFollow;
use App\Entity\UserManagement\User;
use App\Persistence\ObjectManager;
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * UserPageFollowManager
 *
 * @author null
 */
class UserPageFollowManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    protected $logger;
    protected $sfSession;
    protected $translator;

    /**
     * UserModuleFollowManager constructor.
     * @param EntityManager $em
     * @param Logger $logger
     */
    public function __construct(ObjectManager $em, LoggerInterface $logger, SessionInterface $sfSession, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->sfSession = $sfSession;
        $this->translator = $translator;
    }

    public function updateUserPageFollow(Session $session, Module $module, Course $course, Page $page, User $user): void
    {
        $this->logger->err('updateUserPageFollow');
        $userPage = $this->em->getRepository('App:UserFrontManagement\UserPageFollow')->findOneBy(['page' => $page, 'course' => $course, 'module' => $module, 'session' => $session, 'user' => $user]);

        try {
            if (!is_object($userPage)) {
                $this->logger->err('!is_object(userPage)');
                $userPage = new UserPageFollow();
                $userPage->setPage($page);
                $userPage->setRefPage($page->getReference());
                $userPage->setSession($session);
                $userPage->setModule($module);
                $userPage->setCourse($course);
                //tracking
                $userPage->setUser($user);
                $userPage->setStartDate(new \DateTime('now'));
                $userPage->setEndDate(new \DateTime('now'));
                $userPage->setDurationTotal(new \DateTime('00:00:00'));
                $userPage->setDurationTotalSec(0);
                $userPage->setDurationLastSession(new \DateTime('00:00:00'));
                $userPage->setDurationLastSessionSec(0);
                $userPage->setLastConnexion(new \DateTime('now'));
            } else {
                $this->logger->err('is_object(userPage)');
                //$newTotDurationP = $userPage->getDurationTotal()->getTimestamp() + 1;
                //$durationTotalP = new \DateTime();
                //$durationTotalP->setTimestamp($newTotDurationP);
                // $durationSessionP= new \DateTime();
                // $durationSessionP->setTimestamp($duration);
                //tracking
                //$userPage->setDurationTotal($durationTotalP);
                //$userPage->setDurationLastSession($durationSessionP);
                //$userPage->setLastConnexion(new \DateTime('now'));
            }

            $this->em->persist($userPage);
            $this->em->flush();
        } catch (DBALException $e) {
            $this->logger->err($e->getMessage());
            $this->sfSession->getFlashBag()->add('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            $this->sfSession->getFlashBag()->add('error', $e->getMessage());
        }

        return;
    }
}
