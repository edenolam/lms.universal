<?php

namespace App\Controller\UserFrontManagement;

use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\Sco;
use App\Entity\PlanningManagement\Session;
use App\Exception\InvalidDataException;
use App\Manager\ScormManager;
use App\Repository\FormationManagement\FormationPathRepository;
use App\Repository\FormationManagement\ModuleRepository;
use App\Repository\FormationManagement\ScoRepository;
use App\Repository\PlanningManagement\SessionRepository;
use App\Repository\UserFrontManagement\UserFormationSessionFollowRepository;
use App\Serializer\ScoTrackingSerializer;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserFrontManagement\UserModuleFollowRepository;
use Psr\Log\LoggerInterface;

/**
 * @Route("/user")
 *
 * @author free
 */
class UserScormController extends AbstractController
{
    /**
     * module scorm page
     *
     * @Route("/home/formation/scorm/{slugSession}/{slugModule}", name="user_formation_module_organisation_scorm", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function pageScorm($slugSession = null, $slugModule, Request $request, FormationPathRepository $formationPathRepository, ModuleRepository $moduleRepository, SessionRepository $sessionRepository, ScormManager $scormManager, KernelInterface $kernel)
    {
        $em = $this->getDoctrine()->getManager();
        $module = $moduleRepository->findOneBy(['slug' => $slugModule]);

        if ($slugSession) {
            $session = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy(['slug' => $slugSession]);
        } elseif ($this->get('security.authorization_checker')->isGranted('ROLE_CONCEPTEUR')) {
            $session = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy(['id' => Session::PRE_VUE]);
        } else {
            $session = null;
        }

        if ($session != null) {
            // from UserFrontController::page()

            //CHECK LECTURE DONE & IF NO PRE TEST OR EVAL VALIDATION -> MODULE SUCCESS
            $userModule = $em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')->findOneBy([
          'user' => $this->getUser(),
          'module' => $module,
          'session' => $session
          ]);

            //TEST IF PRETEST
            foreach ($module->getModuleTests() as $moduleTest) {
                if ($moduleTest->getTest()->getTypeTest()->getConditional() == 'pretest') {
                    //$elem['pretest'] = $moduleTest->getTest();

                    $userPreTest = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findPreTestByTestAndUserIdAndModuleRef($this->getUser()->getId(), $moduleTest->getTest()->getId(), $moduleTest->getModule()->getReference(), $session->getId());

                    $userPreTest = end($userPreTest);

                    if ($userPreTest) {
                        if ($userPreTest->getScore() < $moduleTest->getScore() && $userPreTest->getTentative() < $moduleTest->getNumberTry()) {
                            //USER PRETEST SCORE
                            return $this->redirect($this->generateUrl('test_front_begin', [
                  'sessionSlug' => $session->getSlug(),
                  'moduleSlug' => $module->getSlug(),
                  'testSlug' => $moduleTest->getTest()->getSlug()
                ]));
                        }
                    } else {
                        //NO USER PRETEST -> REDIRECT BEGIN TEST
                        return $this->redirect($this->generateUrl('test_front_begin', [
                'sessionSlug' => $session->getSlug(),
                'moduleSlug' => $module->getSlug(),
                'testSlug' => $moduleTest->getTest()->getSlug()
              ]));
                    }
                }
            }

            if (!is_object($module->getScorm()) && $module->getIsScorm()) {
                $fileSystem = new Filesystem();

                $imsmanifest = $kernel->getProjectDir() . '/public' . dirname($module->getScormPath()) . '/imsmanifest.xml';

                if ($fileSystem->exists($imsmanifest)) {
                    $scormData = $scormManager->parseScorm(file_get_contents($imsmanifest));

                    // create scorm => scorm update event
                    $serializedScorm = $scormManager->createScorm($module, [
                      'hashName' => Uuid::uuid4()->toString(),
                      'version' => $scormData['version'],
                      'scos' => $scormData['scos'],
                  ]);
                }

                return $this->redirect($this->generateUrl('user_formation_module_organisation_scorm', [
                                'slugSession' => $session->getSlug(),
                                'slugModule' => $slugModule
                            ]));
            }

            $sco = $module->getScorm()->getScos();
            $tracking = $scormManager->generateScoTracking($session, $sco[0], $this->getUser());
            $sessionModuleInfo = $em->getRepository('App\Entity\PlanningManagement\SessionFormationPathModule')->findOneBy(['session' => $session, 'module' => $module]);

            return $this->render('UserFrontManagement/page_scorm.html.twig', [
                'currentModule' => $module,
                'userModule' => $userModule,
                'currentFormation' => $session->getFormationPath()->getSlug(),
                'session' => $session,
                'scorm' => $module->getScorm(),
                'scoEntryUrl' => dirname($module->getScormPath()) . '/' . $sco[0]->getEntryUrl(),
                'sco' => $sco[0],
                'tracking' => $tracking,
                'sessionModuleInfo' => $sessionModuleInfo
          ]);
        } else {
            return $this->redirect($this->generateUrl('home_dashboard', []));
        }
    }

    /**
     * scorm commit
     *
     * @Route("/home/formation/scormCommit/{session_slug}/{module_id}/{sco_uuid}/{mode}",  name="user_formation_module_organisation_scorm_commit", defaults={"mode": "log"}, methods={"PUT", "POST"})
     *
     * @return JsonResponse
     */
    public function scoCommit($session_slug, $module_id, $sco_uuid, $mode, Request $request, SessionRepository $sessionRepository, ModuleRepository $moduleRepository, ScoRepository $scoRepository, ScormManager $scormManager, ScoTrackingSerializer $scoTrackingSerializer)
    {
        $session = $sessionRepository->findOneBy([
          'slug' => $session_slug
        ]);

        $module = $moduleRepository->findOneBy([
            'id' => $module_id
        ]);

        $sco = $scoRepository->findOneBy([
          'uuid' => $sco_uuid
        ]);

        if (!$module || !$session || !$sco) {
            throw new NotFoundHttpException($translator->trans('not_found_http_exception'));
        }

        $data = $this->decodeRequest($request);
        $tracking = $scormManager->updateScoTracking($session, $sco, $this->getUser(), $mode, $data);

        return new JsonResponse($scoTrackingSerializer->serialize($tracking), 200);
    }

    /**
     * @param Request $request
     *
     * @return array
     *
     * @throws InvalidDataException
     */
    protected function decodeRequest(Request $request)
    {
        $decodedRequest = json_decode($request->getContent(), true);

        if (null === $decodedRequest) {
            throw new InvalidDataException('Invalid request content sent.', []);
        }

        return $decodedRequest;
    }

    /**
     * Update User Page scorm Follow Duration
     *
     * @Route("/formation/pageScormDuration/{session}/{module}", name="user_formation_module_organisation_duration_scorm", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function pageDuration(Request $request, Session $session, Module $module, UserModuleFollowRepository $userModuleFollowRepository,  UserFormationSessionFollowRepository $userFormationSessionRepositor, LoggerInterface $logger)
    {
        $em = $this->getDoctrine()->getManager();

        $usermoduleFollow = $userModuleFollowRepository->findOneBy(['module' => $module, 'session' => $session, 'user' => $this->getUser()]);
        $userFormationSessionFollow = $userFormationSessionRepositor->findOneBy(['session' => $session, 'user' => $this->getUser()]);

        $duration = $request->request->get('duration');

        if ($usermoduleFollow) {
            $today = new \DateTime('now');
            if($today < $session->getClosingDate() && $usermoduleFollow->getEndDate() == null){
                $newTotDurationP = $usermoduleFollow->getDurationTotalSec() + 60;
                //tracking
                $usermoduleFollow->setDurationTotalSec($newTotDurationP);
                $usermoduleFollow->setDurationLastSessionSec($duration);
                $usermoduleFollow->setLastConnexion(new \DateTime('now'));
            }
        } 

        try {
            $em->persist($usermoduleFollow);
            $em->flush();

            if($userFormationSessionFollow){
                $totalTimeSecSession = $userModuleFollowRepository->getTotalTimeSecSession($this->getUser(), $session);
                $userFormationSessionFollow->setDurationTotalSec($totalTimeSecSession);
                $em->persist($userFormationSessionFollow);
                $em->flush();
            }

            return new JsonResponse([
                'module' => $module->getId(),
                'session' => $session->getId(),
                'duration' => $duration,
            ]);
        } catch (\Doctrine\DBAL\DBALException $e) {
            return new JsonResponse(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()]);
        }
    }
}
