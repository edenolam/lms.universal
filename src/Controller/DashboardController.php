<?php

namespace App\Controller;

use App\Repository\FormationManagement\CourseRepository;
use App\Repository\FormationManagement\FormationPathRepository;
use App\Repository\FormationManagement\ModuleRepository;
use App\Repository\FormationManagement\PageRepository;
use App\Repository\PlanningManagement\SessionRepository;
use App\Repository\UserManagement\LoggedMessageRepository;
use App\Repository\UserManagement\TrackingRepository;
use App\Repository\UserManagement\UserRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user")
 */
class DashboardController extends Controller
{
    /**
     * Fonction home
     *
     * @Route("/dashboard", name="home_dashboard", methods="GET")
     *
     * @Security("has_role('ROLE_USER')")
     */
    public function dashboard(UserRepository $userRepository, FormationPathRepository $formationPathRepository, ModuleRepository $moduleRepository, CourseRepository $courseRepository, PageRepository $pageRepository, SessionRepository $sessionRepository, TrackingRepository $trackingRepository, LoggedMessageRepository $loggedMessageRepository)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $activeUserFormationSessionFollow = $em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findByActiveSession($user);

        $downFormations = $em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->userCountAllDone($user);

        $userLastFormation = $em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findOneBy(['user' => $user], ['LastConnexion' => 'DESC']);

        $userLastNote = $em->getRepository('App\Entity\UserFrontManagement\UserPageFollow')->findLastUserNote($user);

        $events = [];
        $formations_todo = 0;
        $evals_todo = [];
        $modules_todo = [];

        // tous les sessions
        foreach ($user->getSessions() as $session) {
            $event['title'] = $session->getTitle();
            $event['start'] = $session->getOpeningDate()->format('Y-m-d');
            $event['end'] = $session->getClosingDate()->format('Y-m-d');
            $today = new \DateTime();

            // les sessions encours
            if ($session->getOpeningDate() < $today && $session->getClosingDate() > $today) {
                $event['url'] = $this->generateUrl(
                    'user_formation_module',
                    ['slugSession' => $session->getSlug()]
                );
                $event['className'] = 'opened';
                // for nb formation(s) en cours
                $userformationSessionfollow = $em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findOneBy(['user' => $user, 'session' => $session]);
                // les formations à faire
                if (!$userformationSessionfollow || !$userformationSessionfollow->getEndDate()) {
                    $formations_todo++;

                    // les modules à faire
                    foreach ($session->getFormationPath()->getFormationPathModules() as $formationPathModule) {
                        $userModule = $em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')->findOneBy([
                                    'user' => $user,
                                    'module' => $formationPathModule->getModule(),
                                    'session' => $session
                                ]);
                        if (!$userModule || !$userModule->getSuccess()) {
                            // les modules à faire
                            array_push($modules_todo, [
                                'formation' => $session->getFormationPath()->getSlug(),
                                'module' => $formationPathModule->getModule()->getSlug(),
                                ]);
                            // les evaluation à faire
                            $goEval = true;
                            if($userModule != null){
                                foreach ($userModule->getModule()->getValidationModes() as $val) {
                                    if ($val->getConditional() == 'pre-test-valid' || $val->getConditional() == 'pre-test-non-valid') {
                                        $userPreTest = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findPreTest($user->getId(), $session->getId(), $userModule->getModule()->getReference());

                                        if ($userPreTest == null) {
                                            $goEval = false;
                                        }
                                    } elseif ($val->getConditional() == 'lecture') {
                                        if ($userModule->getPercentage() != 100) {
                                            $goEval = false;
                                        }
                                    }
                                }
                            }

                            foreach ($formationPathModule->getModule()->getModuleTests() as $moduleTest) {
                                if ($moduleTest->getTest()->getTypeTest()->getConditional() == 'eval') {
                                    $SessionFormationPathModule = $em->getRepository('App\Entity\PlanningManagement\SessionFormationPathModule')->findOneBy(
                                        [
                                            'session' => $session,
                                            'module' => $formationPathModule->getModule()
                                        ]
                                    );

                                    if ($SessionFormationPathModule &&$SessionFormationPathModule->getOpeningDateEvaluation() <= $today && $SessionFormationPathModule->getClosingDateEvaluation() >= $today && $goEval == true) {
                                        $elem['session'] = $session->getSlug();
                                        $elem['formation'] = $session->getFormationPath()->getSlug();
                                        $elem['module'] = $formationPathModule->getModule()->getSlug();
                                        $elem['test'] = $moduleTest->getTest()->getSlug();
                                        $elem['test_name'] = $moduleTest->getTest()->getTitle();
                                        array_push($evals_todo, $elem);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            // les sessions finis et en courses
            array_push($events, $event);
        }

        // GET USER CERTIFICAT & ATTESTATION
        $certificats = $em->getRepository('App\Entity\ResultManagement\Certificat')->findBy(['user' => $user]);
        $attestations = $em->getRepository('App\Entity\Resultmanagement\Attestation')->findBy(['user' => $user]);

        $userPageNotes = $em->getRepository('App\Entity\UserFrontManagement\UserPageFollow')->findNotesByUser($user);

        return $this->render('home/home.html.twig', [
            'attestations' => $attestations,
            'certificats' => $certificats,
            //'enCoursFormations' => $enCoursFormations,
            'downFormations' => $downFormations,
            //'userLastPage'=> $userLastPage,
            'activeUserFormationSessionFollow' => $activeUserFormationSessionFollow,
            'userLastFormation' => $userLastFormation,
            'events' => json_encode($events),
            'userLastNote' => $userLastNote,
            'notes' => $userPageNotes,
            'formationsActive' => $formations_todo,
            'evals_todo' => $evals_todo,
            'modules_todo' => $modules_todo,
        ]);
    }
}
