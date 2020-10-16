<?php

namespace App\Manager;

use App\Entity\FormationManagement\Course;
use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\Page;
use App\Entity\FormationManagement\ScoTracking;
use App\Entity\PlanningManagement\Session;
use App\Entity\TestManagement\Test;
use App\Entity\UserFrontManagement\UserFormationSessionFollow;
use App\Entity\UserFrontManagement\UserModuleFollow;
use App\Entity\UserFrontManagement\UserTest;
use App\Entity\UserManagement\User;
use App\Persistence\ObjectManager;
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * UserModuleFollowManager
 *
 * @author null
 */
class UserModuleFollowManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var SessionInterface
     */
    protected $sfSession;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var CertificatManager
     */
    protected $certificatManager;

    /**
     * @var UserFormationSessionFollowManager
     */
    protected $userFormationSessionFollowManager;

    /**
     * UserModuleFollowManager constructor.
     * @param ObjectManager $em
     * @param LoggerInterface $logger
     * @param SessionInterface $sfSession
     * @param TranslatorInterface $translator
     * @param CertificatManager $certificatManager
     * @param UserFormationSessionFollowManager $userFormationSessionFollowManager
     */
    public function __construct(ObjectManager $em, LoggerInterface $logger, SessionInterface $sfSession, TranslatorInterface $translator, CertificatManager $certificatManager, UserFormationSessionFollowManager $userFormationSessionFollowManager)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->sfSession = $sfSession;
        $this->translator = $translator;
        $this->certificatManager = $certificatManager;
        $this->userFormationSessionFollowManager = $userFormationSessionFollowManager;
    }

    /**
     * @param Session $session
     * @param Module $module
     * @param User $user
     * @param ScoTracking $scoTracking
     */
    public function updateUserModuleFollowScorm(Session $session, Module $module, User $user, ScoTracking $scoTracking): void
    {
        $this->logger->err('updateUserModuleFollowScorm');
        $userModule = $this->em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')->findOneBy(['module' => $module, 'session' => $session, 'user' => $user]);
        if ($userModule->getStartDate() == null) {
            $userModule->setStartDate(new \DateTime('now'));
        }
        if ($userModule->getEndDate() == null) {
            try {
                $this->logger->err('is_object(userModule)');
                //tacking
                $this->logger->err($scoTracking->getFormattedTotalTime());
                // setDurationTotal
                $str_time = preg_replace("/^([\d]{1,2})\:([\d]{2})$/", '00:$1:$2', $scoTracking->getFormattedTotalTime());
                sscanf($str_time, '%d:%d:%d', $hours, $minutes, $seconds);
                $time_seconds = $hours * 3600 + $minutes * 60 + $seconds;

                $duration_total = gmdate('H:i:s', $time_seconds);
                $userModule->setDurationTotal(new \DateTime($duration_total));
                $userModule->setLastConnexion(new \DateTime('now'));

                // setDurationLastSession
                $duration_last_session = gmdate('H:i:s', $scoTracking->getSessionTime());
                $userModule->setDurationLastSession(new \DateTime($duration_last_session));
                $userModule->setLastCourse(null);
                $userModule->setLastPage(null);

                if ($scoTracking->getProgression() == 0 && $scoTracking->getSuspendData()) {
                    //$scoTracking->getSuspendData()
                    //1;1:1|2:0|3:0|4:0|5:0|6:0|7:0|8:0|9:0|10:0|11:0|12:0|13:0|14:0|15:0|16:0|17:0|18:0|19:0|20:0|21:0|22:0|23:0|24:0|25:0_-et-_{_-ap-_75_-ap-_:-1}
                    $this->logger->err($scoTracking->getSuspendData());
                    $this->logger->err($user->getUsername());
                    $this->logger->err($module->getRegulatoryRef());
                    $progression = $this->progression($scoTracking->getSuspendData());
                    if ($progression > 0) {
                        $userModule->setPercentage($progression);
                    }
                    if ($progression == 100) {
                        if ($userModule->getLectureDate() == null) {
                            $userModule->setLectureDate(new \DateTime('now'));
                        }
                        $userModule->setLectureDone(true);
                    }
                } else {
                    $userModule->setPercentage($scoTracking->getProgression());
                }

                if ($userModule->getPercentage() == 100) {
                    if ($userModule->getLectureDate() == null) {
                        $userModule->setLectureDate(new \DateTime('now'));
                    }
                    $userModule->setLectureDone(true);
                }

                // result
                if ('passed' === $scoTracking->getLessonStatus()) {
                    //   var_dump($scoTracking->getLessonStatus());
                    $userModule->setSuccess(true);
                    //$userModule->setScore($scoTracking->getScoreRaw());
                    $userModule->setEndDate(new \DateTime('now'));
                    $userFormation = $this->em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findOneBy(['session' => $session, 'user' => $user]);
                    $this->certificatManager->createCertificat($userFormation, $userModule, $user);
                } else {
                    $userModule->setScore($scoTracking->getScoreRaw());
                    $userModule->setSuccess(false);
                }
                $this->logger->err($userModule->getEndDate());
                $this->logger->err($userModule->getPercentage());
                $this->logger->err($scoTracking->getLessonStatus());

                if ($userModule->getEndDate() == null && $userModule->getPercentage() == 100 && 'completed' === $scoTracking->getLessonStatus()) {
                    $userFormation = $this->em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findOneBy(['session' => $session, 'user' => $user]);
                    $this->checkValidationModule($session, $module, $user, $userFormation, $userModule);
                }

                $this->em->persist($userModule);
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

    /**
     * @param Session $session
     */
    public function createUserModule(Session $session)
    {
        foreach ($session->getFormationPath()->getFormationPathModules() as $formationPathModule) {
            foreach ($session->getUsers() as $user) {
                $userModule = $this->em->getRepository('App:UserFrontManagement\UserModuleFollow')->findOneBy(['session' => $session, 'module' => $formationPathModule->getModule(), 'user' => $user]);

                if (is_object($userModule) && $userModule instanceof UserModuleFollow) {
                    $userModule->setIsDeleted(false);
                } else {
                    $userModule = $this->createUserModuleFollow($formationPathModule->getModule(), $session, $user);
                }
                try {
                    $this->em->persist($userModule);
                    $this->em->flush();
                } catch (\Doctrine\DBAL\DBALException $e) {
                    $this->sfSession->getFlashBag()->add('error', $e->getMessage());
                } catch (\Exception $e) {
                    $this->sfSession->getFlashBag()->add('error', $e->getMessage());
                }
            }
        }

        return;
    }

    /**
     * @param Session $session
     * @param User $user
     */
    public function deleteUserModule(Session $session, User $user)
    {
        $userModules = $this->em->getRepository('App:UserFrontManagement\UserModuleFollow')->findBy(['session' => $session, 'user' => $user]);
        if ($userModules) {
            foreach ($userModules as $userModule) {
                try {
                    $userModule->setIsDeleted(true);
                    $this->em->persist($userModule);
                    $this->em->flush();
                } catch (\Doctrine\DBAL\DBALException $e) {
                    $this->sfSession->getFlashBag()->add('error', $e->getMessage());
                } catch (\Exception $e) {
                    $this->sfSession->getFlashBag()->add('error', $e->getMessage());
                }
            }
        }

        return;
    }

    /**
     * update user standard module follow
     *
     * @param Session $session
     * @param Module $module
     * @param Course $course
     * @param Page $page
     * @param User $user
     */
    public function updateUserModule(Session $session, Module $module, Course $course, Page $page, User $user)
    {
        $userFormation = $this->em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findOneBy(['session' => $session, 'user' => $user]);
        $userModule = $this->em->getRepository('App:UserFrontManagement\UserModuleFollow')->findOneBy(['module' => $module, 'session' => $session, 'user' => $user]);
        if ($userModule->getStartDate() == null) {
            $userModule->setStartDate(new \DateTime('now')); 
        }
        if ($userModule->getEndDate() == null) {
            try {
                //temps
                $durationTotalM = new \DateTime();
                if ($userModule->getDurationTotal()) {
                    $newTotDurationM = $userModule->getDurationTotal()->getTimestamp() + 1;
                    $durationTotalM->setTimestamp($newTotDurationM);
                }

                $totalTimeSec = $this->em->getRepository('App:UserFrontManagement\UserCourseFollow')->getTotalTimeSecModule($user, $session, $module);
                $userModule->setDurationTotalSec($totalTimeSec);
                
                //pourcentage
                $totalPage = 0;
                foreach ($module->getModuleCourses() as $moduleCourse) {
                    // course isValid
                    if ($moduleCourse->getCourse()->getIsValid()) {
                        $number_page_valid = 0;
                        foreach ($moduleCourse->getCourse()->getPages() as $_page) {
                            // page isValid
                            if ($_page->getIsValid()) {
                                $number_page_valid++;
                            }
                        }
                        $totalPage += $number_page_valid;
                    }
                }
                $totalPageFollow = $this->em->getRepository('App:UserFrontManagement\UserPageFollow')->getTotalFollow($user, $session, $module);
                if ($totalPage == 0 || $totalPageFollow == 0) {
                    $percentage = 0;
                } else {
                    $percentage = round(($totalPageFollow) * 100 / $totalPage, 0, PHP_ROUND_HALF_UP);
                }
                //tacking
                $userModule->setDurationTotal($durationTotalM);
                $userModule->setLastConnexion(new \DateTime('now'));
                $userModule->setDurationLastSession(new \DateTime('00:00:00'));
                $userModule->setLastCourse($course);
                $userModule->setLastPage($page);

                if ($userModule->getPercentage() < $percentage && !$module->getIsScorm()) {
                    $userModule->setPercentage($percentage);
                }
                if ($percentage == 100) {
                    if ($userModule->getLectureDate() == null) {
                        $userModule->setLectureDate(new \DateTime('now'));
                    }
                    $userModule->setLectureDone(true);
                }
                $this->em->persist($userModule);
                $this->em->flush();

                if ($userModule->getEndDate() == null && $userModule->getPercentage() == 100 && $module->getType()->getConditional() != 'notFollow') {
                    $this->checkValidationModule($session, $module, $user, $userFormation, $userModule);
                }
            } catch (\Doctrine\DBAL\DBALException $e) {
                $this->logger->err($e->getMessage());
            } catch (\Exception $e) {
                $this->logger->err($e->getMessage());
            }

            return;
        }
    }

    /** get progression from suspend data
     * 1;1:1|2:0|3:0|4:0|5:0|6:0|7:0|8:0|9:0|10:0|11:0|12:0|13:0|14:0|15:0|16:0|17:0|18:0|19:0|20:0|21:0|22:0|23:0|24:0|25:0_-et-_{_-ap-_75_-ap-_:-1}
     *
     * @param $suspendData
     * @return float|int
     */
    private function progression($suspendData)
    {
        $this->logger->err('progression');
        $page_view = 0;
        if (!empty($suspendData)) {
            $step1 = explode('_', $suspendData);
            if (!empty($step1[0])) {
                $step2 = explode(';', $step1[0]);
                if (isset($step2[1])) {
                    $pages = explode('|', $step2[1]); //1:1
                    foreach ($pages as $page) {
                        $p = explode(':', $page);
                        $page_view += $p[1];
                    }

                    return round($page_view * 100 / count($pages), 0, PHP_ROUND_HALF_UP);
                }
            }
        }

        return 0;
    }

    /**
     * @param Session $session
     * @param Module $module
     * @param User $user
     * @param UserFormationSessionFollow $userFormation
     * @param UserModuleFollow $userModule
     * @param null $test
     * @param null $userTest
     */
    private function checkValidationModule(Session $session, Module $module, User $user, UserFormationSessionFollow $userFormation, UserModuleFollow $userModule, $test = null, $userTest = null)
    {
        $isModuleValid = false;
        $result = [];
        foreach ($module->getValidationModes() as $validationMode) {
            $result[$validationMode->getConditional()] = null;
        }
        $today = new \DateTime('now');

        //result
        if ($today > $session->getClosingDate() && $userModule->getEndDate() == null) {
            $userModule->setSuccess(false);
            $userModule->setEndDate(new \DateTime('now'));
            try {
                $this->em->persist($userModule);
                $this->em->flush();
            } catch (\Doctrine\DBAL\DBALException $e) {
                $this->logger->err($e->getMessage());
            } catch (\Exception $e) {
                $this->logger->err($e->getMessage());
            }

            return;
        } elseif ($today < $session->getClosingDate() && $userModule->getEndDate() == null) {
            if (array_key_exists('eval', $result)) {
                if ($test != null && $userTest != null && $test->getTypeTest()->getConditional() == 'eval') {
                    if ($userTest->getDatePass() != null) {
                        $userModule->setSuccess(true);
                        $userModule->setEndDate(new \DateTime('now'));
                        $userModule->setScore($userTest->getScore());
                    } elseif ($userTest->getTentative() == $userTest->getNumberTry()) {
                        $userModule->setSuccess(false);
                        $userModule->setEndDate(new \DateTime('now'));
                        $userModule->setScore($userTest->getScore());
                    }
                } elseif (array_key_exists('pre-test-valid', $result) && $userModule->getPreTestDone() == true) {
                    if ($test != null && $userTest != null && $test->getTypeTest()->getConditional() == 'pretest') {
                        if ($userTest->getDatePass() != null) {
                            $userModule->setSuccess(true);
                            $userModule->setEndDate(new \DateTime('now'));
                            $userModule->setScore($userTest->getScore());
                        }
                    }
                }
            } else {
                if (array_key_exists('lecture', $result) && $userModule->getLectureDone() == true) {
                    $userModule->setSuccess(true);
                    $userModule->setEndDate(new \DateTime('now'));
                }
            }
            try {
                $this->em->persist($userModule);
                $this->em->flush();
            } catch (\Doctrine\DBAL\DBALException $e) {
                $this->logger->err($e->getMessage());
            } catch (\Exception $e) {
                $this->logger->err($e->getMessage());
            }
            if ($userModule->getSuccess() == true) {
                $this->certificatManager->createCertificat($userFormation, $userModule, $user);
            }

            return;
        } else {
            return;
        }

        return;
    }

    /**
     * @param Session $session
     * @param Module $module
     * @param UserTest $userTest
     * @param Test $test
     * @param User $user
     */
    public function checkValidationTest(Session $session, Module $module, UserTest $userTest, Test $test, User $user)
    {
        if ($test->getTypeTest()->getConditional() != 'sondage') {
            $moduleTest = $this->em->getRepository('App:FormationManagement\ModuleTest')->findOneBy(['module' => $module, 'test' => $test]);
            $answeredQuestions = $this->em->getRepository('App\Entity\UserFrontManagement\UserQuestion')->findBy(['userTest' => $userTest]);
            $userScore = 0;
            $scoreMax = 0;

            foreach ($answeredQuestions as $answeredQuestion) {
                if ($answeredQuestion->getScored()) {
                    $userScore = $userScore + $answeredQuestion->getQuestion()->getWeight();
                }
                $scoreMax = $scoreMax + $answeredQuestion->getQuestion()->getWeight();
            }
            // CHECK SCORE FOR VALIDATION
            if ($scoreMax > 0) 
                $percentagePerso = floor(($userScore / $scoreMax) * 100);
            else 
                $percentagePerso = 0;
            
            if ($percentagePerso >= $moduleTest->getScore()) {
                $userTest->setDatePass(new \DateTime());
            }
            $userTest->setScore($percentagePerso);
            $this->em->persist($userTest);

            // update Module
            $userModule = $this->em->getRepository('App:UserFrontManagement\UserModuleFollow')->findOneBy(['module' => $module, 'session' => $session, 'user' => $user]);
            if ($test->getTypeTest()->getConditional() == 'pretest') {
                if ($userTest->getDatePass() != null) {
                    $userModule->setPreTestDone(true);
                } else {
                    $userModule->setPreTestDone(false);
                }
                if ($userModule->getStartDate() == null) {
                    $userModule->setStartDate(new \DateTime('now'));
                }
            }
            $validationModes = $module->getValidationModes();
            $result = [];
            foreach ($validationModes as $validationMode) {
                $result[$validationMode->getConditional()] = null;
            }
            $today = new \DateTime('now');
            if ($today < $session->getClosingDate() && $userModule->getEndDate() == null) {
                if (array_key_exists('eval', $result)) {
                    if ($test->getTypeTest()->getConditional() == 'eval') {
                        if ($userTest->getDatePass() != null) {
                            $userModule->setValidationDate(new \DateTime('now'));
                        }
                    } elseif (array_key_exists('pre-test-valid', $result) && $userModule->getPreTestDone() == true) {
                        if ($test->getTypeTest()->getConditional() == 'pretest') {
                            if ($userTest->getDatePass() != null) {
                                $userModule->setValidationDate(new \DateTime('now'));
                            }
                        }
                    }
                }
            }

            $this->em->persist($userModule);
            $this->em->flush();
            // formation
            $userFormation = $this->em->getRepository('App:UserFrontManagement\UserFormationSessionFollow')->findOneBy(['session' => $session, 'user' => $user]);

            if (!is_object($userFormation)) {
                $userFormation = new UserFormationSessionFollow();
                $userFormation->setFormation($session->getFormationPath());
                $userFormation->setRefFormation($session->getFormationPath()->getSlug());
                $userFormation->setSession($session);
                $userFormation->setRefSession($session->getReference());
                //tracking
                $userFormation->setUser($user);
                $userFormation->setStartDate(new \DateTime('now'));
                $userFormation->setDurationTotal(new \DateTime('00:00:00')); // DateTime Object
                $userFormation->setDurationLastSession(new \DateTime('00:00:00')); // DateTime Object
                $userFormation->setLastConnexion(new \DateTime('now'));
                $userFormation->setLastModule($module);
                $userFormation->setLastCourse(null);
                $userFormation->setLastPage(null);
                $userFormation->setPercentage(0);
                //score / progress
                $userFormation->setSuccess(false);
                $userFormation->setScore(null);
                $this->em->persist($userFormation);
                $this->em->flush();
            }

            if ($userModule->getEndDate() == null) {
                $this->checkValidationModule($session, $module, $user, $userFormation, $userModule, $test, $userTest);
            }
        }
    }

    /**
     * @param Module $module
     * @param Session $session
     * @param User $user
     * @return UserModuleFollow
     */
    private function createUserModuleFollow(Module $module, Session $session, User $user)
    {
        $userModule = new UserModuleFollow();
        $userModule->setModule($module);
        $userModule->setRefModule($module->getReference());
        $userModule->setSession($session);
        //tracking
        $userModule->setUser($user);
        $userModule->setModuleVersion($module->getVersion());
        $userModule->setStartDate(null);
        $userModule->setDurationTotal(new \DateTime('00:00:00')); // datetime object
        $userModule->setDurationLastSession(new \DateTime('00:00:00')); // datetime object
        $userModule->setLastConnexion(null);
        $userModule->setLastCourse(null);
        $userModule->setLastPage(null);
        $userModule->setPercentage(0);
        //score
        if ($module->getType()->getConditional() != 'notFollow') {
            $userModule->setSuccess(null);
            $userModule->setScore(null);
        } elseif ($module->getType()->getConditional() == 'notFollow') {
            $userModule->setSuccess(true);
            $userModule->setScore(0);
        } else {
            $userModule->setSuccess(null);
            $userModule->setScore(null);
        }

        return $userModule;
    }
}
