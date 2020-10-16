<?php

namespace App\Controller\UserManagement;

use App\Entity\UserManagement\Team;
use App\Manager\AuditManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class TeamController
 */
class TeamController extends AbstractController
{
    /**
     * Affiche la liste des Teams
     *
     * @Route("/admin/UserManagement/team/list", name="admin_team_list", methods={"GET"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function list(Request $request)
    {
        $manager = $this->getDoctrine()->getManager();

        $teams = $manager->getRepository('App\Entity\UserManagement\Team')->findAll();

        return $this->render('UserManagement/Team/list.html.twig', [
            'teams' => $teams,
        ]);
    }

    /**
     *  Affiche le formulaire d'ajout d'une team en Base de données.
     *
     * @Route("/admin/UserManagement/team/create", name="admin_team_create", methods={"GET", "POST"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function create(Request $request, SessionInterface $session, TranslatorInterface $translator, AuditManager $auditManager)
    {
        $team = new Team();

        $manager = $this->getDoctrine()->getManager();

        $form = $this->createForm("App\Form\UserManagement\TeamType", $team);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $team->setCreateUser($this->getUser());
            $team->setUpdateUser($this->getUser());

            $manager->persist($team);
            $manager->flush();

            $auditManager->generateAudit(null, $team, 'add', $this->getUser());

            $session->getFlashBag()->add('success', $translator->trans('team.flash.created'));

            return $this->redirect($this->generateUrl('admin_team_view', [
                        'slug' => $team->getSlug()]));
        }

        $laboratories = $manager->getRepository('App\Entity\UserManagement\Laboratory')->findAll();

        return $this->render('UserManagement/Team/create.html.twig', [
            'form' => $form->createView(),
            'laboratories' => $laboratories,
        ]);
    }

    /**
     *  Affiche une Team .
     *
     * @Route("/admin/UserManagement/view/{slug}", name="admin_team_view", methods={"GET"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function view($slug)
    {
        $manager = $this->getDoctrine()->getManager();

        $team = $manager->getRepository('App\Entity\UserManagement\Team')->findOneBy(['slug' => $slug]);

        if (!$team instanceof Team) {
            throw new BadRequestHttpException($this->get('translator')->trans('global.badRequestHttpException'));
        }

        return $this->render('UserManagement/Team/view.html.twig', [
            'team' => $team,
        ]);
    }

    /**
     *  Affiche le formulaire de modification d'une Team.
     *
     * @Route("/admin/UserManagement/team/edit/{slug}", name="admin_team_edit")
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function edit(Request $request, $slug, SessionInterface $session, TranslatorInterface $translator, AuditManager $auditManager)
    {
        $manager = $this->getDoctrine()->getManager();

        $team = $manager->getRepository('App\Entity\UserManagement\Team')->findOneBy(['slug' => $slug]);

        $old_team = clone $team;

        if (!$team instanceof Team) {
            throw new BadRequestHttpException($translator->trans('global.badRequestHttpException'));
        }

        $form = $this->createForm("App\Form\UserManagement\TeamType", $team);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $team->setCreateUser($this->getUser());
            $team->setUpdateUser($this->getUser());

            $manager->persist($team);
            $manager->flush();

            $auditManager->generateAudit($old_team, $team, 'edit', $this->getUser());

            $session->getFlashBag()->add('success', $translator->trans('team.flash.updated'));

            return $this->redirect($this->generateUrl('admin_team_view', [
                        'slug' => $team->getSlug()]));
        }

        $laboratories = $manager->getRepository('App\Entity\UserManagement\Laboratory')->findAll();

        return $this->render('UserManagement/Team/edit.html.twig', [
            'team' => $team,
            'form' => $form->createView(),
            'laboratories' => $laboratories
        ]);
    }

    /**
     * activer ou desactiver le team.
     *
     * @Route("/admin/UserManagement/team/active/{slug}", name="admin_team_active", methods={"GET", "POST"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function active($slug, SessionInterface $session, TranslatorInterface $translator, AuditManager $auditManager)
    {
        $manager = $this->getDoctrine()->getManager();

        $team = $manager->getRepository('App\Entity\UserManagement\Team')->findOneBy(['slug' => $slug]);

        if (!$team instanceof Team) {
            throw new BadRequestHttpException($translator->trans('global.badRequestHttpException'));
        }

        $old_team = clone $team;

        if (sizeof($team->getUsers()) == 0) {
            if ($team->getIsValid()) {
                $team->setIsValid(false);
                $manager->persist($team);
                $manager->flush();
                $action = 'disable';
            } else {
                $team->setIsValid(true);
                $manager->persist($team);
                $manager->flush();
                $action = 'enable';
            }

            $auditManager->generateAudit($old_team, $team, $action, $this->getUser());

            $session->getFlashBag()->add('success', $translator->trans($action));
        }

        return $this->redirect($this->generateUrl('admin_team_list'));
    }
}
