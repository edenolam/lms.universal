<?php

namespace App\Controller\UserFrontManagement;

use App\Entity\FormationManagement\Module;
use App\Entity\UserFrontManagement\UserTest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/apprenant")
 */
class UserSuiviController extends AbstractController
{
    /**
     * Mon suivi liste des formation
     *
     * @Route("/", name="user_mon_suivis", methods="GET")
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function ViewMonSuivi(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        //Get all active formation for current user
        $activeUserFormation = $em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findByActiveSession($this->getUser());
        $pastUserFormation = $em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findByPastSession($this->getUser());
        //$activeFormations =  $em->getRepository('App\Entity\FormationManagement\FormationPath')->findAllActiveFormation($user);

        //GET DONE & UNDONE FORMATION
        $pastFormations = $em->getRepository('App\Entity\FormationManagement\FormationPath')->findAllPastFormation($this->getUser());

        // PREPARE EVENT ARRAY FOR CALENDAR
        $events = [];
        foreach ($this->getUser()->getSessions() as $session) {
            $event['title'] = $session->getTitle();
            $event['start'] = $session->getOpeningDate()->format('Y-m-d');
            $event['end'] = $session->getClosingDate()->format('Y-m-d');
            $today = new \DateTime();
            if ($session->getOpeningDate() < $today && $session->getClosingDate() > $today) {
                $event['url'] = $this->generateUrl(
                    'user_formation_module',
                    ['slugSession' => $session->getSlug()]
                );
                $event['className'] = 'opened';
            }
            array_push($events, $event);
        }

        // GET UNDONE EVAL
        $evalsCount = 0;
        foreach ($activeUserFormation as $formation) {
            foreach ($formation->getFormation()->getFormationPathModules() as $module) {
                foreach ($module->getModule()->getModuleTests() as $test) {
                    if ($test->getTest()->getTypeTest()->getConditional() == 'eval') {
                        $userModule = $em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')->findOneBy([
                            'module' => $module->getModule(),
                            'user' => $this->getUser(),
                            'session' => $formation->getSession()
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

        $certificats = $em->getRepository('App\Entity\ResultManagement\Certificat')->findBy([
            'user' => $this->getUser()
        ]);

        return $this->render('UserFrontManagement/mon_suivi_list.html.twig', [
            'user' => $this->getUser(),
            'activeFormations' => $activeUserFormation,
            'pastFormations' => $pastUserFormation,
            'certificats' => $certificats,
            'evalsCount' => $evalsCount,
            'events' => json_encode($events)
        ]);
    }

    /**
     * USER suivi FORMATION DETAIL
     * @Route("/suivi/detail/{slugSession}/{slug}", name="user_suivis_formation", methods="GET")
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function ViewMonSuiviFormation(Request $request, $slug, $slugSession)
    {
        $em = $this->getDoctrine()->getManager();

        //Current user
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $session = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy(['slug' => $slugSession]);
        $formationPath = $em->getRepository('App\Entity\FormationManagement\FormationPath')->findOneBy(['slug' => $slug]);
        $userFormation = $em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findOneBy(['user' => $user, 'formation' => $formationPath, 'session' => $session]);
        $certificats = $em->getRepository('App\Entity\ResultManagement\Certificat')->findBy(['userModuleFollow' => $userFormation]);
        $attestation = $em->getRepository('App\Entity\ResultManagement\Attestation')->findOneBy(['UserSessionFollow' => $userFormation]);

        $percPretest = [];
        $percEval = [];
        $percTrain = [];
        $userModules = [];
        foreach ($formationPath->getFormationPathModules() as $formationPathModule) {
            if ($formationPathModule->getModule()->getType()->getConditional() != 'notFollow') {
                $userModule = $em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')->findOneBy([
                    'user' => $user,
                    'module' => $formationPathModule->getModule(),
                    'session' => $session
                ]);

                // user_module_follow
                if (!empty($userModule)) {
                    $elem['module'] = $userModule;
                    $elem['tests'] = [];
                    //$elem['maxTry'] = array();
                    foreach ($userModule->getModule()->getModuleTests() as $moduleTest) {
                        $userTests = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findOneBy([
                            'user' => $user,
                            'test' => $moduleTest->getTest(),
                            'session' => $session
                        ], ['tentative' => 'DESC']);

                        if (!empty($userTests)) {
                            array_push($elem['tests'], $userTests);

                            // ARRAY PUSH TO GET PERCENTAGES OF LAST USERTEST (NORMALLY BEST GRADE )
                            if ($userTests->getTest()->getTypeTest()->getConditional() == 'pretest') {
                                array_push($percPretest, $userTests->getScore());
                            } elseif ($userTests->getTest()->getTypeTest()->getConditional() == 'eval') {
                                array_push($percEval, $userTests->getScore());
                            } elseif ($userTests->getTest()->getTypeTest()->getConditional() == 'training') {
                                array_push($percTrain, $userTests->getScore());
                            }
                        }
                    }
                    array_push($userModules, $elem);
                }
            }
        }

        if (count($percPretest)) {
            $add = 0;
            foreach ($percPretest as $perc) {
                $add = $add + $perc;
            }
            $percPretest = $add / count($percPretest);
        }

        if (count($percEval)) {
            $add = 0;
            foreach ($percEval as $perc) {
                $add = $add + $perc;
            }
            $percEval = $add / count($percEval);
        }

        if (count($percTrain)) {
            $add = 0;
            foreach ($percTrain as $perc) {
                $add = $add + $perc;
            }
            $percTrain = $add / count($percTrain);
        }

        $formationPercentages = ['pretest' => $percPretest, 'train' => $percTrain, 'eval' => $percEval];
        // COUNT EVALS
        $activeUserFormations = $em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findByActiveSession($user);

        $evalsCount = 0;
        foreach ($activeUserFormations as $activeFormation) {
            foreach ($activeFormation->getFormation()->getFormationPathModules() as $module) {
                if ($module->getModule()->getType()->getConditional() != 'notFollow') {
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
        }

        return $this->render('UserFrontManagement/formation_detail.html.twig', [
            'userFormation' => $userFormation,
            'userModules' => $userModules,
            'certificats' => $certificats,
            'attestation' => $attestation,
            'formationPercentages' => $formationPercentages,
            'evalsCount' => $evalsCount
        ]);
    }
}
