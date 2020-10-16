<?php

namespace App\Controller\ResultManagement;

use App\Entity\PlanningManagement\Session;
use App\Entity\TestManagement\Test;
use App\Entity\UserFrontManagement\UserModuleFollow;
use App\Entity\UserFrontManagement\UserTest;
use App\Entity\UserManagement\User;
use App\Form\UserFrontManagement\UserTestType;
use App\Manager\AuditManager;
use App\Manager\CertificatManager;
use App\Manager\UserFormationSessionFollowManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/bilan/user_test")
 */
class UserTestController extends AbstractController
{
    /**
     * @Route("/edit/{session}/{test}/{user}/{userModuleFollow}", name="results_user_test_edit", methods={"GET","POST"})
     * *
     * @Security("has_role('ROLE_GESTION_BILANS')")
     */
    public function edit(Request $request, Session $session, Test $test, User $user, UserModuleFollow $userModuleFollow, CertificatManager $certificatManager, UserFormationSessionFollowManager $userFormationSessionFollowManager, SessionInterface $sfSession, AuditManager $auditManager): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        if ($this->get('security.authorization_checker')->isGranted('ROLE_RESPONSABLE_FORMATION')) {
            $aprenantFolow = $entityManager->getRepository('App\Entity\UserManagement\User')->findApprenantFollowBy($this->getUser(), 'respoFormation', null);
            $tutorFollow = null;
            $role = 'respoFormation';
        } else {
            // $level = $this->getUser()->getHierarchyLevel() - 1;
            // $tutorFollow = array();
            // $tutors = null;
            // for ($i = $level; $i > 0; $i--) {
            //     $tutors = $entityManager->getRepository('App\Entity\UserManagement\User')->findTutorFollowBy($tutors, $this->getUser(), $level);

            //     foreach ($tutors as $tutor) {
            //         array_push($tutorFollow, $tutor);
            //     }
            // }
            $tutorFollow = $entityManager->getRepository('App\Entity\UserManagement\User')->getFollowTutors($this->getUser(), 'tuteur');
            $aprenantFolow = $entityManager->getRepository('App\Entity\UserManagement\User')->findApprenantFollowBy($this->getUser(), 'tuteur', $tutorFollow);
            $role = 'tuteur';
        }

        if (!in_array($userModuleFollow->getUser(), $aprenantFolow)) {
            throw new AccessDeniedHttpException('Access Denied');
        }
        $userTest = $entityManager->getRepository('App\Entity\UserFrontManagement\UserTest')->findOneBy([
            'session' => $session,
            'test' => $test,
            'user' => $user,
        ]);

        if (!$userTest) {
            $userTest = new UserTest();
            $userTest->setSession($session);
            $userTest->setUser($user);
            $userTest->setRefModule($userModuleFollow->getModule()->getReference());
            $userTest->setRefFormation('FnÂ°' . $session->getFormationPath()->getId());
            $userTest->setTest($test);
            $moduleTest = $entityManager->getRepository('App\Entity\FormationManagement\ModuleTest')->findOneBy([
                    'module' => $userModuleFollow->getModule(),
                    'test' => $test
                ]);
            $userTest->setNumberTry($moduleTest->getNumberTry());
            $old_userTest = null;
            $action = 'add';
        } else {
            $old_userTest = clone $userTest;
            $action = 'edit';
        }

        $form = $this->createForm(UserTestType::class, $userTest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($userTest);
            $entityManager->flush();
            $auditManager->generateAudit($old_userTest, $userTest, $action, $this->getUser());
            
            $old_userModuleFollow = clone $userModuleFollow;
            $userModuleFollow->setScore($userTest->getScore());
            $userModuleFollow->setValidationDate($userTest->getDatePass());
            $entityManager->persist($userModuleFollow);
            $auditManager->generateAudit($old_userModuleFollow, $userModuleFollow, 'edit', $this->getUser());
            $entityManager->flush();

            $userFormationFollow = $entityManager->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findOneBy(['session' => $userModuleFollow->getSession(), 'user' => $userModuleFollow->getUser()]);

            if ($userModuleFollow->getSuccess() == true) {
                $certificatManager->createCertificat($userFormationFollow, $userModuleFollow, $userModuleFollow->getUser());
            }

            $userFormationSessionFollowManager->updateUserSessionsFormation($userModuleFollow->getSession(), $userModuleFollow->getModule(), null, null, $userModuleFollow->getUser());

            $sfSession->getFlashBag()->add('success', '');

            //return $this->redirectToRoute('results_list');
        }

        //$session_modules = $entityManager->getRepository('App\Entity\PlanningManagement\SessionFormationPathModule')->findBy(array('session' => $userModuleFollow->getSession()));

        return $this->render('ResultManagement/UserTest/edit.html.twig', [
            'user_module_follow' => $userModuleFollow,
            'user_test' => $userTest,
            'session' => $session,
            'test' => $test,
            'user' => $user,
            'form' => $form->createView(),
            //'session_modules' => $session_modules
        ]);
    }
}
