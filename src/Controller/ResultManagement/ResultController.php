<?php

namespace App\Controller\ResultManagement;

use App\Entity\TestManagement\Test;
use App\Entity\UserFrontManagement\UserTest;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/results")
 */
class ResultController extends Controller
{
    /**
     * GET RESULTS OF ALL SESSIONS
     *
     * @Route("/dashboard/tuteur", name="dashboard_tuteur", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_BILANS')")
     */
    public function viewDashboardTuteur(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $sessions = $em->getRepository('App\Entity\PlanningManagement\Session')->findOpeningSession();
        $formationArray = [];
        if ($this->get('security.authorization_checker')->isGranted('ROLE_RESPONSABLE_FORMATION')) {
            $tutorFollow = null;
            $role = 'respoFormation';
        } else {

            $tutorFollow = $em->getRepository('App\Entity\UserManagement\User')->getFollowTutors($this->getUser(), 'tuteur');
            $role = 'tuteur';
        }
        
        $aprenantFolow = $em->getRepository('App\Entity\UserManagement\User')->findApprenantFollowBy($this->getUser(), $role, $tutorFollow);

        foreach ($sessions as $session) {
            
            $userFormations = $em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->getSessionFormationFollow($session, $this->getUser(), $role, $tutorFollow);
            if($userFormations != null){
                $elem['session'] = $session;
                $countFinished = 0;
                $percEval = 0;
                $countEval = 0;
                $totalTime = 0;
                $lecturePercent = 0;
                foreach ($userFormations as $userFormation) {
                    $userLook = $userFormation->getUser();
                    if ($userFormation->getSuccess()) {
                        $countFinished++;
                    }
                    $totalTime += $userFormation->getDurationTotalSec();
                    $lecturePercent += $userFormation->getPercentage();
                    
                    foreach ($userFormation->getFormation()->getFormationPathModules() as $formationPathModule) {
                        if ($formationPathModule->getModule()->getModuleEvaluation() != null) {
                            $userTests = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findScoreOfSessionLastEvalUser($formationPathModule->getModule()->getModuleEvaluation(),$session,$userLook);
                                if($userTests['score'] and $userTests['score'] != -1){
                                    $percEval = $percEval + $userTests['score'];
                                    $countEval++;
                                }
                        }
                    }
                }
                
                $elem['userFormations'] = count($userFormations);
                $elem['userDone'] = $countFinished;
                if ($countEval !== 0) {
                    $elem['percentage'] = $percEval / $countEval;
                } else {
                    $elem['percentage'] = 0;
                }
                if (count($userFormations) == 0) {
                    $mTime = 0;
                    $mLecture = 0;
                } else {
                    
                    $meanTime = $totalTime / count($userFormations);
                    $hoursMeanTime = floor($meanTime/3600);
                    $minutesMeanTime = floor(($meanTime-($hoursMeanTime*3600))/60);
                    
                    // $mTime = new \DateTime();
                    // $mTime->setTimestamp($meanTime);
                    // $mTime->format('H:i:s');
                    $mLecture = $lecturePercent/count($userFormations);
                }
                $elem['countEval'] = $countEval;
                $elem['lecturePercent'] = $mLecture;
                $elem['meanTime']['h'] = $hoursMeanTime;
                $elem['meanTime']['m'] = $minutesMeanTime;

                array_push($formationArray, $elem);
            }
        }
        //var_dump($formationArray);

        return $this->render('ResultManagement/DashBoardTuteur/view_summary_formation.html.twig', [
      'sessions' => $formationArray
    ]);
    }
}
