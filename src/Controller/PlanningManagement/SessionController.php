<?php

namespace App\Controller\PlanningManagement;

use App\Entity\FormationManagement\FormationPath;
use App\Entity\PlanningManagement\Animateur;
use App\Entity\PlanningManagement\Session;
use App\Entity\PlanningManagement\SessionFormationPathModule;
use App\Event\PlanningManagement\SessionEvent;
use App\Form\PlanningManagement\SessionFormationPathModuleType;
use App\Manager\AuditManager;
use App\Manager\UserModuleFollowManager;
use FOS\UserBundle\Model\UserManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/admin/planningManagement/session")
 */
class SessionController extends AbstractController
{
    /**
     * Show list of session
     *
     * @Route("/list", name="admin_session_list", defaults={"page": "1"}, methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_SESSION')")
     * @param Request $request
     * @param int $page
     * @return Response
     */
    public function list(Request $request, int $page)
    {
        $em = $this->getDoctrine()->getManager();

        $sessions = $em->getRepository('App\Entity\PlanningManagement\Session')->findBy([], ['closingDate' => 'DESC']); //findLatest($page);

        $sessionFilterForm = $this->createForm('App\Form\Filter\SessionFilterType');

        $sessionFilterForm->handleRequest($request);

        if ($sessionFilterForm->isSubmitted() && $sessionFilterForm->isValid()) {
            $formationPath = $sessionFilterForm['formationPath']->getData();
            $user = $sessionFilterForm['user']->getData();
            $division = $sessionFilterForm['division']->getData();
            $team = $sessionFilterForm['team']->getData();

            $sessions = $em->getRepository('App\Entity\PlanningManagement\Session')->filterSession($formationPath, $user, $division, $team);
        }

        return $this->render('PlanningManagement/Session/list.html.twig', [
            'sessions' => $sessions,
            'total' => count($sessions),
            'sessionFilterForm' => $sessionFilterForm->createView(),
        ]);
    }

    /**
     * Show form for formationPath creatation
     *
     * @Route("/create", name="admin_session_create", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_SESSION')")
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param AuditManager $auditManager
     * @param SessionInterface $sfSession
     * @return RedirectResponse|Response
     */
    public function create(Request $request, TranslatorInterface $translator, AuditManager $auditManager, SessionInterface $sfSession)
    {
        $session = new Session();

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm('App\Form\PlanningManagement\SessionType', $session);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formationPath = $session->getFormationPath();

            $max = 0;

            if (count($formationPath->getSessions()) !== 1) {
                foreach ($formationPath->getSessions() as $formationPathSession) {
                    if ($formationPathSession->getSessionNumber() >= $max) {
                        $max = $formationPathSession->getSessionNumber() + 1;
                    }
                }
            } else {
                $max = 1;
            }

            $session->setSessionNumber($max);

            // ADD USERS TEAMS DIVISIONS
            if (count($session->getUsers())) {
                foreach ($session->getUsers() as $user) {
                    $test = null;
                    $user = $em->getRepository('App\Entity\UserManagement\User')->findBy(['id' => $user->getId()]);
                    // if meme formation is not underway
                    if (!$em->getRepository('App\Entity\UserManagement\User')->checkByFormationAndDate($user, $session)) {
                        $session->addUser($user);
                    }
                }
            }

            $em->persist($session);
            $em->flush();

            $auditManager->generateAudit(null, $session, 'add', $this->getUser());

            $sfSession->getFlashBag()->add('success', $translator->trans('La session est crée'));

            return $this->redirect($this->generateUrl('admin_session_edit_module', [
                'slug' => $session->getSlug()
            ]));
        }

        return $this->render('PlanningManagement/Session/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Show form to edit saved formationPath
     *
     * @Route("/edit/{slug}", name="admin_session_edit", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_SESSION')")
     * @param Request $request
     * @param $slug
     * @param TranslatorInterface $translator
     * @param AuditManager $auditManager
     * @param SessionInterface $sfSession
     * @return RedirectResponse|Response
     */
    public function edit(Request $request, $slug, TranslatorInterface $translator, AuditManager $auditManager, SessionInterface $sfSession)
    {
        $em = $this->getDoctrine()->getManager();

        $session = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy([
            'slug' => $slug,
        ]);
        $oldEntity = clone $session;
        $oldFormationPath = $session->getFormationPath();
        $oldOpeningDate = $session->getOpeningDate();
        $oldClosingDate = $session->getClosingDate();

        $form = $this->createForm('App\Form\PlanningManagement\SessionType', $session);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sessionFormationPathModules = $em->getRepository('App\Entity\PlanningManagement\SessionFormationPathModule')->findBy([
                'session' => $session
            ]);
            if ($sessionFormationPathModules != null) {
                if ($session->getFormationPath() != $oldFormationPath) {
                    foreach ($sessionFormationPathModules as $sessionFormationPathModule) {
                        $em->remove($sessionFormationPathModule);
                        $em->flush();
                    }
                } elseif ($session->getOpeningDate() != $oldOpeningDate || $session->getClosingDate() != $oldClosingDate) {
                    foreach ($sessionFormationPathModules as $sessionFormationPathModule) {
                        if ($sessionFormationPathModule->getOpeningDate() < $session->getOpeningDate()) {
                            $sessionFormationPathModule->setOpeningDate($session->getOpeningDate());
                            $em->persist($sessionFormationPathModule);
                            $em->flush();
                        }
                        if ($sessionFormationPathModule->getClosingDate() > $session->getClosingDate()) {
                            $sessionFormationPathModule->setClosingDate($session->getClosingDate());
                            $em->persist($sessionFormationPathModule);
                            $em->flush();
                        }
                        if ($sessionFormationPathModule->getOpeningDateEvaluation() < $session->getOpeningDate()) {
                            $sessionFormationPathModule->setOpeningDateEvaluation($session->getOpeningDate());
                            $em->persist($sessionFormationPathModule);
                            $em->flush();
                        }
                        if ($sessionFormationPathModule->getClosingDateEvaluation() > $session->getClosingDate()) {
                            $sessionFormationPathModule->setClosingDateEvaluation($session->getClosingDate());
                            $em->persist($sessionFormationPathModule);
                            $em->flush();
                        }
                    }
                }
            }

            $em->persist($session);
            $em->flush();

            $auditManager->generateAudit($oldEntity, $session, 'edit', $this->getUser());

            return $this->redirect($this->generateUrl('admin_session_edit_module', [
                'slug' => $session->getSlug()
            ]));
        }

        return $this->render('PlanningManagement/Session/edit.html.twig', [
            'form' => $form->createView(),
            'session' => $session
        ]);
    }

    /**
     * Show form to edit saved formationPath
     *
     * @Route("/edit_module/{slug}", name="admin_session_edit_module", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_SESSION')")
     * @param Request $request
     * @param $slug
     * @param TranslatorInterface $translator
     * @param AuditManager $auditManager
     * @return Response
     */
    public function editModule(Request $request, $slug, TranslatorInterface $translator, AuditManager $auditManager)
    {
        $em = $this->getDoctrine()->getManager();

        $session = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy([
            'slug' => $slug,
        ]);
        $oldEntity = clone $session;

        $form = $this->createForm('App\Form\PlanningManagement\SessionType', $session);

        $sessionFormationPathModules = $em->getRepository('App\Entity\PlanningManagement\SessionFormationPathModule')->findBy([
            'session' => $session
        ]);

        if (!$sessionFormationPathModules) {
            // if sessionFormationPathModules exists pas
            $i = 0;
            foreach ($session->getFormationPath()->getFormationPathModules() as $formationPathModule) {
                $sessionFormationPathModules[$i] = new SessionFormationPathModule();
                $sessionFormationPathModules[$i]->setSession($session);
                $sessionFormationPathModules[$i]->setModule($formationPathModule->getModule());
                $sessionFormationPathModules[$i]->setOpeningDate($session->getOpeningDate());
                $sessionFormationPathModules[$i]->setClosingDate($session->getClosingDate());
                $sessionFormationPathModules[$i]->setOpeningDateEvaluation($session->getOpeningDate());
                $sessionFormationPathModules[$i]->setClosingDateEvaluation($session->getClosingDate());
                $em->persist($sessionFormationPathModules[$i]);
                $em->flush();
                $i++;
            }
        }
        $auditManager->generateAudit($oldEntity, $session, 'edit', $this->getUser());

        return $this->render('PlanningManagement/Session/edit_module.html.twig', [
            'sessionFormationPathModules' => $sessionFormationPathModules,
            'form' => $form->createView(),
            'session' => $session
        ]);
    }

    /**
     *
     * @Route("/edit_presentiel/{slug}", name="admin_session_edit_presentiel", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_SESSION')")
     * @param Request $request
     * @param $slug
     * @param TranslatorInterface $translator
     * @param AuditManager $auditManager
     * @return Response
     */
    public function editPresentielModule(Request $request, $slug, TranslatorInterface $translator, AuditManager $auditManager)
    {
        $em = $this->getDoctrine()->getManager();
        $animateurs = $em->getRepository(Animateur::class)->findAll();
        $session = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy([
            'slug' => $slug,
        ]);
        $form = $this->createForm(SessionFormationPathModuleType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $form->getData();
        }

        return $this->render('PlanningManagement/Session/edit_presentiel.html.twig', [
            'form' => $form->createView(),
            'animateurs' => $animateurs,
            'session' => $session
        ]);
    }

    /**
     * Show form to edit saved formationPath
     *
     * @Route("/edit_user/{slug}", name="admin_session_edit_user", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_SESSION')")
     * @param Request $request
     * @param $slug
     * @param UserManagerInterface $userManager
     * @param TranslatorInterface $translator
     * @param EventDispatcherInterface $dispatcher
     * @param UserModuleFollowManager $userModuleFollowManager
     * @param AuditManager $auditManager
     * @param SessionInterface $sfSession
     * @return RedirectResponse|Response
     */
    public function editUser(Request $request, $slug, UserManagerInterface $userManager, TranslatorInterface $translator, EventDispatcherInterface $dispatcher, UserModuleFollowManager $userModuleFollowManager, AuditManager $auditManager, SessionInterface $sfSession)
    {
        $em = $this->getDoctrine()->getManager();

        $session = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy([
            'slug' => $slug,
        ]);

        $oldEntity = clone $session;

        $form = $this->createForm('App\Form\PlanningManagement\SessionType', $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // CHECK REMOVED TEAMS
            $oldTeams = $em->getRepository('App\Entity\UserManagement\Team')->findBySessionId($session->getId());
            foreach ($oldTeams as $team) {
                $team = $em->getRepository('App\Entity\UserManagement\Team')->find($team->getId());
                if (!$session->getTeams()->contains($team)) {
                    $session->removeTeam($team);
                    $teamUsers = $em->getRepository('App\Entity\UserManagement\User')->findBy([
                        'team' => $team
                    ]);
                    foreach ($teamUsers as $user) {
                        $session->removeUser($user);
                        $userModuleFollowManager->deleteUserModule($session, $user);
                    }
                }
            }

            // CHECK REMOVED DIVISONS
            $oldDivisions = $em->getRepository('App\Entity\UserManagement\Division')->findBySessionId($session->getId());
            foreach ($oldDivisions as $division) {
                $division = $em->getRepository('App\Entity\UserManagement\Division')->find($division->getId());
                if (!$session->getDivisions()->contains($division)) {
                    $session->removeDivision($division);
                    $divisionsUsers = $em->getRepository('App\Entity\UserManagement\User')->findBy([
                        'division' => $division
                    ]);
                    foreach ($divisionsUsers as $user) {
                        $session->removeUser($user);
                        $userModuleFollowManager->deleteUserModule($session, $user);
                    }
                }
            }

            // CHECK REMOVED USERS
            $oldUsers = $em->getRepository('App\Entity\UserManagement\User')->findBySessionId($session->getId());
            foreach ($oldUsers as $user) {
                $user = $userManager->findUserBy(['id' => $user->getId()]);
                if (!$session->getUsers()->contains($user)) {
                    $session->removeUser($user);
                    $userModuleFollowManager->deleteUserModule($session, $user);
                }
            }

            // ADD NEW TEAMS
            if (!$session->getTeams()->isEmpty()) {
                $teams = $session->getTeams();

                foreach ($teams as $team) {
                    $team = $em->getRepository('App\Entity\UserManagement\Team')->find($team->getId());
                    $team->removeSession($session);
                    $team->addSession($session);
                    $teamUsers = $em->getRepository('App\Entity\UserManagement\User')->findBy([
                        'team' => $team
                    ]);
                    foreach ($teamUsers as $user) {
                        if (!$user->getSessions()->contains($session)) {
                            if (!$em->getRepository('App\Entity\UserManagement\User')->checkByFormationAndDate($user, $session)) {
                                $user->removeSession($session);
                                $user->addSession($session);  //userModuleFollow created or isDeleted == flase;
                            }
                        }
                    }
                    $em->persist($team);
                }
            }

            // ADD NEW DIVISIONS
            if (!$session->getDivisions()->isEmpty()) {
                $divisions = $session->getDivisions();

                foreach ($divisions as $division) {
                    $division = $em->getRepository('App\Entity\UserManagement\Division')->find($division->getId());
                    $division->removeSession($session);
                    $division->addSession($session);
                    $divisionsUsers = $em->getRepository('App\Entity\UserManagement\User')->findBy([
                        'division' => $division
                    ]);
                    foreach ($divisionsUsers as $user) {
                        if (!$user->getSessions()->contains($session)) {
                            if (!$em->getRepository('App\Entity\UserManagement\User')->checkByFormationAndDate($user, $session)) {
                                $user->removeSession($session);
                                $user->addSession($session);  //userModuleFollow created or isDeleted == flase;
                            }
                        }
                    }
                    $em->persist($division);
                }
            }

            // ADD NEW USERS
            if (!$session->getUsers()->isEmpty()) {
                $users = $session->getUsers();

                foreach ($users as $user) {
                    $user = $em->getRepository('App\Entity\UserManagement\User')->find($user->getId());
                    if (!$em->getRepository('App\Entity\UserManagement\User')->checkByFormationAndDate($user, $session)) {
                        $user->removeSession($session);
                        $user->addSession($session);  //userModuleFollow created or isDeleted == flase;
                    }
                    $em->persist($user);
                }
            }

            $session->setUpdateDate();
            $em->persist($session);
            $em->flush();

            $auditManager->generateAudit($oldEntity, $session, 'edit', $this->getUser());

            // dispatcher SessionEvent
            $event = new SessionEvent($session);
            $dispatcher->dispatch(SessionEvent::NAME, $event);

            $sfSession->getFlashBag()->add('success', $translator->trans('La session est mise à jour'));

            return $this->redirect($this->generateUrl('admin_session_list', []));
        }

        $team = null;
        $division = null;
        $lab = null;
        $apprenants = $em->getRepository('App\Entity\UserManagement\User')->findAllApprenant($team, $division, $lab);
        $oldApprenants = $em->getRepository('App\Entity\UserManagement\User')->findBySessionId($session->getId());

        $newApprenants = [];
        foreach ($apprenants as $apprenant) {
            if (!in_array($apprenant, $oldApprenants)) {
                $newApprenants[] = $apprenant;
            }
        }

        $teams = $em->getRepository('App\Entity\UserManagement\Team')->findBy(['isValid' => 1]);
        $divisions = $em->getRepository('App\Entity\UserManagement\Division')->findBy(['isValid' => 1]);
        $labs = $em->getRepository('App\Entity\UserManagement\Laboratory')->findBy(['isValid' => 1]);

        return $this->render('PlanningManagement/Session/edit_user.html.twig', [
            'form' => $form->createView(),
            'session' => $session,
            'apprenants' => $newApprenants,
            'teams' => $teams,
            'divisions' => $divisions,
            'labs' => $labs,
            'oldApprenants' => $oldApprenants,
        ]);
    }

    /**
     * Show form to edit saved formationPath
     *
     * @Route("/edit_module_flash/{id}", name="admin_session_edit_module_flash", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_SESSION')")
     */
    public function updateFlash(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('App\Entity\PlanningManagement\SessionFormationPathModule')->findOneBy([
            'id' => $id,
        ]);
        $output = [];

        if ($entity) {
            $output['id'] = $request->get('pk');
            $output['name'] = $request->get('name');
            $output['value'] = $request->get('value');

            switch ($request->get('name')) {
                case 'openingDate':
                    $entity->setOpeningDate(new \Datetime($request->get('value')));
                    break;
                case 'closingDate':
                    $entity->setClosingDate(new \Datetime($request->get('value')));
                    break;
                case 'openingDateEvaluation':
                    $entity->setOpeningDateEvaluation(new \Datetime($request->get('value')));
                    break;
                case 'closingDateEvaluation':
                    $entity->setClosingDateEvaluation(new \Datetime($request->get('value')));
                    break;
                default:
                    break;
            }

            $em->flush($entity);
        }

        return new JsonResponse($output, 200);
    }
}
