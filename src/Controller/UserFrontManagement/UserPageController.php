<?php

namespace App\Controller\UserFrontManagement;

use App\Entity\FormationManagement\Course;
use App\Entity\FormationManagement\FormationPath;
use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\Page;
use App\Entity\PlanningManagement\Session;
use App\Entity\UserFrontManagement\UserPageFollow;
use App\Event\UserFrontManagement\PageEvent;
use App\Repository\UserFrontManagement\UserCourseFollowRepository;
use App\Repository\UserFrontManagement\UserPageFollowRepository;
use App\Repository\UserFrontManagement\UserModuleFollowRepository;
use App\Repository\UserFrontManagement\UserFormationSessionFollowRepository;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/apprenant")
 */
class UserPageController extends AbstractController
{
    /**
     * Fonction choisit un test ou une page
     *
     * @Route("/formation/page/{slugSession}/{formationPath}/{slugModule}/{slugChapter}/{slugPage}", name="user_formation_module_organisation", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function page(Request $request, string $slugSession, FormationPath $formationPath = null, string $slugModule, string $slugChapter = null, string $slugPage = null, UserCourseFollowRepository $userCourseFollowRepository, UserPageFollowRepository $userPageFollowRepository, EventDispatcherInterface $dispatcher)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();

        $currentModule = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBy(['slug' => $slugModule]);

        if (!$currentModule instanceof Module) {
            throw new NotFoundHttpException('Module Not Found Exception');
        }
        if ($slugSession != 'preview') {
            $currentSession = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy(['slug' => $slugSession]);
            if (!$currentSession instanceof Session) {
                throw new NotFoundHttpException('Session Not Found Exception');
            }
        } elseif ($slugSession == 'preview' && $this->get('security.authorization_checker')->isGranted('ROLE_CONCEPTEUR')) {
            //$currentSession = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy(array("id" => Session::PRE_VUE));
            $currentSession = null;
            $formationPath = null;
        //$currentSession = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy(array("id" => Session::PRE_VUE));
        } else {
            throw new BadRequestHttpException('Bad Request Exception');
        }

        if ($slugSession != 'preview') {
            $sessionModuleInfo = $em->getRepository('App\Entity\PlanningManagement\SessionFormationPathModule')->findOneBy(['session' => $currentSession, 'module' => $currentModule]);
            // TEST IF PRETEST redirection
            foreach ($currentModule->getModuleTests() as $moduleTest) {
                $now = new \DateTime('now');
                if ($sessionModuleInfo->getOpeningDate() <= $now and $now <= $sessionModuleInfo->getClosingDate()) {
                    if ($moduleTest->getTest()->getTypeTest()->getConditional() == 'pretest') {
                        $elem['pretest'] = $moduleTest->getTest();

                        $userPreTest = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findPreTestByTestAndUserIdAndModuleRef($user->getId(), $moduleTest->getTest()->getId(), $moduleTest->getModule()->getReference(), $currentSession->getId());

                        $userPreTest = end($userPreTest);

                        if ($userPreTest) {
                            if (($userPreTest->getLastIndexQuestion() == -1 && $userPreTest->getTentative() < $userPreTest->getNumberTry() && $userPreTest->getDatePass() == null) || $userPreTest->getScore() == -1) {
                                //USER PRETEST SCORE
                                return $this->redirect($this->generateUrl('test_front_begin', [
                                    'sessionSlug' => $currentSession->getSlug(),
                                    'moduleSlug' => $currentModule->getSlug(),
                                    'testSlug' => $moduleTest->getTest()->getSlug()
                                ]));
                            }
                        } else {
                            //NO USER PRETEST -> REDIRECT BEGIN TEST
                            return $this->redirect($this->generateUrl('test_front_begin', [
                                'sessionSlug' => $currentSession->getSlug(),
                                'moduleSlug' => $currentModule->getSlug(),
                                'testSlug' => $moduleTest->getTest()->getSlug()
                            ]));
                        }
                    }
                }
            }
        } else {
            $sessionModuleInfo = null;
        }

        unset($currentChapter);

        if ($slugChapter) {
            $currentChapter = $em->getRepository('App\Entity\FormationManagement\Course')->findBy(['slug' => $slugChapter]);
        } else {
            $currentChapter = $em->getRepository('App\Entity\FormationManagement\Course')->findFirstChapter($currentModule);
        }

        if (!$currentChapter[0] instanceof Course) {
            throw new NotFoundHttpException('Course Not Found Exception');
        }
        unset($currentPage);
        if ($slugPage == null) {
            $currentPage = $em->getRepository('App\Entity\FormationManagement\Page')->findFirstPage($currentChapter[0]);
        } else {
            $currentPage = $em->getRepository('App\Entity\FormationManagement\Page')->findBy(['slug' => $slugPage, 'course' => $currentChapter[0]]);
        }

        if (!$currentPage[0] instanceof Page) {
            throw new NotFoundHttpException('Page Not Found Exception');
        }
        $validationVia = [];
        foreach ($currentModule->getValidationModes() as $value) {
            $validationVia[$value->getId()] = $value->getTitle();
        }

        //Next page
        $nextPage = $em->getRepository('App\Entity\FormationManagement\Page')->findNextPage($formationPath, $currentModule, $currentChapter[0], $currentPage[0]);

        //Prev page
        $prevPage = $em->getRepository('App\Entity\FormationManagement\Page')->findPrevPage($formationPath, $currentModule, $currentChapter[0], $currentPage[0]);
        $localisation = $request->getLocale();

        $inSummaryPrevPage = $em->getRepository('App\Entity\FormationManagement\Page')->findInSummaryPrevPage($formationPath, $currentModule, $currentChapter[0], $currentPage[0]);

        if ($slugSession != 'preview') {
            //CHECK LECTURE DONE & IF NO PRE TEST OR EVAL VALIDATION -> MODULE SUCCESS
            $userModule = $em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')->findOneBy([
                'user' => $user,
                'module' => $currentModule,
                'session' => $currentSession,
            ]); //$this->getModulesPercentage($user, $currentModule[0], $currentSession, $em);
        } else {
            $userModule = null;
        }
        if ($slugSession != 'preview') {
            $numberOfPages = $this->getModuleNumberPages($currentModule, false);
        }else{
            $numberOfPages = $this->getModuleNumberPages($currentModule, true);
        }

        if ($slugSession != 'preview') {
            $readenPages = count($em->getRepository('App\Entity\UserFrontManagement\UserPageFollow')->findBy([
                'user' => $user,
                'module' => $currentModule,
                'session' => $currentSession,
            ]));
        } else {
            $readenPages = null;
        }

        $indexOfPage = 0;
        $testSamePage = true;

        if ($slugSession != 'preview') {
            foreach ($currentModule->getModuleCourses() as $course) {
                foreach ($course->getCourse()->getPages() as $page) {
                    if($page->getIsValid()){
                        if ($testSamePage) {
                            if ($currentPage[0]->getId() === $page->getId()) {
                                $testSamePage = false;
                            } else {
                                $indexOfPage++;
                            }
                        }

                        $userPageFollow = $userPageFollowRepository->findOneBy(['user' => $user, 'session' => $currentSession, 'module' => $course->getModule(), 'course' => $course->getCourse(), 'page' => $page], ['LastConnexion' => 'DESC']);
                        if ($userPageFollow) {
                            $page->setUserPageFollow($userPageFollow);
                        }
                    }
                }

                $userCourseFollow = $userCourseFollowRepository->findOneBy(['user' => $user, 'session' => $currentSession, 'module' => $course->getModule(), 'course' => $course->getCourse()], ['LastConnexion' => 'DESC']);
                if ($userCourseFollow) {
                    $course->setUserCourseFollow($userCourseFollow);
                }
            }
        }

        if ($slugSession != 'preview') {
            $userPage = $em->getRepository('App\Entity\UserFrontManagement\UserPageFollow')->findOneBy([
                'user' => $user,
                'page' => $currentPage[0],
                'session' => $currentSession,
            ]);
        } else {
            $userPage = null;
        }

        if ($slugSession != 'preview') {
            $userModules = $em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')->findBy([
                'session' => $currentSession,
                'user' => $user
            ]);
        } else {
            $userModules = null;
        }

        if ($slugSession != 'preview') {
            // dispatcher PageEvent
            $event = new PageEvent($currentPage[0], $currentChapter[0], $currentModule, $currentSession, $user);
            $dispatcher->dispatch(PageEvent::NAME, $event);
        }

        return $this->render('UserFrontManagement/page.html.twig', [
            'userModules' => $userModules,
            'userPage' => $userPage,
            'currentChapter' => $currentChapter[0],
            'currentFormation' => $formationPath,
            'currentSession' => $currentSession,
            'currentModule' => $currentModule,
            'currentPage' => $currentPage[0],
            'nextPage' => $nextPage,
            'prevPage' => $prevPage,
            'localisation' => $localisation,
            'userModule' => $userModule,
            'numberPages' => $numberOfPages,
            'readenPages' => $readenPages, 
            'indexOfPage' => $indexOfPage + 1,
            'inSummaryPrevPage' => $inSummaryPrevPage,
            'sessionModuleInfo' => $sessionModuleInfo,
            'slugSession' => $slugSession
        ]);
    }

    private function getModuleNumberPages($module, $preview)
    {
        $numberOfPages = 0;
        foreach ($module->getModuleCourses() as $moduleCourse) {
            if ($moduleCourse->getModule()->getIsValid() || $preview) {
                foreach ($moduleCourse->getCourse()->getPages() as $page) {
                    if ($page->getIsValid()) {
                        $numberOfPages++;
                    }
                }
            }
        }

        return $numberOfPages;
    }

    /**
     * Update User Page Fellow Duration
     *
     * @Route("/formation/pageDuration/{session}/{module}/{course}/{page}", name="user_formation_module_organisation_duration", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function pageDuration(Request $request, Session $session, Module $module, Course $course, Page $page, UserPageFollowRepository $userPageFollowRepositor, UserCourseFollowRepository $userCourseFollowRepositor, UserModuleFollowRepository $userModuleFollowRepositor, UserFormationSessionFollowRepository $userFormationSessionRepositor,LoggerInterface $logger)
    {
        $em = $this->getDoctrine()->getManager();

        $userPageFollow = $userPageFollowRepositor->findOneBy(['page' => $page, 'course' => $course, 'module' => $module, 'session' => $session, 'user' => $this->getUser()]);
        $userCourseFollow = $userCourseFollowRepositor->findOneBy(['course' => $course, 'module' => $module, 'session' => $session, 'user' => $this->getUser()]);
        $userModuleFollow = $userModuleFollowRepositor->findOneBy(['module' => $module, 'session' => $session, 'user' => $this->getUser()]);
        $userFormationSessionFollow = $userFormationSessionRepositor->findOneBy(['session' => $session, 'user' => $this->getUser()]);

        $duration = $request->request->get('duration');

        if ($userPageFollow) {
            $today = new \DateTime('now');
            if($today <= $session->getClosingDate() && $userModuleFollow->getEndDate() == null){
                $newTotDurationP = $userPageFollow->getDurationTotal()->getTimestamp() + 15;
                $durationTotalP = new \DateTime();
                $durationTotalP->setTimestamp($newTotDurationP);
                $durationSessionP = new \DateTime();
                $durationSessionP->setTimestamp($duration);

                $newTotDurationSec = $userPageFollow->getDurationTotalSec() + 15;
               
                //tracking
                $userPageFollow->setDurationTotal($durationTotalP);
                $userPageFollow->setDurationLastSession($durationSessionP);
                $userPageFollow->setDurationTotalSec($newTotDurationSec);
                $userPageFollow->setDurationLastSessionSec($duration);
                $userPageFollow->setLastConnexion(new \DateTime('now'));

                
               
            }
        } else {
            $userPageFollow = new UserPageFollow();
            $userPageFollow->setPage($page);
            $userPageFollow->setRefPage($page->getReference());
            $userPageFollow->setSession($session);
            $userPageFollow->setModule($module);
            $userPageFollow->setCourse($course);
            //tracking
            $userPageFollow->setUser($user);
            $userPageFollow->setStartDate(new \DateTime('now'));
            $userPageFollow->setEndDate(new \DateTime('now'));
            $userPageFollow->setDurationTotal(new \DateTime('00:00:00'));
            $userPageFollow->setDurationLastSession(new \DateTime('00:00:00'));
            $userPageFollow->setLastConnexion(new \DateTime('now'));
        }

        try {
            $em->persist($userPageFollow);
            $em->flush();            
                
            if($userCourseFollow){
                $totalTimeSecCourse = $userPageFollowRepositor->getTotalTimeSecCourse($this->getUser(), $session, $module, $course);
                $userCourseFollow->setDurationTotalSec($totalTimeSecCourse);
                $em->persist($userCourseFollow);
                $em->flush();
            }
            
            if($userModuleFollow){
                $totalTimeSecModule = $userCourseFollowRepositor->getTotalTimeSecModule($this->getUser(), $session, $module);
                $userModuleFollow->setDurationTotalSec($totalTimeSecModule);
                $em->persist($userModuleFollow);
                $em->flush();
            }
            if($userFormationSessionFollow){
                $totalTimeSecSession = $userModuleFollowRepositor->getTotalTimeSecSession($this->getUser(), $session);
                $userFormationSessionFollow->setDurationTotalSec($totalTimeSecSession);
                $em->persist($userFormationSessionFollow);
                $em->flush();
            }

            return new JsonResponse([
                'page' => $page->getId(),
                'course' => $course->getId(),
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
