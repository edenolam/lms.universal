<?php

namespace App\Controller\ResultManagement;

use App\Entity\ResultManagement\Requete;
use App\Entity\TestManagement\Test;
use App\Entity\UserFrontManagement\UserModuleFollow;
use App\Entity\UserFrontManagement\UserTest;
use App\Repository\UserFrontManagement\UserTestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/bilan")
 */
class BilanController extends AbstractController
{
    /**
     * GET RESULTS OF ALL SESSIONS
     *
     * @Route("/list/{requete}", defaults={"requete"=0}, name="results_list", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_BILANS')")
     */
    public function list(Request $request, $requete, UserTestRepository $userTestRepository)
    {
        
        $em = $this->getDoctrine()->getManager();
        $select_user = $select_team = $select_division = $select_formation = $select_session = $select_module = $select_datestart = $select_dateend = $modulefiltreresult = null;

        if ($this->get('security.authorization_checker')->isGranted('ROLE_RESPONSABLE_FORMATION')) {
            $tutorFollow = null;
            $role = 'respoFormation';
        } else {
            $tutorFollow = $em->getRepository('App\Entity\UserManagement\User')->getFollowTutors($this->getUser(), 'tuteur');
            $role = 'tuteur';
        }

        $bilanForm = $this->createForm('App\Form\ResultManagement\BilanType', null, ['tutorFollow' => $tutorFollow, 'role' => $role, 'user' => $this->getUser()]);
        $bilanForm->handleRequest($request);


        // si on recoit les données via le formulaire principal
        if ($bilanForm->isSubmitted() && $bilanForm->isValid()) {
            $select_division = $bilanForm['division']->getData();
            $select_team = $bilanForm['team']->getData();
            $select_user = $bilanForm['user']->getData();
            $select_formation = $bilanForm['formation']->getData();
            $select_session = $bilanForm['session']->getData();
            $select_module = $bilanForm['module']->getData();
            $select_datestart = $bilanForm['datestart']->getData();
            $select_dateend = $bilanForm['dateend']->getData();
            $modulefiltreresult = $em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')
            ->tableauFiltreResult($select_division, $select_team, $select_user, $select_module, $select_formation, $select_session, $select_datestart, $select_dateend, $this->getUser(), $role, $tutorFollow);
            
        // si les données proviennent du tableau de bor tuteur
        } elseif ($request->get('slugSession') && $request->get('slugFormation')) {
            $select_formation = $em->getRepository('App\Entity\FormationManagement\FormationPath')->findBy(['slug' => $request->get('slugFormation')]);
            $select_session = $em->getRepository('App\Entity\PlanningManagement\Session')->findBy(['slug' => $request->get('slugSession')]);
            $select_user = new ArrayCollection();
            $select_team = new ArrayCollection();
            $select_division = new ArrayCollection();
            $select_module = new ArrayCollection();
            
            $modulefiltreresult = $em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')
            ->tableauFiltreResult($select_division, $select_team, $select_user, $select_module, $select_formation, $select_session, $select_datestart, $select_dateend, $this->getUser(), $role, $tutorFollow);
        //si les données  proviennent du select de sauvegarde des requetes
        } elseif ($requete) {
            $formulaire = $em->getRepository('App\Entity\ResultManagement\Requete')->findOneBy(['id' => $requete]);
            $forme = json_decode($formulaire->getFormulaire());
            $divisionArrayId = json_decode($forme->{'division'});
            $userArrayId = json_decode($forme->{'user'});
            $teamArrayId = json_decode($forme->{'team'});
            $formationArrayId = json_decode($forme->{'formation'});
            $sessionArrayId = json_decode($forme->{'session'});
            $moduleArrayId = json_decode($forme->{'module'});

            $select_user = new ArrayCollection();
            $select_team = new ArrayCollection();
            $select_division = new ArrayCollection();
            $select_formation = new ArrayCollection();
            $select_session = new ArrayCollection();
            $select_module = new ArrayCollection();

            if ($userArrayId != 'null') {
                $select_user = $em->getRepository('App\Entity\UserManagement\User')->findBy(['id' => $userArrayId]);
            }
            if ($teamArrayId != 'null') {
                $select_team = $em->getRepository('App\Entity\UserManagement\Team')->findBy(['id' => $teamArrayId]);
            }
            if ($divisionArrayId != 'null') {
                $select_division = $em->getRepository('App\Entity\UserManagement\Division')->findBy(['id' => $divisionArrayId]);
            }
            if ($formationArrayId != 'null') {
                $select_formation = $em->getRepository('App\Entity\FormationManagement\FormationPath')->findBy(['id' => $formationArrayId]);
            }
            if ($moduleArrayId != 'null') {
                $select_module = $em->getRepository('App\Entity\FormationManagement\Module')->findBy(['id' => $moduleArrayId]);
            }
            if ($sessionArrayId != 'null') {
                $select_session = $em->getRepository('App\Entity\PlanningManagement\Session')->findBy(['id' => $sessionArrayId]);
            }
            $modulefiltreresult = $em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')
            ->tableauFiltreResult($select_division, $select_team, $select_user, $select_module, $select_formation, $select_session, $select_datestart, $select_dateend, $this->getUser(), $role, $tutorFollow);
        }

        $pourcentages = $scoreValidation = $validated = $failed= $inProcess = $totalLines = $totallianeNotPrez = $j = 0;

        if($modulefiltreresult != null){
            foreach($modulefiltreresult as $data){
                $totalLines = $totalLines + 1;
                if($data->getModule()->getType()->getConditional() != "presentiel"){
                    $pourcentages = $pourcentages + $data->getPercentage();
                    $totallianeNotPrez = $totallianeNotPrez + 1;
                }
                // if($data->getModule()->getEvaluation()){
                //     $j = $j + 1;
                // }
                if($data->getScore() != null){
                    $scoreValidation = $scoreValidation + $data->getScore();
                    $j = $j + 1;
                }
                if($data->getSuccess() == null){
                    $inProcess = $inProcess + 1;
                }elseif($data->getSuccess() == true){
                    $validated = $validated + 1;
                }else{
                    $failed = $failed + 1;
                }
            }
        }
                
        if ($j != 0){
            $moyenneScore = $scoreValidation / $j;
        }else{
            $moyenneScore = 0;
        }
        if($totallianeNotPrez != 0){
            $moyenne = $pourcentages / $totallianeNotPrez;
        } else {
            $moyenne = 0;
        }

        if($totalLines != 0){
            $moyenneSucces = ($validated / $totalLines) * 100;
            $moyenneFailed = ($failed / $totalLines) * 100;
            $moyenneProcess = ($inProcess / $totalLines) * 100;
        } else {
            $moyenneSucces = 0;
            $moyenneFailed = 0;
            $moyenneProcess = 0;
        }
        

        return $this->render('ResultManagement/Bilan/bilan.html.twig', [
            'bilanForm' => $bilanForm->createView(),
            // 'user' => $select_user,
            // 'equipe' => $select_team,
            // 'division' => $select_division,
            // 'formation' => $select_formation,
            // 'session' => $select_session,
            // 'datestart' => $select_datestart,
            // 'dateend' => $select_dateend,
            // 'module' => $select_module,
            // 'divisions' => $divisions,
            // 'teams' => $teams,
            // 'users' => $users,
            // 'formations' => $formations,
            // 'sessions' => $sessions,
            // '_select_session' => $_select_session,
            // 'modules' => $modules,
            'pourcentages' => $pourcentages,
            'scoreValidation' =>$scoreValidation,
            'moyenneSucces' =>$moyenneSucces,
            'moyenneFailed' =>$moyenneFailed,
            'moyenneProcess' =>$moyenneProcess,
            'moyenneScore' =>$moyenneScore,
            'moyenne' => $moyenne,
            'validated' => $validated,
            'failed' =>$failed,
            'inProcess' =>$inProcess,
            'totalLines'=> $totalLines,
            'modulefiltres' => $modulefiltreresult //$userModuleFollows
        ]);
    }

    /**
     * GET RESULTS OF ALL SESSIONS
     *
     * @Route("/export/{user}/{division}/{team}/{sessions}/{formation}/{module}/{datestart}/{dateend}", name="results_export", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_BILANS')")
     */
    public function excel(Request $request, \Twig_Environment $twig, UserTestRepository $userTestRepository, LoggerInterface $logger, $user = null, $division = null, $team = null, $sessions = null, $formation = null, $module = null, $datestart = null, $dateend = null)
    {
        $em = $this->getDoctrine()->getManager();
        if ($this->get('security.authorization_checker')->isGranted('ROLE_RESPONSABLE_FORMATION')) {
            $tutorFollow = null;
            $role = 'respoFormation';
        } else {
            $tutorFollow = $em->getRepository('App\Entity\UserManagement\User')->getFollowTutors($this->getUser(), 'tuteur');
            $role = 'tuteur';
        }

        //$data = json_decode($request->getContent(),true);
        $select_user = $em->getRepository('App\Entity\UserManagement\User')->findBy(['id' => json_decode($user)]);
        $select_team = $em->getRepository('App\Entity\UserManagement\Team')->findBy(['id' => json_decode($team)]);
        $select_division = $em->getRepository('App\Entity\UserManagement\Division')->findBy(['id' => json_decode($division)]);
        $select_formation = $em->getRepository('App\Entity\FormationManagement\FormationPath')->findBy(['id' => json_decode($formation)]);
        $select_module = $em->getRepository('App\Entity\FormationManagement\Module')->findBy(['id' => json_decode($module)]);
        $select_session = $em->getRepository('App\Entity\PlanningManagement\Session')->findBy(['id' => json_decode($sessions)]);
        if($datestart != 'null' and $dateend != 'null'){
            $select_datestart = $datestart;
            $select_dateend = $dateend;
        }else{
            $select_datestart = null;
            $select_dateend = null;
        }

        $modulefiltreresult = $em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')->tableauFiltreResult($select_division, $select_team, $select_user, $select_module, $select_formation, $select_session, $select_datestart, $select_dateend, $this->getUser(), $role, $tutorFollow);

        // $userModuleFollows = array();
        // $i=0;
        // foreach($modulefiltreresult as $modulefollow){

        //     $userPreTest = $userTestRepository->findLastTestByTest($modulefollow->getUser(), $modulefollow->getSession(), $modulefollow->getModule()->getModulePreTestId(), Test::PRE_TEST);
        //     $userTraining = $userTestRepository->findLastTestByTest($modulefollow->getUser(), $modulefollow->getSession(), $modulefollow->getModule()->getModuleTrainingId(), Test::ENTRAINEMENT);
        //     $userEvaluation = $userTestRepository->findLastTestByTest($modulefollow->getUser(), $modulefollow->getSession(), $modulefollow->getModule()->getModuleEvaluationId(), Test::EVALUATION);


        //     $modulefollow->setUserPreTest($userPreTest);
        //     $modulefollow->setUserTraining($userTraining);
        //     $modulefollow->setUserEvaluation($userEvaluation);

        //     $userModuleFollows [$i] = $modulefollow;
        //     $i++;
        // }
     

        // appendre userTest for userModuleFollow
        $userModuleFollows = array_map(function (UserModuleFollow $userModuleFollow) use ($userTestRepository) {
            $userPreTest = $userTestRepository->findLastTestByTest($userModuleFollow->getUser(), $userModuleFollow->getSession(), $userModuleFollow->getModule()->getModulePreTestId(), Test::PRE_TEST);
            $userTraining = $userTestRepository->findLastTestByTest($userModuleFollow->getUser(), $userModuleFollow->getSession(), $userModuleFollow->getModule()->getModuleTrainingId(), Test::ENTRAINEMENT);
            $userEvaluation = $userTestRepository->findLastTestByTest($userModuleFollow->getUser(), $userModuleFollow->getSession(), $userModuleFollow->getModule()->getModuleEvaluationId(), Test::EVALUATION);

            if ($userPreTest) {
                $userModuleFollow->setUserPreTest($userPreTest);
            }
            if ($userTraining) {
                $userModuleFollow->setUserTraining($userTraining);
            }
            if ($userEvaluation) {
                $userModuleFollow->setUserEvaluation($userEvaluation);
            }

            return $userModuleFollow; // userModuleFollow entity avec userTest datas
        }, $modulefiltreresult);

        $content = $twig->render('ResultManagement/Bilan/export_bilan.html.twig', [
            'filtre' => $userModuleFollows, //$modulefiltreresult
        ]);

        $today = new \Datetime();

        try {
            return new Response(iconv("UTF-8", "ISO-8859-1//TRANSLIT",$content), 200, [
                'Content-Type' => 'application/force-download; charset=utf-8',
                'Content-Disposition' => 'attachement; filename="export_Bilan_' . $today->format('Y-m-d H:i:s') . '.xls'
            ]);
        } catch (\Exception $exception) {
            $logger->addError($exception->getMessage());
        }
    }

    /**
     * GET RESULTS OF ALL SESSIONS
     *
     * @Route("/export_presentiel/{user}/{division}/{team}/{sessions}/{formation}/{module}/{datestart}/{dateend}", name="results_export_presentiel", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_BILANS')")
     */
    public function excelPresentiel(Request $request,  \Twig_Environment $twig, LoggerInterface $logger, $user = null, $division = null, $team = null, $sessions = null, $formation = null, $module = null, $datestart = null, $dateend = null)
    {
        $em = $this->getDoctrine()->getManager();
        if ($this->get('security.authorization_checker')->isGranted('ROLE_RESPONSABLE_FORMATION')) {
            $tutorFollow = null;
            $role = 'respoFormation';
        } else {
            $tutorFollow = $em->getRepository('App\Entity\UserManagement\User')->getFollowTutors($this->getUser(), 'tuteur');
            $role = 'tuteur';
        }

        //$data = json_decode($request->getContent(),true);
        $select_user = $em->getRepository('App\Entity\UserManagement\User')->findBy(['id' => json_decode($user)]);
        $select_team = $em->getRepository('App\Entity\UserManagement\Team')->findBy(['id' => json_decode($team)]);
        $select_division = $em->getRepository('App\Entity\UserManagement\Division')->findBy(['id' => json_decode($division)]);
        $select_formation = $em->getRepository('App\Entity\FormationManagement\FormationPath')->findBy(['id' => json_decode($formation)]);
        $select_module = $em->getRepository('App\Entity\FormationManagement\Module')->findBy(['id' => json_decode($module)]);
        $select_session = $em->getRepository('App\Entity\PlanningManagement\Session')->findBy(['id' => json_decode($sessions)]);
        // $select_datestart = $datestart;
        // $select_dateend = $dateend;
        if($datestart != 'null' and $dateend != 'null'){
            $select_datestart = $datestart;
            $select_dateend = $dateend;
        }else{
            $select_datestart = null;
            $select_dateend = null;
        }

        $modulefiltreresult = $em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')->tableauFiltreResult($select_division, $select_team, $select_user, $select_module, $select_formation, $select_session, $select_datestart, $select_dateend, $this->getUser(), $role, $tutorFollow, 'presentiel');
        $content = $twig->render('ResultManagement/Bilan/export_presentiel.html.twig', [
            'filtre' => $modulefiltreresult
        ]);

        $today = new \Datetime();

        try {
            return new Response(iconv("UTF-8", "ISO-8859-1//TRANSLIT",$content), 200, [
                'Content-Type' => 'application/force-download',
                'Content-Disposition' => 'attachement; filename="export_presentiel' . $today->format('Y-m-d H:i:s') . '.xls'
            ]);
        } catch (\Exception $exception) {
            $logger->addError($exception->getMessage());
        }
    }

    /**
     * GET RESULTS OF ALL SESSIONS
     *
     * @Route("/query_save/{user}/{division}/{team}/{sessions}/{formation}/{module}/{nom}", name="save_form", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_BILANS')")
     */
    public function saveForm(Request $request, $user = null, $division = null, $team = null, $sessions = null, $formation = null, $module = null, $nom = null)
    {
        $em = $this->getDoctrine()->getManager();

        $formulaire = ['user' => $user, 'team' => $team, 'division' => $division, 'formation' => $formation, 'module' => $module, 'session' => $sessions];
        $forme = json_encode($formulaire);

        $requete = new Requete();
        $requete->setName($nom);
        $requete->setFormulaire($forme);
        $requete->setUser($this->getUser());
        $requete->setSort(0);
        $em->persist($requete);
        $em->flush();

        return $this->redirectToRoute('results_list');
    }

    /**
     * @Route("/list/delete/{favori}", name="delete_favori", methods={"GET", "POST"})
     *
     * @Security("is_granted('ROLE_GESTION_BILANS')")
     */
    public function deleteFavori(Request $request, $favori = null)
    {
        $em = $this->getDoctrine()->getManager();
        $favoris = $em->getRepository('App\Entity\ResultManagement\Requete')->findOneBy(['id' => $favori]);
        $em->remove($favoris);
        $em->flush();

        return $this->redirectToRoute('results_list');
    }
}
