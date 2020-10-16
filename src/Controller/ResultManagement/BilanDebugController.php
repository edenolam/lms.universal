<?php

namespace App\Controller\ResultManagement;

use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\ScoTracking;
use App\Entity\PlanningManagement\Session;
use App\Entity\UserFrontManagement\UserFormationSessionFollow;
use App\Entity\UserFrontManagement\UserModuleFollow;
use App\Entity\UserManagement\User;
use App\Repository\FormationManagement\ScoTrackingRepository;
use App\Repository\UserFrontManagement\UserFormationSessionFollowRepository;
use App\Repository\UserFrontManagement\UserModuleFollowRepository;
use App\Manager\ScormManager;
use App\Manager\UserModuleFollowManager; 
use App\Manager\UserFormationSessionFollowManager; 
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/bilan_debug")
 */
class BilanDebugController extends AbstractController
{
    /**
     * @Route("/detail/{user}/{session}/{module}", name="bilan_debug", methods={"GET"})
     *
     * @Security("is_granted('ROLE_SUPER_ADMIN')")
     */
    public function detail(Request $request, User $user, Session $session, Module $module, ScormManager $scormManager, ScoTrackingRepository $scoTrackingRepo, UserModuleFollowRepository $userModuleFollowRepository, UserFormationSessionFollowRepository $userformationSessionfollowRepository ): Response
    {
        $sco = $module->getScorm()->getScos();
        //$tracking = $scormManager->generateScoTracking($session, $sco[0], $this->getUser());
        $tracking = $scoTrackingRepo->findOneBy(['sco' => $sco[0], 'user' => $user, 'session' => $session]);
        
        $em = $this->getDoctrine()->getManager();

        $userModuleFollow = $userModuleFollowRepository->findOneBy(['user' => $user, 'module' => $module, 'session' => $session]);

        $userformationSessionfollow = $userformationSessionfollowRepository->findOneBy(['user' => $user, 'session' => $session]);

        return $this->render('ResultManagement/Bilan/bilan_debug.html.twig', [
            'user' => $user,
            'session' => $session,
            'module' => $module,
            'tracking' => $tracking,
            'userModuleFollow' => $userModuleFollow,
            'userformationSessionfollow' => $userformationSessionfollow

        ]);        
    }

    /**
     * @Route("/update/{user}/{session}/{module}", name="bilan_debug_update", methods={"GET"})
     *
     * @Security("is_granted('ROLE_SUPER_ADMIN')")
     */
    public function update(Request $request, User $user, Session $session, Module $module, ScormManager $scormManager, ScoTrackingRepository $scoTrackingRepo, UserFormationSessionFollowManager $userFormationSessionFollowManager,
        UserModuleFollowManager $userModuleFollowManager): Response
    {
        $em = $this->getDoctrine()->getManager();

        $sco = $module->getScorm()->getScos();
        // get scoTracking
        $tracking = $scoTrackingRepo->findOneBy(['sco' => $sco[0], 'user' => $user, 'session' => $session]);
        $data = $tracking->getDetails(); 
        
        $lessonStatus = $data['cmi.core.lesson_status'] ?? null;
        $sessionTime = $data['cmi.core.session_time'] ?? null;
        $progression = $data['cmi.core.progression'] ?? 0;
        
        $tracking->setEntry($data['cmi.core.entry']);
        $tracking->setExitMode($data['cmi.core.exit']);

        $bestStatus = $tracking->getLessonStatus();

        if ($lessonStatus !== $bestStatus && 'passed' !== $bestStatus && 'completed' !== $bestStatus) {
            if (('not attempted' === $bestStatus && !empty($lessonStatus)) ||
                 (('browsed' === $bestStatus || 'incomplete' === $bestStatus)
                    && ('failed' === $lessonStatus || 'passed' === $lessonStatus || 'completed' === $lessonStatus)) ||
                 ('failed' === $bestStatus && ('passed' === $lessonStatus || 'completed' === $lessonStatus))
            ) {
                $tracking->setLessonStatus($lessonStatus);
                $bestStatus = $lessonStatus;
            }
        }
        if (empty($lessonStatus) && 'not attempted' === $bestStatus) {
            $tracking->setLessonStatus('incomplete');
        }

        $em->persist($tracking);
        $em->flush();

        $userModuleFollowManager->updateUserModuleFollowScorm($session, $module, $user, $tracking);
        $userFormationSessionFollowManager->updateUserSessionsFormationScorm($session, $module, $user, $tracking);

        
        return $this->redirect($this->generateUrl('bilan_debug', [
            'user' => $user->getId(),
            'module' => $module->getId(),
            'session' => $session->getId(),
        ]));
    }    
}
