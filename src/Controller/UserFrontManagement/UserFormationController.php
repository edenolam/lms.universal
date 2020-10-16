<?php

namespace App\Controller\UserFrontManagement;

use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\Page;
use App\Entity\PlanningManagement\Session;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Html2Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/apprenant")
 */
class UserFormationController extends AbstractController
{
    /**
     * Fonction formation
     *
     * @Route("/formation", name="user_formation", methods="GET")
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function ViewMesformation(Request $request, $downFormation = false)
    {
        $em = $this->getDoctrine()->getManager();
        // Current user
        $user = $this->getUser();

        // Get all active formation for current user
        //$activeFormations =  $em->getRepository('App\Entity\FormationManagement\FormationPath')->findAllActiveFormation($user);
        $allSessionToFollow = $em->getRepository('App\Entity\PlanningManagement\Session')->findAllFormationSession($user);
        $overFormation = [];
        $today = new \DateTime();
        $formationWithSessionOpenButFinished = [];

        $formationArray = null;
        if (!empty($allSessionToFollow)) {
            $formationArray = [];
            foreach ($allSessionToFollow as $sessionToFollow) {
                $userFormation = $em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findOneBy(['user' => $user, 'formation' => $sessionToFollow->getFormationPath(), 'session' => $sessionToFollow]);
                if ($userFormation) {
                    if ($userFormation->getSuccess() || $userFormation->getEndDate() != null || $userFormation->getSession()->getClosingDate() < $today) {
                        $elem['session'] = $sessionToFollow;
                        $elem['formation'] = $sessionToFollow->getFormationPath();
                        $elem['userFormation'] = $userFormation;
                        array_push($overFormation, $elem);
                        if ($userFormation->getSession()->getClosingDate() > $today) {
                            array_push($formationWithSessionOpenButFinished, $userFormation->getFormation());
                        }
                    } else {
                        $elem['session'] = $sessionToFollow;
                        $elem['formation'] = $sessionToFollow->getFormationPath();
                        $elem['userFormation'] = $userFormation;
                        array_push($formationArray, $elem);
                    }
                } else {
                    if ($sessionToFollow->getClosingDate() < $today) {
                        $elem['session'] = $sessionToFollow;
                        $elem['formation'] = $sessionToFollow->getFormationPath();
                        $elem['userFormation'] = $userFormation;
                        array_push($overFormation, $elem);
                    } else {
                        if (!in_array($sessionToFollow->getFormationPath(), $formationWithSessionOpenButFinished)) {
                            $elem['session'] = $sessionToFollow;
                            $elem['formation'] = $sessionToFollow->getFormationPath();
                            $elem['userFormation'] = $userFormation;
                            array_push($formationArray, $elem);
                        }
                    }
                }
            }
        }

        $allFormations = $em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findBy([
            'user' => $user
        ]);

        // foreach($allFormations as $formation){
        // 			// IF DATE IS OVER
        // 	if($formation->getSession()->getClosingDate() < $today){
        // 		array_push($overFormation, $formation);
        // 	}
        // }

        // GET USER CERTIFICAT & ATTESTATION
        $certificats = $em->getRepository('App\Entity\ResultManagement\Certificat')->findBy([
            'user' => $user
        ]);
        $attestations = $em->getRepository('App\Entity\Resultmanagement\Attestation')->findBy([
            'user' => $user
        ]);
        $activeUserFormations = $em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findByActiveSession($user);
        $evalsCount = 0;
        foreach ($activeUserFormations as $activeFormation) {
            foreach ($activeFormation->getFormation()->getFormationPathModules() as $module) {
                foreach ($module->getModule()->getModuleTests() as $test) {
                    if ($test->getTest()->getTypeTest()->getConditional() == 'eval') {
                        $userModule = $em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')->findOneBy([
                            'user' => $user,
                            'module' => $module->getModule(),
                            'session' => $activeFormation->getSession()
                        ]);
                        if ($userModule == null) {
                            $evalsCount++;
                        } else {
                            if (!$userModule->getSuccess()) {
                                $evalsCount++;
                            }
                        }
                    }
                }
            }
        }

        return $this->render('UserFrontManagement/formation.html.twig', [
            'user' => $user,
            'activeFormations' => $formationArray,
            'overFormations' => $overFormation,
            'allforfo' => $allFormations,
            'certificats' => $certificats,
            'attestations' => $attestations,
            'evalsCount' => $evalsCount
        ]);
    }

    /**
     * Fonction module
     *
     * @Route("/formation/module/{slugSession}", name="user_formation_module", methods="GET")
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function ViewMesModuleFormation(Request $request, $slugSession = null)
    {
        $em = $this->getDoctrine()->getManager();

        //Current user
        $user = $this->getUser();

        if ($slugSession) {
            $currentSession = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy(['slug' => $slugSession]);
            $hasAccess = $em->getRepository('App\Entity\FormationManagement\FormationPath')->hasFormationAccess($currentSession->getFormationPath(), $user);
        } elseif ($this->get('security.authorization_checker')->isGranted('ROLE_CONCEPTEUR')) {
            $currentSession = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy(['id' => Session::PRE_VUE]);
            $hasAccess = true;
        } else {
            $hasAccess = false;
        }

        if (empty($hasAccess) || $hasAccess == false) {
            return $this->redirect($this->generateUrl('user_formation'));
        }

        //LOOP MODULES & TEST FOR PRETEST & PRETEST DONE => ADD TO ARRAY WITH MODULE PRETEST BOOLEAN
        $modules = [];
        $sessionModuleInfo = $em->getRepository('App\Entity\PlanningManagement\SessionFormationPathModule')->findBy(['session' => $currentSession]);
        foreach ($sessionModuleInfo as $sessionModule) {
            $userModule = $em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')->findOneBy([
                'user' => $user,
                'module' => $sessionModule->getModule(),
                'session' => $currentSession
            ]);

            // get lastPage et lastCourse with currentSession and $formationPathModule->getModule()
            $userLastPage = $em->getRepository('App\Entity\UserFrontManagement\UserPageFollow')->findOneBy(['user' => $user, 'session' => $currentSession, 'module' => $sessionModule->getModule()], ['LastConnexion' => 'DESC']);

            if ($userLastPage) {
                $userModule->setUserLastPage($userLastPage);
            }
            if ($userModule != null) {
                $elem = [
                    'module' => $sessionModule->getModule(),
                    'userModule' => $userModule,
                    'sessionModule' =>$sessionModule
                ];
            } else {
                $elem = [
                    'module' => $sessionModule->getModule(),
                    'userModule' => null,
                    'sessionModule' =>$sessionModule
                ];
            }

            // module tests
           
            foreach ($sessionModule->getModule()->getModuleTests() as $moduleTest) {
                if ($moduleTest->getTest()->getTypeTest()->getConditional() == 'pretest') {
                    $elem['pretest'] = $moduleTest->getTest();
                    $userPreTest = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findPreTestByTestAndUserIdAndModuleRef($user->getId(), $moduleTest->getTest()->getId(), $moduleTest->getModule()->getReference(), $currentSession->getId());
                    $userPreTest = end($userPreTest);
                    if ($userPreTest) {
                        if ($userPreTest->getScore() < $moduleTest->getScore()) {
                            $elem['done'] = false;
                        } else {
                            $elem['done'] = true;
                        }
                        if ($userPreTest->getTentative() >= $moduleTest->getNumberTry()) {
                            $elem['open'] = false;
                        } else {
                            $elem['open'] = true;
                        }
                    } else {
                        $elem['done'] = false;
                        $elem['open'] = true;
                    }
                }
            }
            array_push($modules, $elem);
        }

        

        return $this->render('UserFrontManagement/module.html.twig', [
            'user' => $user,
            'currentFormation' => $currentSession->getFormationPath(),
            'modules' => $modules,
            'session' => $currentSession,
            'sessionModuleInfo' => $sessionModuleInfo,
        ]);
    }

    /**
     * Fonction choisit un test ou une page
     *
     * @Route("/formation/endModule/{sessionSlug}/{slugModule}/{slugChapter}/{slugPage}", name="user_formation_module_end", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function endModule(Request $request, $sessionSlug, $slugModule, $slugChapter, $slugPage)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $currentSession = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy(['slug' => $sessionSlug]);
        $currentModule = $em->getRepository('App\Entity\FormationManagement\Module')->findBy(['slug' => $slugModule]);
        $currentChapter = $em->getRepository('App\Entity\FormationManagement\Course')->findBy(['slug' => $slugChapter]);
        $currentPage = $em->getRepository('App\Entity\FormationManagement\Page')->findBy(['slug' => $slugPage, 'course' => $currentChapter[0]]);
        $userModule = $em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')->findOneBy(['user' => $user, 'module' => $currentModule[0]]);

        $sort = null;
        foreach ($currentSession->getFormationPath()->getFormationPathModules() as $FormationModule) {
            if ($FormationModule->getModule() == $currentModule[0]) {
                $sort = $FormationModule->getSort() + 1;
            }
        }
        $nextModule = null;
        foreach ($currentSession->getFormationPath()->getFormationPathModules() as $FormationModule) {
            if ($FormationModule->getSort() == $sort) {
                $nextModule = $FormationModule->getModule();
            }
        }

        return $this->render('UserFrontManagement/end_module.html.twig', [
            'currentChapter' => $currentChapter[0],
            'userModule' => $userModule,
            'currentFormation' => $currentSession->getFormationPath(),
            'currentModule' => $currentModule[0],
            'currentPage' => $currentPage[0],
            'currentSession' => $currentSession,
            'nextModule' => $nextModule,
        ]);
    }

    /**
     * GENERATE FORMATION SYLLABUS
     * @Route("/formation/syllabus/{slugSession}/{slugFormation}", name="user_formation_syllabus", methods={"GET", "POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function syllabus(Request $request, $slugSession, $slugFormation = null, KernelInterface $kernel)
    {
        $em = $this->getDoctrine()->getManager();

        if ($slugSession && $slugSession != 'null') {
            $session = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy(['slug' => $slugSession]);
            $formation = $session->getFormationPath();
        } elseif ($this->get('security.authorization_checker')->isGranted('ROLE_CONCEPTEUR') && $slugFormation != null) {
            $session = null;
            $formation = $em->getRepository('App\Entity\FormationManagement\FormationPath')->findOneBy(['slug' => $slugFormation]);
        } else {
            $session = null;
            $formation = null;
        }

        if ($formation) {
            try {
                $html2pdf = new Html2Pdf('P', 'A4', 'fr');
                $html2pdf->pdf->SetDisplayMode('fullpage');
                ob_start();
                $content = ob_get_clean();
                $chemin = $kernel->getProjectDir();
                $user = $this->get('security.token_storage')->getToken()->getUser();
                $content = $this->container->get('templating')->render('UserFrontManagement/partials/formation_syllabus.html.twig', [
                    'formation' => $formation,
                    'user' => $user,
                    'chemin' => $chemin,
                    'session' => $session,
                ]);
                $html2pdf->writeHTML($content);
                $html2pdf->output($formation->getTitle() . '.pdf');
            } catch (Html2PdfException $e) {
                $html2pdf->clean();
                $formatter = new ExceptionFormatter($e);
                echo $formatter->getHtmlMessage();
            }

            return $this->redirect($this->generateUrl('home_dashboard', []));
        } else {
            return $this->redirect($this->generateUrl('home_dashboard', []));
        }
    }

    /**
     * SHOW LEXIQUE BY USER & MODULES
     *
     * @Route("/formation/cetificatattesation", name="user_certificat_attestation", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function certificatAttestationAction()
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $attestations = $em->getRepository('App\Entity\ResultManagement\Attestation')->findBy(['user' => $user]);
        $certificats = $em->getRepository('App\Entity\ResultManagement\Certificat')->findBy(['user' => $user]);

        return $this->render('UserFrontManagement/tools/certificat_attestation.html.twig', [
            'user' => $user,
            'attestations' => $attestations,
            'certificats' => $certificats,
        ]);
    }
}
