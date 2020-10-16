<?php

namespace App\Controller\UserManagement;

use App\Entity\UserManagement\Division;
use App\Manager\AuditManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class DivisionController
 */
class DivisionController extends AbstractController
{
    /**
     * Affiche la liste des divisions
     *
     * @Route("/admin/UserManagement/division/list", name="admin_division_list", methods={"GET"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function list(Request $request)
    {
        $manager = $this->getDoctrine()->getManager();

        $divisions = $manager->getRepository('App\Entity\UserManagement\Division')->findAll();
        $countUsers = [];
        foreach ($divisions as $division) {
            $CountUserDiv = $manager->getRepository('App\Entity\UserManagement\Division')->getUsersDiv($division);
            $countUsers[$division->getSlug()] = $CountUserDiv;
        }

        return $this->render('UserManagement/Division/list.html.twig', [
            'divisions' => $divisions,
            'CountUserDiv' => $countUsers,
        ]);
    }

    /**
     *  Affiche le formulaire d'ajout d'une division en Base de données.
     *
     * @Route("/admin/UserManagement/division/create", name="admin_division_create", methods={"GET", "POST"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function create(Request $request, SessionInterface $session, TranslatorInterface $translator, AuditManager $auditManager)
    {
        $division = new Division();

        $manager = $this->getDoctrine()->getManager();

        $form = $this->createForm("App\Form\UserManagement\DivisionType", $division);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $division->setCreateUser($this->getUser());
            $division->setUpdateUser($this->getUser());

            $manager->persist($division);
            $manager->flush();

            $auditManager->generateAudit(null, $division, 'add', $this->getUser());

            $session->getFlashBag()->add('success', $translator->trans('division.flash.created'));

            return $this->redirect($this->generateUrl('admin_division_view', [
                'slug' => $division->getSlug()]));
        }

        return $this->render('UserManagement/Division/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     *  Affiche une division .
     *
     * @Route("/admin/UserManagement/division/view/{slug}", name="admin_division_view", methods={"GET"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function view($slug)
    {
        $manager = $this->getDoctrine()->getManager();

        $division = $manager->getRepository('App\Entity\UserManagement\Division')->findOneBy(['slug' => $slug]);

        if (!$division instanceof Division) {
            throw new BadRequestHttpException($this->get('translator')->trans('global.badRequestHttpException'));
        }

        return $this->render('UserManagement/Division/view.html.twig', [
            'division' => $division,
        ]);
    }

    /**
     *  Affiche le formulaire de modification d'une division.
     *
     * @Route("/admin/UserManagement/division/edit/{slug}", name="admin_division_edit")
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function edit(Request $request, $slug, SessionInterface $session, TranslatorInterface $translator, AuditManager $auditManager)
    {
        $manager = $this->getDoctrine()->getManager();

        $division = $manager->getRepository('App\Entity\UserManagement\Division')->findOneBy(['slug' => $slug]);

        $old_division = clone $division;

        if (!$division instanceof Division) {
            throw new BadRequestHttpException($this->get('translator')->trans('global.badRequestHttpException'));
        }

        $form = $this->createForm("App\Form\UserManagement\DivisionType", $division);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $division->setCreateUser($this->getUser());
            $division->setUpdateUser($this->getUser());

            $division->setRevision($division->getRevision() + 1);

            $manager->persist($division);
            $manager->flush();

            $auditManager->generateAudit($old_division, $division, 'edit', $this->getUser());

            $session->getFlashBag()->add('success', $translator->trans('division.flash.updated'));

            return $this->redirect($this->generateUrl('admin_division_view', [
                'slug' => $division->getSlug()]));
        }

        return $this->render('UserManagement/Division/edit.html.twig', [
            'division' => $division,
            'form' => $form->createView(),
        ]);
    }

    /**
     * activer ou desactiver le division.
     *
     * @Route("/admin/UserManagement/division/active/{slug}", name="admin_division_active", methods={"GET", "POST"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function active($slug, SessionInterface $session, TranslatorInterface $translator, AuditManager $auditManager)
    {
        $manager = $this->getDoctrine()->getManager();

        $division = $manager->getRepository('App\Entity\UserManagement\Division')->findOneBy(['slug' => $slug]);
        $users = $manager->getRepository('App\Entity\UserManagement\User')->findBy(['division' => $division]);

        $old_division = clone $division;

        if (!$division instanceof Division) {
            throw new BadRequestHttpException($translator->trans('global.badRequestHttpException'));
        }
        if (count($users) == 0) {
            if ($division->getIsValid()) {
                $division->setIsValid(false);
                $manager->persist($division);
                $manager->flush();
                $action = 'disable';
            } else {
                $division->setIsValid(true);
                $manager->persist($division);
                $manager->flush();
                $action = 'enable';
            }
            $auditManager->generateAudit($old_division, $division, $action, $this->getUser());

            $session->getFlashBag()->add('success', $translator->trans('division.flash.updated'));
        }

        return $this->redirect($this->generateUrl('admin_division_list'));
    }
}
