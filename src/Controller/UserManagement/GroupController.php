<?php

namespace App\Controller\UserManagement;

use App\Entity\UserManagement\Group;
use App\Entity\UserManagement\Role;
use App\Entity\UserManagement\User;
use App\Event\ServiceManagement\AuditTrailEvent;
use FOS\UserBundle\Controller\GroupController as BaseController;
use FOS\UserBundle\Event\FilterGroupResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseGroupEvent;
use FOS\UserBundle\FOSUserEvents;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class GroupController
 */
class GroupController extends BaseController
{
    /**
     * Affiche la liste des group
     *
     * @Route("/admin/UserManagement/group/list", name="admin_group_list", methods={"GET", "POST"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function listAction()
    {
        /** @var $groupManager \FOS\UserBundle\Model\GroupManagerInterface */
        $groupManager = $this->container->get('fos_user.group_manager');

        return $this->render('UserManagement/Group/list.html.twig', [
            'groups' => $groupManager->findGroups(),
        ]);
    }

    /**
     * Affiche le formulaire d'ajout de groupe et enregistre le groupe en Base de données.
     *
     * @Route("/admin/UserManagement/group/create", name="admin_group_create", methods={"GET", "POST"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function createAction(Request $request)
    {
        /** @var $groupManager \FOS\UserBundle\Model\GroupManagerInterface */
        $groupManager = $this->get('fos_user.group_manager');
        /** @var $formFactory \FOS\UserBundle\Form\Factory\FactoryInterface */
        //$formFactory = $this->get('fos_user.group.form.factory');
        /** @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $dispatcher = $this->get('event_dispatcher');

        $group = $groupManager->createGroup('');

        $dispatcher->dispatch(FOSUserEvents::GROUP_CREATE_INITIALIZE, new \FOS\UserBundle\Event\GroupEvent($group, $request));

        $form = $this->createForm("App\Form\UserManagement\GroupType", $group, ['roles' => $this->getRoles()]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) { // && $form->isValid() ) {
            $event = new FormEvent($form, $request);
            $dispatcher->dispatch(FOSUserEvents::GROUP_CREATE_SUCCESS, $event);

            $group_data = $request->get('user_group');
            if (isset($group_data['roles'])) {
                $group->setRoles($group_data['roles']);
            }

            $groupManager->updateGroup($group);

            if (null === $response = $event->getResponse()) {
                $url = $this->container->get('router')->generate('admin_group_view', ['groupName' => $group->getName()]);
                $response = new RedirectResponse($url);
            }

            $dispatcher->dispatch(FOSUserEvents::GROUP_CREATE_COMPLETED, new FilterGroupResponseEvent($group, $request, $response));

            $event = new AuditTrailEvent(null, $group, 'add', $this->getUser());
            $dispatcher->dispatch(AuditTrailEvent::NAME, $event);

            return $response;
        }

        return $this->render('UserManagement/Group/create.html.twig', [
            'form' => $form->createView(),
            'roles' => $this->getRoles()
        ]);
    }

    /**
     * Récupère les rôles disponibles.
     *
     * @return array
     */
    private function getRoles()
    {
        $roleHierarchy = $this->container->getParameter('security.role_hierarchy.roles');
        $roles = array_keys($roleHierarchy);

        foreach ($roles as $role) {
            $theRoles[$role] = $role;
        }

        return $theRoles;
    }

    /**
     * Affiche le formulaire de modification d'un group.
     *
     * Route("/admin/UserManagement/group/edit/{id}", name="admin_group_edit", methods={"GET", "POST"})
     * @Route("/admin/UserManagement/group/edit/{id}", name="admin_group_edit", methods={"GET", "POST"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function editAction(Request $request, $id)
    {
       // $group = $this->findGroupBy('name', $groupName);
       $em = $this->getDoctrine()->getManager();
       $group = $em->getRepository('App\Entity\UserManagement\Group')->findOneBy(['id' => $id]);

        // role_admin ne peut editer le group qui contient le ROLE_SUPER_ADMIN
        if (in_array('ROLE_SUPER_ADMIN', $group->getRoles())) {
            if (!in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
                $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN', null, 'Unable to access this page!');
            }
        }
        $old_group = clone $group;

        // $this->getUser()->getRoles(); //$group->getRoles()
        /** @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $dispatcher = $this->get('event_dispatcher');

        $event = new GetResponseGroupEvent($group, $request);
        $dispatcher->dispatch(FOSUserEvents::GROUP_EDIT_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }
        $form = $this->createForm("App\Form\UserManagement\GroupType", $group, ['roles' => $this->getRoles()]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {  // && $form->isValid() ) {
            /** @var $groupManager \FOS\UserBundle\Model\GroupManagerInterface */
            $groupManager = $this->container->get('fos_user.group_manager');

            $event = new FormEvent($form, $request);
            $dispatcher->dispatch(FOSUserEvents::GROUP_EDIT_SUCCESS, $event);

            // get form data
            $group_data = $request->get('user_group');

            // get group roles
            if(array_key_exists('roles',$group_data)){
                $group->setRoles($group_data['roles']);
            

                $groupManager->updateGroup($group);

                if (null === $response = $event->getResponse()) {
                    $url = $this->container->get('router')->generate('admin_group_view', ['groupName' => $group->getName()]);
                    $response = new RedirectResponse($url);
                }

                $dispatcher->dispatch(FOSUserEvents::GROUP_EDIT_COMPLETED, new FilterGroupResponseEvent($group, $request, $response));

                $event = new AuditTrailEvent($old_group, $group, 'edit', $this->getUser());
                $dispatcher->dispatch(AuditTrailEvent::NAME, $event);

                return $response;
            }else{
                $this->get('session')->getFlashBag()->add('error', 'Selectionner au moins un rôles');
            }
        }

        return $this->render('UserManagement/Group/edit.html.twig', [
            'form' => $form->createView(),
            'group' => $group,
            'roles' => $this->getRoles()
        ]);
    }

    /**
     * Affiche les détails d'un groupe d'utilisateurs.
     *
     * @Route("/admin/UserManagement/group/view/{groupName}", name="admin_group_view", methods={"GET", "POST"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function viewAction($groupName)
    {
        $group = $this->findGroupBy('name', $groupName);

        return $this->render('UserManagement/Group/view.html.twig', ['group' => $group]);
    }

    /**
     * desactive/active un groupe d'utilisateurs.
     *
     * @Route("/admin/UserManagement/group/active/{groupName}", name="admin_group_active", methods={"GET"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function active(Request $request, $groupName)
    {
        $group = $this->findGroupBy('name', $groupName);
        $manager = $this->getDoctrine()->getManager();
        // role_admin ne peut editer le group qui contient le ROLE_SUPER_ADMIN
        if (in_array('ROLE_SUPER_ADMIN', $group->getRoles())) {
            if (!in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
                $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN', null, 'Unable to access this page!');
            }
        }

        $old_group = clone $group;

        if (sizeof($group->getUsers()) == 0) {
            if ($group->getIsValid()) {
                $group->setIsValid(false);
                $manager->persist($group);
                $manager->flush();
                $action = 'disable';
            } else {
                $group->setIsValid(true);
                $manager->persist($group);
                $manager->flush();
                $action = 'enable';
            }
        }

        $event = new AuditTrailEvent($old_group, $group, $action, $this->getUser());
        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch(AuditTrailEvent::NAME, $event);

        $response = new RedirectResponse($this->generateUrl('admin_group_list'));

        return $response;
    }
}
