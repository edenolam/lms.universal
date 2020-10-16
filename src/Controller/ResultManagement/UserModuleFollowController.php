<?php

namespace App\Controller\ResultManagement;

use App\Entity\TestManagement\Test;
use App\Entity\UserFrontManagement\UserModuleFollow;
use App\Entity\UserFrontManagement\UserTest;
use App\Form\UserFrontManagement\UserModuleFollowType;
use App\Manager\AuditManager;
use App\Manager\CertificatManager;
use App\Manager\UserFormationSessionFollowManager;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/bilan/userModuleFollow")
 */
class UserModuleFollowController extends AbstractController
{
    /**
     * @Route("/{id}/edit", name="results_user_module_follow_edit", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_BILANS')")
     */
    public function edit(Request $request, UserModuleFollow $userModuleFollow, CertificatManager $certificatManager, UserFormationSessionFollowManager $userFormationSessionFollowManager, SessionInterface $sfSession, AuditManager $auditManager): Response
    {
        $em = $this->getDoctrine()->getManager();

        if ($this->get('security.authorization_checker')->isGranted('ROLE_RESPONSABLE_FORMATION')) {
            $aprenantFolow = $em->getRepository('App\Entity\UserManagement\User')->findApprenantFollowBy($this->getUser(), 'respoFormation', null);
            $tutorFollow = null;
            $role = 'respoFormation';
        } else {
            $aprenantFolow = $em->getRepository('App\Entity\UserManagement\User')->findApprenantFollowBy($this->getUser(), 'tuteur', null);
            $role = 'tuteur';
        }

        if (!in_array($userModuleFollow->getUser(), $aprenantFolow)) {
            throw new AccessDeniedHttpException('Access Denied');
        }
        $old_userModuleFollow = clone $userModuleFollow;
        $form = $this->createForm(UserModuleFollowType::class, $userModuleFollow);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $Test = $em->getRepository('App\Entity\TestManagement\Test')->findOneBy(['id' => $userModuleFollow->getModule()->getModuleEvaluationId()]);
            if ($userModuleFollow->getSuccess() == true &&( $Test == null || ($Test != null && !$Test->getIsTestPresentiel()))) {
                $userModuleFollow->setValidationDate($userModuleFollow->getEndDate());
                $em->persist($userModuleFollow);
                $em->flush();
            }

            $auditManager->generateAudit($old_userModuleFollow, $userModuleFollow, 'edit', $this->getUser());
            $userFormationFollow = $em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findOneBy(['session' => $userModuleFollow->getSession(), 'user' => $userModuleFollow->getUser()]);

            if ($userModuleFollow->getSuccess() == true &&( $Test == null || ($Test != null && !$Test->getIsTestPresentiel()))) {
                $certificatManager->createCertificat($userFormationFollow, $userModuleFollow, $userModuleFollow->getUser());
            }

            $userFormationSessionFollowManager->updateUserSessionsFormation($userModuleFollow->getSession(), $userModuleFollow->getModule(), null, null, $userModuleFollow->getUser());

            $sfSession->getFlashBag()->add('success', '');
        }

        return $this->render('ResultManagement/UserModuleFollow/edit.html.twig', [
            'user_module_follow' => $userModuleFollow,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/csv/import", name="results_user_module_follow_csv", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_BILANS')")
     */
    public function csvImport(Request $request, SessionInterface $sfSession, LoggerInterface $logger): Response
    {
        return $this->render('ResultManagement/UserModuleFollow/edit_csv.html.twig', [
        ]);
    }

    /**
     * @Route("/csv/check", name="results_user_module_follow_csv_check", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_BILANS')")
     */
    public function checkCSVImport(Request $request, SessionInterface $sfSession, LoggerInterface $logger, AuditManager $auditManager, CertificatManager $certificatManager, UserFormationSessionFollowManager $userFormationSessionFollowManager)
    {
        $csv = $request->files->get('csv');
        $info = [];
        if ($csv) {
            try {
                //load file on base
                $csv = $request->files->get('csv');
                // $target_dir = "uploads/modulePresentiels";
                // $target_file = $target_dir . basename($csv);
                // $reulstUpload = move_uploaded_file(basename($csv),$target_dir);
                // var_dump($reulstUpload);
                // exit();
                // $csv->move($target_dir, $csv);
                //load data file
                $info = $this->loadUserModulePresentiels($csv, $auditManager, $certificatManager, $userFormationSessionFollowManager);
            } catch (Exception $e) {
                $sfSession->getFlashBag()->add('error', $e->getMessage());
            }
        }

        return $this->render('ResultManagement\UserModuleFollow\edit_csv.html.twig', [
            'info' => $info,
        ]);
    }

    protected function loadUserModulePresentiels($csv, AuditManager $auditManager, CertificatManager $certificatManager, UserFormationSessionFollowManager $userFormationSessionFollowManager)
    {
        $em = $this->getDoctrine()->getManager();
        $handle = fopen($csv->getRealPath(), 'r');
        $i = 1;
        $info = [];
        $info['evalrequired'] = $info['evalAdd'] = $info['evalfailedAdd'] = $info['moduleFollow'] = $info['moduleFollowAdd'] = $info['moduleFollowfailedAdd'] = 0;
        $info['data']['module'] = [];
        $info['data']['apprenant'] = [];
        $info['data']['session'] = [];
        $info['data']['umf'] = [];
        while (($data = fgetcsv($handle, null, ';')) !== false) {
            if ($i != 1) {
                $module = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBy(['slug' => $data[8], 'regulatoryRef' => mb_convert_encoding($data[9], "UTF-8", "HTML-ENTITIES"), 'title' => mb_convert_encoding($data[10], "UTF-8", "HTML-ENTITIES")]);

                if ($module) {
                    $apprenant = $em->getRepository('App\Entity\UserManagement\User')->findOneBy(['username' => $data[1], 'firstname' => mb_convert_encoding($data[2], "UTF-8", "HTML-ENTITIES"), 'lastname' => mb_convert_encoding($data[3], "UTF-8", "HTML-ENTITIES")]);
                    if ($apprenant) {
                        $session = $em->getRepository('App\Entity\PLanningManagement\Session')->findOneBy(['slug' => $data[4], 'title' => mb_convert_encoding($data[5], "UTF-8", "HTML-ENTITIES")]);
                        if ($session) {
                            $umf = $em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')->findOneBy([
                                'id' => str_replace('PRES_', '', $data[0]),
                                'session' => $session,
                                'module' => $module,
                                'user' => $apprenant,
                            ]);

                            //update user module follow
                            if ($umf != null) {
                                ++$info['moduleFollow'];

                                try {
                                    $old_userModuleFollow = clone $umf;
                                    $user = $this->getUser();
                                    $umf->setSuccess($data[11]);
                                    $umf->setDurationTotalSec($data[12]);
                                    $umf->setStartDate(new \Datetime($data[13]));
                                    $umf->setEndDate(new \Datetime($data[14]));
                                    $em->persist($umf);
                                    $em->flush();
                                    $auditManager->generateAudit($old_userModuleFollow, $umf, 'edit', $this->getUser());
                                    ++$info['moduleFollowAdd'];
                                } catch (Exception $e) {
                                    ++$info['moduleFollowfailedAdd'];
                                }
                                //update user test présentiel
                                if ($umf->getModule()->getModuleEvaluation() && $umf->getModule()->getModuleEvaluation()->getIsTestPresentiel()) {
                                    ++$info['evalrequired'];
                                    $utf = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findOneBy([
                                        'session' => $session,
                                        'test' => $umf->getModule()->getModuleEvaluation(),
                                        'user' => $apprenant,
                                        'id' => str_replace('TEST_', '', $data[15]),
                                    ]);

                                    try {
                                        if (!$utf) {
                                            $utf = new UserTest();
                                            $utf->setSession($session);
                                            $utf->setUser($apprenant);
                                            $utf->setRefModule($umf->getModule()->getReference());
                                            $utf->setRefFormation('FnÂ°' . $session->getFormationPath()->getId());
                                            $utf->setTest($umf->getModule()->getModuleEvaluation());
                                            $old_utf = null;
                                            $action = 'add';
                                        } else {
                                            $old_utf = clone $utf;
                                            $action = 'edit';
                                        }
                                        $utf->setNumberTry($data[16]);
                                        $utf->setTentative($data[16]);
                                        $utf->setScore($data[17]);
                                        $utf->setDatePass(new \Datetime($data[18]));
                                        $utf->setDateDown(new \Datetime($data[19]));
                                        $em->persist($utf);

                                        $umf->setScore($utf->getScore());
                                        $umf->setValidationDate($utf->getDatePass());
                                        $em->persist($umf);
                                        $auditManager->generateAudit($old_userModuleFollow, $umf, 'edit', $this->getUser());

                                        $em->flush();
                                        $auditManager->generateAudit($old_utf, $utf, $action, $this->getUser());
                                        ++$info['evalAdd'];
                                    } catch (Exception $e) {
                                        ++$info['evalfailedAdd'];
                                    }
                                }

                                $userFormationFollow = $em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findOneBy(['session' => $umf->getSession(), 'user' => $umf->getUser()]);
                                if ($umf->getSuccess() == true) {
                                    $certificatManager->createCertificat($userFormationFollow, $umf, $umf->getUser());
                                }
                    
                                $userFormationSessionFollowManager->updateUserSessionsFormation($umf->getSession(), $umf->getModule(), null, null, $umf->getUser());
                            } else {
                                $info['data']['umf'][$data[0]] = $i;
                            }
                        } else {
                            $info['data']['session'][$data[0]] = $i;
                        }
                    } else {
                        $info['data']['apprenant'][$data[0]] = $i;
                    }
                } else {
                    $info['data']['module'][$data[0]] = $i;
                }
            }
            
            $i++;
        }
        fclose($handle);

        return $info;
    }
}
