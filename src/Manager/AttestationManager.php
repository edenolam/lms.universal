<?php

namespace App\Manager;

use App\Entity\ResultManagement\Attestation;
use App\Entity\UserFrontManagement\UserFormationSessionFollow;
use App\Entity\UserManagement\User;
use App\Persistence\ObjectManager;
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * AttestationManager
 *
 * @author null
 */
class AttestationManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    protected $logger;
    protected $sfSession;
    protected $translator;

    /**
     * UserFormationSessionFollowManager constructor.
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

    public function createAttestation(UserFormationSessionFollow $userFormation, User $user)
    {
        if ($userFormation->getSuccess() == true) {
            $attestation = $this->em->getRepository('App\Entity\ResultManagement\Attestation')->findOneBy(
                ['UserSessionFollow' => $userFormation,
                 'user' => $user]
            );
            
            if (!$attestation instanceof Attestation) {
                $today = new \DateTime('now');
                $attestation = new Attestation();
                $attestation->setUserSessionFollow($userFormation);
                $attestation->setUser($user);
                $attestation->setSerialCode($userFormation->getRefSession() . '_' . $userFormation->getRefFormation() . '_' . $today->format('Y_m') . '_N°' . $userFormation->getId());
                $attestation->setFormationTitle($userFormation->getFormation()->getTitle());
                $attestation->setSession($userFormation->getSession());
                $attestation->setStartDate($userFormation->getSession()->getOpeningDate());
                $attestation->setEndDate($userFormation->getSession()->getClosingDate());
                $interval = $userFormation->getSession()->getOpeningDate()->diff($userFormation->getSession()->getClosingDate(),false);
                $attestation->setDurationSessionFormation($interval->format('%d j'));
                $attestation->setValidationDate($userFormation->getEndDate());
                $attestation->setUserLitteralLastname($user->getLastname());
                $attestation->setUserLitteralFirstName($user->getFirstname());
                if ($user->getLaboratory() != null && $user->getLaboratory()->getLogo() != null) {
                    $attestation->setUserLabLogoUrl($user->getLaboratory()->getLogo());
                }
                if ($user->getLaboratory() != null && $user->getLaboratory()->getTitle() != null) {
                    $attestation->setUserLaboratory($user->getLaboratory()->getTitle());
                }
                $attestation->setCreateDate($today);
                $attestation->setOwnDownload(0);
                $attestation->setManagerDownload(0);

                $userModules = $this->em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')->findBy(['session' => $userFormation->getSession(),'user'=>$user]);

                $moduleDetails = [];
                foreach ($userModules as $usermodule) {
                    if ($usermodule->getModule()->getType()->getConditional() != 'notFollow') {
                        $detail['module_name'] = $usermodule->getModule()->getTitle();

                        $eval = false;
                        $preTest = false;
                        foreach ($usermodule->getModule()->getValidationModes() as $mode) {
                            if ($mode->getConditional() == 'pre-test-valid') {
                                $preTest = true; //pre test validant
                            } elseif ($mode->getConditional() == 'eval') {
                                $eval = true; //eval
                            }
                        }
                        $moduletests = $usermodule->getModule()->getModuleTests();
                        $detail['test_eval'] = null;
                        $detail['test_pre'] = null;
                        foreach ($moduletests as $moduletest) {
                            if ($moduletest->getTest()->getTypeTest()->getConditional() == 'eval' && $eval == true) {
                                $detail['test_eval'] = $moduletest->getScore();
                            }
                            if ($moduletest->getTest()->getTypeTest()->getConditional() == 'pretest' && $preTest == true) {
                                $detail['test_pre'] = $moduletest->getScore();
                            }
                        }
                        $moduleCourses = $usermodule->getModule()->getModuleCourses();
                        foreach ($moduleCourses as $moduleCourse) {
                            $detail['courses'][] = $moduleCourse->getcourse()->getTitle();
                        }

                        $moduleDetails[] = $detail;
                    }
                }
                $attestation->setModuleDetails($moduleDetails);

                $this->em->persist($attestation);
            }
            try {
                $this->em->flush();
            } catch (DBALException $e) {
                $this->logger->err($e->getMessage());
            } catch (\Exception $e) {
                $this->logger->err($e->getMessage());
            }
        }
    }
}
