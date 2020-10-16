<?php

namespace App\Controller\ServiceManagement;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;

class RechercheController extends AbstractController
{
    /**
     * Search formation, module, courses, page, lexique
     * @Route("/search", name="user_search", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function search(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if ($data['textToSearch'] != '' && strlen($data['textToSearch']) > 3) {
            $rep = ['status' => 'ok', 'textSearch' => $data['textToSearch']];

            return  new Response(json_encode($rep));
        } else {
            $rep = ['status' => 'false', 'textSearch' => $data['textToSearch']];

            return  new Response(json_encode($rep));
        }
    }

    /**
     * Search formation, module, courses, page, lexique
     * @Route("/search_result/{textSearch}/{pageFormation}/{pageModule}/{pageCourse}/{pagePage}", name="user_search_result", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function searchResult(Request $request, $textSearch, $pageFormation = 0, $pageModule = 0, $pageCourse = 0, $pagePage = 0)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        if ($data != null) {
            $rep = [
                'status' => 'ok',
                'textSearch' => $data['textSearch'],
                'pageFormation' => $data['pageFormation'],
                'pageModule' => $data['pageModule'],
                'pageCourse' => $data['pageCourse'],
                'pagePage' => $data['pagePage'],
        ];

            return  new Response(json_encode($rep));
        }
        //pagination
        $maxresult = 5;
        // $pageFormation = 0;
        // $pageModule = 0;
        // $pageCourse = 0;
        // $pagePage = 0;

        // if($data["pageFormation"]!="" && $data["pageFormation"] > 0){
        //     $pageFormation = $data["pageFormation"];
        // }
        // if($data["pageModule"]!="" && $data["pageModule"] > 0){
        //     $pageModule = $data["pageModule"];
        // }
        // if($data["pageCourse"]!="" && $data["pageCourse"] > 0){
        //     $pageCourse = $data["pageCourse"];
        // }
        // if($data["pagePage"]!="" && $data["pagePage"] > 0){
        //     $pagePage = $data["pagePage"];
        // }

        $formationSearch = $em->getRepository("App\Entity\FormationManagement\FormationPath")->searchFormationSession($user, $textSearch, $pageFormation, $maxresult);
        $countTotalresultFormation = $em->getRepository("App\Entity\FormationManagement\FormationPath")->searchFormationSessionCount($user, $textSearch);

        $moduleSearch = $em->getRepository("App\Entity\FormationManagement\Module")->searchModuleSession($user, $textSearch, $pageModule, $maxresult);
        $countTotalresultModule = $em->getRepository("App\Entity\FormationManagement\Module")->searchModuleSessionCount($user, $textSearch);

        $courseSearch = $em->getRepository("App\Entity\FormationManagement\Course")->searchCourseSession($user, $textSearch, $pageCourse, $maxresult);
        $countTotalresultCourse = $em->getRepository("App\Entity\FormationManagement\Course")->searchCourseSessionCount($user, $textSearch);

        $pageSearch = $em->getRepository("App\Entity\FormationManagement\Page")->searchPageSession($user, $textSearch, $pagePage, $maxresult);
        $countTotalresultPage = $em->getRepository("App\Entity\FormationManagement\Page")->searchPageSessionCount($user, $textSearch);


        $totalfind = $countTotalresultFormation + $countTotalresultModule + $countTotalresultCourse + $countTotalresultPage;

        return $this->render('ServiceManagement/searchResult.html.twig', [
        'textSearch' => $textSearch,
        'formationSearchs' => $formationSearch,
        'moduleSearchs' => $moduleSearch,
        'courseSearchs' => $courseSearch,
        'pageSearchs' => $pageSearch,
        'totalfind' => $totalfind,
        'countTotalresultFormation' => $countTotalresultFormation,
        'countTotalresultModule' => $countTotalresultModule,
        'countTotalresultCourse' => $countTotalresultCourse,
        'countTotalresultPage' => $countTotalresultPage,
        'pageFormation' => $pageFormation,
        'pageModule' => $pageModule,
        'pageCourse' => $pageCourse,
        'pagePage' => $pagePage,
        'maxresult' => $maxresult,
    ]);
    }
 
    /**
     * Search formation, user access
     * @Route("/search_acces/formation/{slug}/{textSearch}", name="user_search_access_formation", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function searchAccessFormation(Request $request, $slug, $textSearch,SessionInterface $sfSession, TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $userStartFormation = $em->getRepository('App\Entity\PlanningManagement\Session')->findlastSessionByFormationandUser($user, $slug);
        if ($userStartFormation != null) {
            return $this->redirect(($this->generateUrl('user_formation_module', [
                'slugSession' => $userStartFormation->getSlug(),
            ])));
        } else {
            $sfSession->getFlashBag()->add('error', $translator->trans('search.error'));
            return $this->redirect(($this->generateUrl('user_search_result', [
                'textSearch' => $textSearch,
            ])));
        }
    }

    /**
     * Search page, user access
     * @Route("/search_acces/module/{slug}/{textSearch}", name="user_search_access_module", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function searchAccessModule(Request $request, $slug, $textSearch,SessionInterface $sfSession, TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $module = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBy(array('slug'=>$slug));
        $userStartModule = $em->getRepository('App\Entity\PlanningManagement\SessionFormationPathModule')->findlastSessionByModuleandUser($user, $slug);
        $session = null;
        foreach($userStartModule as $sfm){
            $listModules = $sfm->getSession()->getFormationPath()->listModules();
            if(in_array($module,$listModules)){
                $session = $sfm->getSession();
            }
        }
        if ($session != null) {
            return $this->redirect(($this->generateUrl('user_formation_module', [
                'slugSession' => $session->getSlug(),
            ])));
        } else {
            $sfSession->getFlashBag()->add('error', $translator->trans('search.error'));
            return $this->redirect(($this->generateUrl('user_search_result', [
                'textSearch' => $textSearch,
            ])));
        }

    }

    /**
     * Search page, user access
     * @Route("/search_acces/course/{slug}/{textSearch}", name="user_search_access_course", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function searchAccessCourse(Request $request, $slug, $textSearch,SessionInterface $sfSession, TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $course = $em->getRepository('App\Entity\FormationManagement\Course')->findOneBy(array('slug'=>$slug));
        $module = $course->getModuleCourses()[0]->getModule();
        $userStartCourse = $em->getRepository('App\Entity\PlanningManagement\SessionFormationPathModule')->findlastSessionByModuleandUser($user, $module);
        $session = null;
        foreach($userStartCourse as $sfm){
            $listModules = $sfm->getSession()->getFormationPath()->listModules();
            if(in_array($module,$listModules)){
                $session = $sfm->getSession();
            }
        }
        if ($session != null) {
                return $this->redirect(($this->generateUrl('user_formation_module', [
                    'slugSession' => $session->getSlug(),
                ])));
            } else {
                $sfSession->getFlashBag()->add('error', $translator->trans('search.error'));
                return $this->redirect(($this->generateUrl('user_search_result', [
                    'textSearch' => $textSearch,
                ])));
            }
    }

    /**
     * Search page, user access
     * @Route("/search_acces/page/{slug}/{textSearch}", name="user_search_access_page", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function searchAccessPage(Request $request, $slug, $textSearch,SessionInterface $sfSession, TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $page = $em->getRepository('App\Entity\FormationManagement\Page')->findOneBy(array('slug'=>$slug));
        $module = $page->getCourse()->getModuleCourses()[0]->getModule();

        $userStartPage = $em->getRepository('App\Entity\PlanningManagement\SessionFormationPathModule')->findlastSessionByModuleandUser($user, $module->getSlug());
        $session = null;
        foreach($userStartPage as $sfm){
            $listModules = $sfm->getSession()->getFormationPath()->listModules();
            if(in_array($module,$listModules)){
                $session = $sfm->getSession();
            }
        }
        if ($session != null) {
            return $this->redirect(($this->generateUrl('user_formation_module', [
                'slugSession' => $session->getSlug(),
            ])));
        } else {
            $sfSession->getFlashBag()->add('error', $translator->trans('search.error'));
            return $this->redirect(($this->generateUrl('user_search_result', [
                'textSearch' => $textSearch,
            ])));
        }
    }
}
