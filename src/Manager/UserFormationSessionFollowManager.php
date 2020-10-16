<?php

namespace App\Manager;

use App\Entity\FormationManagement\Course;
use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\Page;
use App\Entity\FormationManagement\ScoTracking;
use App\Entity\PlanningManagement\Session;
use App\Entity\UserFrontManagement\UserFormationSessionFollow;
use App\Entity\UserManagement\User;
use App\Persistence\ObjectManager;
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * UserFormationSessionFollowManager
 *
 * @author null
 */
class UserFormationSessionFollowManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    protected $logger;
    protected $sfSession;
    protected $translator;
    protected $attestationManager;

    /**
     * UserFormationSessionFollowManager constructor.
     * @param EntityManager $em
     * @param Logger $logger
     */
    public function __construct(ObjectManager $em, LoggerInterface $logger, SessionInterface $sfSession, TranslatorInterface $translator, AttestationManager $attestationManager)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->sfSession = $sfSession;
        $this->translator = $translator;
        $this->attestationManager = $attestationManager;
    }

    /**
     * @param Session $session
     */
    public function createUserSessionsFormation(Session $session)
    {
        foreach ($session->getUsers() as $user) {
            $userFormationSessionFollow = $this->em->getRepository('App:UserFrontManagement\UserFormationSessionFollow')->findOneBy(['session' => $session, 'user' => $user]);

            if (is_object($userFormationSessionFollow) && $userFormationSessionFollow instanceof UserFormationSessionFollow) {
                $userFormationSessionFollow->setIsDeleted(false);
            } else {
                $userFormationSessionFollow = $this->createUserSessionsFormationFollow($session, $user);
            }

            try {
                $this->em->persist($userFormationSessionFollow);
                $this->em->flush();
            } catch (\Doctrine\DBAL\DBALException $e) {
                $this->sfSession->getFlashBag()->add('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->sfSession->getFlashBag()->add('error', $e->getMessage());
            }
        }

        return;
    }

    /**
     * @param Session $session
     * @param Module $module
     * @param User $user
     * @param ScoTracking $scoTracking
     */
    public function updateUserSessionsFormationScorm(Session $session, Module $module, User $user, ScoTracking $scoTracking): void
    {
        $this->logger->err('updateUserSessionsFormationScorm');
        $userFormation = $this->em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findOneBy(['session' => $session, 'user' => $user]);
        if ($userFormation->getEndDate() == null) {
            try {
                $this->logger->err('is_object(userFormation');
                $duration_total_secondes = strtotime($userFormation->getDurationTotal()->format('H:i:s')) - strtotime('TODAY') + $scoTracking->getSessionTime();
                $duration_total = gmdate('H:i:s', $duration_total_secondes);

                $totalTimeSec = $this->em->getRepository('App:UserFrontManagement\UserModuleFollow')->getTotalTimeSecSession($user, $session);
                $userFormation->setDurationTotalSec($totalTimeSec);

                //pourcentage ???
                $totalPercentage = 0;
                $percentageScore = 0;
                $formation_success = true;
                $countModule = 0;
                $countModuleWithProgress = 0;
                $countModuleWithPEval = 0;
                $formationModules = $session->getFormationPath()->getFormationPathModules();

                foreach ($formationModules as $formationModule) {
                    if ($formationModule->getModule()->getType()->getConditional()!='notFollow') {
                        $countModule++;
                        $userModule = $this->em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')->findOneBy(
                            ['user' => $user,
                            'module' => $formationModule->getModule(),
                            'session' => $session]
                        );
                        if (is_object($userModule) ) {
                            if($userModule->getModule()->getType()->getConditional()!='presentiel'){
                                $countModuleWithProgress ++;
                                $totalPercentage += $userModule->getPercentage();
                            }
                            if($userModule->getModule()->getEvaluation()){
                                $percentageScore += $userModule->getScore();
                                $countModuleWithPEval ++;
                            }
                            if (!$userModule->getSuccess()) {
                                $formation_success = false;
                            }
                        }
                    }
                }

                if ($countModuleWithProgress != 0) {
                    $percentage = round($totalPercentage / $countModuleWithProgress, 0, PHP_ROUND_HALF_UP);
                } else {
                    $percentage = 0;
                }
                if ($countModuleWithPEval != 0) {
                    $score = round($percentageScore / $countModuleWithPEval, 0, PHP_ROUND_HALF_UP);
                } else {
                    $score = 0;
                }

                //tacking
                $userFormation->setLastConnexion(new \DateTime('now'));
                $userFormation->setDurationTotal(new \DateTime($duration_total));
                $duration_last_session = gmdate('H:i:s', $scoTracking->getSessionTime());

                if (is_int($scoTracking->getSessionTime())) {
                    $userFormation->setDurationLastSession(new \DateTime($duration_last_session));
                }

                $userFormation->setLastModule($module);
                if ($userFormation->getStartDate() == null) {
                    $userFormation->setStartDate(new \DateTime('now'));
                }
                $userFormation->setLastCourse(null);
                $userFormation->setLastPage(null);
                $userFormation->setPercentage($percentage);
                $userFormation->setScore($score);

                // result
                //if ('passed' === $scoTracking->getLessonStatus()) {
                    if ($formation_success == true && $percentage >= 99) {
                        //var_dump($scoTracking->getLessonStatus());
                        $userFormation->setSuccess(true);
                        $userFormation->setEndDate(new \DateTime('now'));
                        $this->attestationManager->createAttestation($userFormation, $user);
                    }
                //}

                $this->em->persist($userFormation);
                $this->em->flush();

                if ($userFormation->getSuccess()) {
                    $this->sfSession->getFlashBag()->add('success', $this->translator->trans(
                        'userFrontManagement.formation.success',
                        ['%formation%' => $userFormation->getFormation()->getTitle()
                        ]
                    ));
                }
            } catch (DBALException $e) {
                $this->logger->err($e->getMessage());
                $this->sfSession->getFlashBag()->add('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->logger->err($e->getMessage());
                $this->sfSession->getFlashBag()->add('error', $e->getMessage());
            }
        }
    }

    /**
     * @param Session $session
     * @param Module $module
     * @param Course $course
     * @param Page $page
     * @param User $user
     */
    public function updateUserSessionsFormation(Session $session, Module $module, Course $course = null, Page $page = null, User $user)
    {
        $this->logger->err('updateUserSessionsFormation');
        $userFormation = $this->em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findOneBy(['session' => $session, 'user' => $user]);
        if ($userFormation->getEndDate() == null) {
            try {
                $this->logger->err('is_object(userFormation');
                if ($userFormation->getDurationTotal()) {
                    $newTotDurationF = $userFormation->getDurationTotal()->getTimestamp() + 1;
                    $durationTotalF = new \DateTime();
                    $durationTotalF->setTimestamp($newTotDurationF);
                } else {
                    $durationTotalF = new \DateTime('00:00:00');
                }

                $totalTimeSec = $this->em->getRepository('App:UserFrontManagement\UserModuleFollow')->getTotalTimeSecSession($user, $session);
                $userFormation->setDurationTotalSec($totalTimeSec);

                $totalPercentage = 0;
                $countModule = 0;
                $countModuleWithProgress = 0;
                $countModuleWithPEval = 0;
                $score = 0;
                foreach ($session->getFormationPath()->getFormationPathModules() as $Fmodule) {
                    // user module follow
                    if ($Fmodule->getModule()->getType()->getConditional()!='notFollow') {
                        $countModule++;
                        $userModuleFollow = $this->em->getRepository('App:UserFrontManagement\UserModuleFollow')->findOneBy([
                            'user' => $user,
                            'module' => $Fmodule->getModule(),
                            'session' => $session]);

                        if ($userModuleFollow) {
                            if($userModuleFollow->getModule()->getType()->getConditional()!='presentiel'){
                                $totalPercentage += $userModuleFollow->getPercentage();
                                $countModuleWithProgress++;
                            }
                            if($userModuleFollow->getModule()->getEvaluation()){
                                $score += $userModuleFollow->getScore();
                                $countModuleWithPEval ++;
                            }
                            
                        }
                    }
                }
                if ($countModuleWithProgress != 0) {
                    $percentage = round($totalPercentage / $countModuleWithProgress, 0, PHP_ROUND_HALF_UP);
                } else {
                    $percentage = 0;
                }
                if ($countModuleWithPEval != 0) {
                    $percentageScore = round($score / $countModuleWithPEval, 0, PHP_ROUND_HALF_UP);
                } else {
                    $percentageScore = 0;
                }

                //tacking
                $userFormation->setLastConnexion(new \DateTime('now'));
                $userFormation->setDurationTotal($durationTotalF);
                $userFormation->setDurationLastSession(new \DateTime('00:00:00'));
                $userFormation->setLastModule($module);
                $userFormation->setScore($percentageScore);
                $userFormation->setPercentage($percentage);
                if ($userFormation->getStartDate() == null) {
                    $userFormation->setStartDate(new \DateTime('now'));
                }
                if ($course != null) {
                    $userFormation->setLastCourse($course);
                }
                if ($course != null) {
                    $userFormation->setLastPage($page);
                }

                $this->em->persist($userFormation);
                $this->em->flush();

                if ($userFormation->getSuccess()) {
                    $this->sfSession->getFlashBag()->add('success', $this->translator->trans(
                        'userFrontManagement.formation.success',
                        ['%formation%' => $userFormation->getFormation()->getTitle()
                        ]
                    ));
                }

                if ($userFormation->getEndDate() == null) {
                    $this->checkValidationFormation($session, $user, $userFormation);
                }
            } catch (DBALException $e) {
                $this->logger->err($e->getMessage());
                $this->sfSession->getFlashBag()->add('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->logger->err($e->getMessage());
                $this->sfSession->getFlashBag()->add('error', $e->getMessage());
            }
        }
    }

    /**
     * @param Session $session
     * @param User $user
     * @param UserFormationSessionFollow $userFormation
     */
    public function checkValidationFormation(Session $session, User $user, UserFormationSessionFollow $userFormation)
    {
        $isFormationValid = false;
        $resultEnd = [];
        $modulesFollowEnd = $this->em->getRepository('App:UserFrontManagement\UserModuleFollow')->getTotalFollowEnd($user, $session);
        $nbModules = 0;
        foreach ($session->getFormationPath()->getFormationPathModules() as $Fmodule) {
            if ($Fmodule->getModule()->getType()->getConditional() != 'notFollow') {
                $nbModules++;
            }
        }
        $nbUserModules = 0;
        foreach ($modulesFollowEnd as $umodule) {
            if ($umodule->getModule()->getType()->getConditional() != 'notFollow') {
                $nbUserModules++;
            }
        }

        $today = new \DateTime('now');

        if ($nbUserModules == $nbModules) {
            //var_dump($modulesFollowEnd);
            foreach ($modulesFollowEnd as $uModule) {
                $resultEnd[$uModule->getId()] = $uModule->getSuccess();
            }
            //result
            if (in_array(false, $resultEnd) || $today > $session->getClosingDate()) {
                $userFormation->setSuccess(false);
                $userFormation->setEndDate(new \DateTime('now'));

                try {
                    $this->em->persist($userFormation);
                    $this->em->flush();
                } catch (\Doctrine\DBAL\DBALException $e) {
                    $this->logger->err($e->getMessage());
                    $this->sfSession->getFlashBag()->add('error', $e->getMessage());
                } catch (\Exception $e) {
                    $this->logger->err($e->getMessage());
                    $this->sfSession->getFlashBag()->add('error', $e->getMessage());
                }

                return;
            } elseif ($today < $session->getClosingDate() && !in_array(false, $resultEnd)) {
                $userFormation->setSuccess(true);
                $userFormation->setEndDate(new \DateTime('now'));
                try {
                    $this->em->persist($userFormation);
                    $this->em->flush();
                } catch (\Doctrine\DBAL\DBALException $e) {
                    $this->logger->err($e->getMessage());
                    $this->sfSession->getFlashBag()->add('error', $e->getMessage());
                } catch (\Exception $e) {
                    $this->logger->err($e->getMessage());
                    $this->sfSession->getFlashBag()->add('error', $e->getMessage());
                }
                $this->attestationManager->createAttestation($userFormation, $user);

                return;
            } else {
                return;
            }
        } elseif ($today > $session->getClosingDate()) {
            $userFormation->setSuccess(false);
            $userFormation->setEndDate(new \DateTime('now'));
            try {
                $this->em->persist($userFormation);
                $this->em->flush();
            } catch (\Doctrine\DBAL\DBALException $e) {
                $this->logger->err($e->getMessage());
                $this->sfSession->getFlashBag()->add('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->logger->err($e->getMessage());
                $this->sfSession->getFlashBag()->add('error', $e->getMessage());
            }

            return;
        } else {
            return;
        }
    }

    /**
     * @param Session $session
     * @param User $user
     * @return UserFormationSessionFollow
     */
    private function createUserSessionsFormationFollow(Session $session, User $user)
    {
        $userFormationSessionFollow = new UserFormationSessionFollow();
        $userFormationSessionFollow->setFormation($session->getFormationPath());
        $userFormationSessionFollow->setRefFormation($session->getFormationPath()->getSlug());
        $userFormationSessionFollow->setSession($session);
        $userFormationSessionFollow->setRefSession($session->getReference());
        //tracking
        $userFormationSessionFollow->setUser($user);
        $userFormationSessionFollow->setStartDate(null);
        $userFormationSessionFollow->setDurationTotal(new \DateTime('00:00:00')); // DateTime Object
        $userFormationSessionFollow->setDurationLastSession(new \DateTime('00:00:00')); // DateTime Object
        $userFormationSessionFollow->setLastConnexion(null);
        $userFormationSessionFollow->setLastModule(null);
        $userFormationSessionFollow->setLastCourse(null);
        $userFormationSessionFollow->setLastPage(null);
        $userFormationSessionFollow->setPercentage(0);
        //score / progress
        $userFormationSessionFollow->setSuccess(false);
        $userFormationSessionFollow->setScore(null);

        return $userFormationSessionFollow;
    }
}
