<?php

namespace App\Manager;

use App\Entity\FormationManagement\Course;
use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\Page;
use App\Entity\PlanningManagement\Session;
use App\Entity\UserFrontManagement\UserCourseFollow;
use App\Entity\UserManagement\User;
use App\Persistence\ObjectManager;
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * UserCourseFollowManager
 *
 * @author null
 */
class UserCourseFollowManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    protected $logger;
    protected $sfSession;
    protected $translator;

    /**
     * UserCourseFollowManager constructor.
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

    public function updateUserCourse(Session $session, Module $module, Course $course, Page $page, User $user): void
    {
        $this->logger->err('updateUserCourse');
        $userCourse = $this->em->getRepository('App:UserFrontManagement\UserCourseFollow')->findOneBy(['course' => $course, 'module' => $module, 'session' => $session, 'user' => $user]);

        $userModuleFollow = $this->em->getRepository('App:UserFrontManagement\UserModuleFollow')->findOneBy(['module' => $module, 'session' => $session, 'user' => $user]);


        try {
            if (!is_object($userCourse)) {
                $this->logger->err('!is_object(userCourse)');
                $userCourse = new UserCourseFollow();
                $userCourse->setCourse($course);
                $userCourse->setRefCourse($course->getReference());
                $userCourse->setSession($session);
                $userCourse->setModule($module);
                //tracking
                $userCourse->setUser($user);
                $userCourse->setStartDate(new \DateTime('now'));
                $userCourse->setDurationTotal(new \DateTime('00:00:00'));
                $userCourse->setDurationLastSession(new \DateTime('00:00:00'));
                $userCourse->setDurationTotalSec(0);
                $userCourse->setDurationLastSessionSec(0);
                $userCourse->setLastConnexion(new \DateTime('now'));
                if ($page) {
                    $userCourse->setLastPage($page);
                }
            } else {
                $this->logger->err('is_object(userCourse)');
                $today = new \DateTime('now');
                if($today < $session->getClosingDate() && $userModuleFollow->getEndDate() == null){
                    //temps
                    $newTotDurationC = $userCourse->getDurationTotal()->getTimestamp() + 1;
                    $durationTotalC = new \DateTime();
                    $durationTotalC->setTimestamp($newTotDurationC);

               
                    $totalTimeSec = $this->em->getRepository('App:UserFrontManagement\UserPageFollow')->getTotalTimeSecCourse($user, $session, $module, $course);
                    $userCourse->setDurationTotalSec($totalTimeSec);


                    //pourcentage
                    $totalPage = $this->em->getRepository('App:FormationManagement\Course')->getTotalPageCourse($course);
                    $totalPageFollow = $this->em->getRepository('App:UserFrontManagement\UserPageFollow')->getTotalFollow($user, $session, $module, $course);
                    if ($totalPage == 0 || $totalPageFollow == 0) {
                        $percentage = 0;
                    } else {
                        $percentage = round($totalPageFollow * 100 / $totalPage, 0, PHP_ROUND_HALF_UP);
                    }
                    //tracking
                    $userCourse->setDurationTotal($durationTotalC);
                    $userCourse->setLastConnexion(new \DateTime('now'));
                    $userCourse->setDurationLastSession(new \DateTime('00:00:00'));
                    if ($page) {
                        $userCourse->setLastPage($page);
                    }
                    $userCourse->setPercentage($percentage);
                    if ($percentage == 100) {
                        $userCourse->setEndDate(new \DateTime('now'));
                    }
                }
            }

            $this->em->persist($userCourse);
            $this->em->flush();
        } catch (DBALException $e) {
            $this->logger->err($e->getMessage());
            $this->sfSession->getFlashBag()->add('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            $this->sfSession->getFlashBag()->add('error', $e->getMessage());
        }
    }
}
