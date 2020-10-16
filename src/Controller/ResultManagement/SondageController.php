<?php

namespace App\Controller\ResultManagement;

use App\Entity\ResultManagement\Requete;
use App\Entity\TestManagement\Test;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/bilan")
 */
class SondageController extends AbstractController
{
    /**
     * GET RESULTS OF ALL SESSIONS
     *
     * @Route("/sondage/{requete}", defaults={"requete"=0}, name="sondage_view", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_ANALYSE_SONDAGE')")
     */
    public function sondage(Request $request, $requete)
    {
        $em = $this->getDoctrine()->getManager();
        $select_user = $select_team = $select_division = $select_formation = $select_session = $select_test = null;

        $sondageForm = $this->createForm('App\Form\ResultManagement\SondageType', null);
        $sondageForm->handleRequest($request);

        // si on recoit les données via le formulaire principal
        if ($sondageForm->isSubmitted() && $sondageForm->isValid()) {
            if ($sondageForm['test']->getData()) {
                $select_division = $sondageForm['division']->getData();
                $select_team = $sondageForm['team']->getData();
                $select_user = $sondageForm['user']->getData();
                $select_formation = $sondageForm['formation']->getData();
                $select_session = $sondageForm['session']->getData();
                $select_test = $sondageForm['test']->getData();
            }
        }

        $divisions = $teams = $users = $formations = $sessions = $tests = $testfiltreresult = [];

        $testfiltreresult = $em->getRepository('App\Entity\UserFrontManagement\UserTest')
            ->resultSondage($select_division, $select_team, $select_user, $select_test, $select_formation, $select_session);
        $output = [];
        $testLook = $em->getRepository('App\Entity\TestManagement\Test')->findOneBy(['id' => $select_test]);
        $questions = $em->getRepository('App\Entity\TestManagement\Question')->findOneBy(['test' => $testLook]);
        $output['somme'] = [];
        $output['multiple'] = [];
        $output['unique'] = [];
        $output['totalUserF'] = 0;
        // update the filtre
        foreach ($testfiltreresult as $result) {
            $userQuestions = $em->getRepository('App\Entity\UserFrontManagement\UserQuestion')->findBy(['userTest' => $result]);
            $output['totalUserF']++;
            foreach ($userQuestions as $uQ) {
                $output[$result->getId()][$uQ->getQuestion()->getSlug()] = $uQ;
                if ($uQ->getQuestion()->getAnswerType()->getConditional() == 'graduation') {
                    if (array_key_exists($uQ->getQuestion()->getSlug(), $output['somme'])) {
                        $output['somme'][$uQ->getQuestion()->getSlug()] += $uQ->getUserAnswers();
                    } else {
                        $output['somme'][$uQ->getQuestion()->getSlug()] = $uQ->getUserAnswers();
                    }
                } elseif ($uQ->getQuestion()->getAnswerType()->getConditional() == 'multiple') {
                    foreach ($uQ->getUserAnswers() as $ua) {
                        if (array_key_exists($uQ->getQuestion()->getSlug(), $output['multiple'])) {
                            if (array_key_exists($ua, $output['multiple'][$uQ->getQuestion()->getSlug()])) {
                                ++$output['multiple'][$uQ->getQuestion()->getSlug()][$ua];
                            } else {
                                $output['multiple'][$uQ->getQuestion()->getSlug()][$ua] = 1;
                            }
                        } else {
                            $output['multiple'][$uQ->getQuestion()->getSlug()][$ua] = 1;
                        }
                    }
                } elseif ($uQ->getQuestion()->getAnswerType()->getConditional() == 'unique') {
                    if (array_key_exists($uQ->getQuestion()->getSlug(), $output['unique'])) {
                        if (array_key_exists($uQ->getUserAnswers(), $output['unique'][$uQ->getQuestion()->getSlug()])) {
                            ++$output['unique'][$uQ->getQuestion()->getSlug()][$uQ->getUserAnswers()];
                        } else {
                            $output['unique'][$uQ->getQuestion()->getSlug()][$uQ->getUserAnswers()] = 1;
                        }
                    } else {
                        $output['unique'][$uQ->getQuestion()->getSlug()][$uQ->getUserAnswers()] = 1;
                    }
                }
            }

            // if mes apprenents
            if (!in_array($result->getUser(), $users)) {
                array_push($users, $result->getUser());
            }
            if ($result->getUser()->getTeam()) {
                if (!in_array($result->getUser()->getTeam(), $teams)) {
                    array_push($teams, $result->getUser()->getTeam());
                }
            }
            if ($result->getUser()->getDivision()) {
                if (!in_array($result->getUser()->getDivision(), $divisions)) {
                    array_push($divisions, $result->getUser()->getDivision());
                }
            }
            if (!in_array($result->getSession(), $sessions)) {
                array_push($sessions, $result->getSession());
            }
            if (!in_array($result->getSession()->getFormationPath(), $formations)) {
                array_push($formations, $result->getSession()->getFormationPath());
            }
            if (!in_array($result->getTest(), $tests)) {
                array_push($tests, $result->getTest());
            }
        }

        return $this->render('ResultManagement/Sondage/sondage.html.twig', [
            'sondageForm' => $sondageForm->createView(),
            'user' => $select_user,
            'equipe' => $select_team,
            'division' => $select_division,
            'formation' => $select_formation,
            'session' => $select_session,
            'test' => $select_test,
            'divisions' => $divisions,
            'teams' => $teams,
            'users' => $users,
            'formations' => $formations,
            'sessions' => $sessions,
            'tests' => $tests,
            'testfiltreresult' => $testfiltreresult,
            'output' => $output,
            'testLook' => $testLook,
            'questions' => $questions,
            'origine' =>'sondage'
        ]);
    }

    /**
     * GET RESULTS OF ALL SESSIONS
     *
     * @Route("/sondage/export/{user}/{division}/{team}/{sessions}/{formation}/{test}", name="sondage_export", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_ANALYSE_SONDAGE')")
     */
    public function excel(Request $request, LoggerInterface $logger, $user = null, $division = null, $team = null, $sessions = null, $formation = null, $test = null)
    {
        $em = $this->getDoctrine()->getManager();

        $select_user = $em->getRepository('App\Entity\UserManagement\User')->findOneBy(['id' => $user]);
        $select_team = $em->getRepository('App\Entity\UserManagement\Team')->findOneBy(['id' => $team]);
        $select_division = $em->getRepository('App\Entity\UserManagement\Division')->findOneBy(['id' => $division]);
        $select_formation = $em->getRepository('App\Entity\FormationManagement\FormationPath')->findOneBy(['id' => $formation]);
        $select_test = $em->getRepository('App\Entity\TestManagement\Test')->findOneBy(['id' => $test]);

        $sessionArrayId = json_decode($sessions);
        $select_session = new ArrayCollection();
        if ($sessions != 'null') {
            foreach ($sessionArrayId as $idS) {
                $s = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy(['id' => $idS]);
                $select_session[] = $s;
            }
        }

        $divisions = $teams = $users = $formations = $sessions = $tests = $testfiltreresult = [];

        $testfiltreresult = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->resultSondage($select_division, $select_team, $select_user, $select_test, $select_formation, $select_session, $this->getUser());

        $output = [];
        $testLook = $em->getRepository('App\Entity\TestManagement\Test')->findOneBy(['id' => $select_test]);
        $questions = $em->getRepository('App\Entity\TestManagement\Question')->findOneBy(['test' => $testLook]);
        $output['somme'] = [];
        $output['multiple'] = [];
        $output['unique'] = [];
        $output['totalUserF'] = 0;
        // update the filtre
        foreach ($testfiltreresult as $result) {
            $userQuestions = $em->getRepository('App\Entity\UserFrontManagement\UserQuestion')->findBy(['userTest' => $result]);
            $output['totalUserF']++;
            foreach ($userQuestions as $uQ) {
                $output[$result->getId()][$uQ->getQuestion()->getSlug()] = $uQ;
                if ($uQ->getQuestion()->getAnswerType()->getConditional() == 'graduation') {
                    if (array_key_exists($uQ->getQuestion()->getSlug(), $output['somme'])) {
                        $output['somme'][$uQ->getQuestion()->getSlug()] += $uQ->getUserAnswers();
                    } else {
                        $output['somme'][$uQ->getQuestion()->getSlug()] = $uQ->getUserAnswers();
                    }
                } elseif ($uQ->getQuestion()->getAnswerType()->getConditional() == 'multiple') {
                    foreach ($uQ->getUserAnswers() as $ua) {
                        if (array_key_exists($uQ->getQuestion()->getSlug(), $output['multiple'])) {
                            if (array_key_exists($ua, $output['multiple'][$uQ->getQuestion()->getSlug()])) {
                                ++$output['multiple'][$uQ->getQuestion()->getSlug()][$ua];
                            } else {
                                $output['multiple'][$uQ->getQuestion()->getSlug()][$ua] = 1;
                            }
                        } else {
                            $output['multiple'][$uQ->getQuestion()->getSlug()][$ua] = 1;
                        }
                    }
                } elseif ($uQ->getQuestion()->getAnswerType()->getConditional() == 'unique') {
                    if (array_key_exists($uQ->getQuestion()->getSlug(), $output['unique'])) {
                        if (array_key_exists($uQ->getUserAnswers(), $output['unique'][$uQ->getQuestion()->getSlug()])) {
                            ++$output['unique'][$uQ->getQuestion()->getSlug()][$uQ->getUserAnswers()];
                        } else {
                            $output['unique'][$uQ->getQuestion()->getSlug()][$uQ->getUserAnswers()] = 1;
                        }
                    } else {
                        $output['unique'][$uQ->getQuestion()->getSlug()][$uQ->getUserAnswers()] = 1;
                    }
                }
            }

            // if mes apprenents
            if (!in_array($result->getUser(), $users)) {
                array_push($users, $result->getUser());
            }
            if ($result->getUser()->getTeam()) {
                if (!in_array($result->getUser()->getTeam(), $teams)) {
                    array_push($teams, $result->getUser()->getTeam());
                }
            }
            if ($result->getUser()->getDivision()) {
                if (!in_array($result->getUser()->getDivision(), $divisions)) {
                    array_push($divisions, $result->getUser()->getDivision());
                }
            }
            if (!in_array($result->getSession(), $sessions)) {
                array_push($sessions, $result->getSession());
            }
            if (!in_array($result->getSession()->getFormationPath(), $formations)) {
                array_push($formations, $result->getSession()->getFormationPath());
            }
            if (!in_array($result->getTest(), $tests)) {
                array_push($tests, $result->getTest());
            }
        }

        $content = $this->container->get('templating')->render('ResultManagement/Sondage/tableau.html.twig', [
            'testfiltreresult' => $testfiltreresult,
            'testLook' => $select_test,
            'test' => $select_test->getId(),
            'output' => $output,
            'testLook' => $testLook,
            'questions' => $questions,
            'origine' =>'excel'
        ]);

        $today = new \Datetime();

        try {
            return new Response(iconv("UTF-8", "ISO-8859-1//TRANSLIT",$content), 200, [
                'Content-Type' => 'application/force-download',
                'Content-Disposition' => 'attachement; filename="export_Bilan_' . $today->format('Y-m-d H:i:s') . '.xls'
            ]);
        } catch (\Exception $exception) {
            $logger->addError($exception->getMessage());
            return new Response();
        }
        
    }
}
