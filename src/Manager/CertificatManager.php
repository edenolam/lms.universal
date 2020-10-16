<?php

namespace App\Manager;

use App\Entity\ResultManagement\Certificat;
use App\Entity\UserFrontManagement\UserFormationSessionFollow;
use App\Entity\UserFrontManagement\UserModuleFollow;
use App\Entity\UserManagement\User;
use App\Persistence\ObjectManager;
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * CertificatManager
 *
 * @author null
 */
class CertificatManager
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

    public function createCertificat(UserFormationSessionFollow $userFormation, UserModuleFollow $userModule, User $user)
    {
        if ($userModule->getSuccess() == true) {
            $today = new \DateTime('now');
            $certificat = $this->em->getRepository('App\Entity\ResultManagement\Certificat')->findOneBy(
                ['userModuleFollow' => $userModule,
                 'user' => $user]
            );
            if (!$certificat instanceof Certificat) {
                $certificat = new Certificat();
                $certificat->setUserModuleFollow($userModule);
                $certificat->setUser($user);
                $certificat->setSerialCode($userModule->getRefModule() . '_' . $today->format('Y_m') . '_N°' . $userModule->getId());
                $certificat->setFormationTitle($userFormation->getFormation()->getTitle());
                $certificat->setModuleTitle($userModule->getModule()->getTitle());
                $certificat->setModuleRef($userModule->getModule()->getRegulatoryRef());
                $certificat->setUserLitteralLastname($user->getLastname());
                $certificat->setUserLitteralFisrtname($user->getFirstname());
                if ($user->getLaboratory() != null && $user->getLaboratory()->getLogo() != null) {
                    $certificat->setUserLabLogUrl($user->getLaboratory()->getLogo());
                }
                if ($user->getLaboratory() != null && $user->getLaboratory()->getTitle() != null) {
                    $certificat->setUserLaboratory($user->getLaboratory()->getTitle());
                }
                $certificat->setCreateDate($today);
                $certificat->setOwnDownload(0);
                $certificat->setManagerDownload(0);
           
                $certificat->setValidationDate($userModule->getEndDate());

                $resut['success'] = true;
                if ($userModule->getScore() != null) {
                    $resut['score'] = $userModule->getScore() . '%';
                } else {
                    $resut['score'] = '';
                }
                if ($userModule->getLectureDone() == true) {
                    $resut['lecture'] = $userModule->getLectureDone();
                } else {
                    $resut['lecture'] = false;
                }
                if ($userModule->getPreTestDone() == true) {
                    $resut['lecture'] = $userModule->getPreTestDone();
                } else {
                    $resut['lecture'] = false;
                }
                $certificat->setResult($resut);

                $validationMode = [];
                foreach ($userModule->getModule()->getValidationModes() as $mode) {
                    $detail = [];
                    $testModule = null;
                    $detail['mode'] = $mode->getTitle();
                    if ($mode->getConditional() == 'pre-test-valid' || $mode->getConditional() == 'pre-test-non-valid') {
                        $testModule = $this->em->getRepository('App\Entity\FormationManagement\ModuleTest')->findByTestTypeANDModule($userModule->getModule(), 'pretest');
                    } elseif ($mode->getConditional() == 'eval') {
                        $testModule = $this->em->getRepository('App\Entity\FormationManagement\ModuleTest')->findByTestTypeANDModule($userModule->getModule(), 'eval');
                    }

                    $data = '';
                    if (isset($testModule)) {
                        if ($testModule->getScore() != null) {
                            $detail['score_needed'] = $testModule->getScore();
                        }
                        $detail['number_try'] = $testModule->getNumberTry();
                        $detail['chrono_question'] = $testModule->getChronoQuestion();
                        $detail['chrono_test'] = $testModule->getChronoTest();
                    } else {
                        $detail['NA'] = 'NA';
                    }
                    $validationMode[] = $detail;
                }
                $certificat->setValidationMode($validationMode);

                $this->em->persist($certificat);
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
