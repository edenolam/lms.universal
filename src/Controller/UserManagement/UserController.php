<?php

namespace App\Controller\UserManagement;

use App\Entity\UserManagement\Role;
use App\Entity\UserManagement\User;
use App\Event\ServiceManagement\AuditTrailEvent;
use App\Event\UserManagement\RegistrationUserEvent;
use App\Repository\UserManagement\UserRepository;
use App\Utils\Utils;
use FOS\UserBundle\Model\UserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserController
 *
 * @Route("/admin/UserManagement/user")
 */
class UserController extends Controller
{
    /**
     * Utilisateur liste {key}/{order}/{line}/{page}
     *
     * @Route("/index", name="admin_user_index",  methods={"GET"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param int $page
     * @param UserRepository $userRepository
     *
     * @throws NotFoundHttpException
     *
     * @return Response
     */
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('UserManagement/User/list.html.twig', [
            'users' => $userRepository->findUserOrderByLastName(),
        ]);
    }

    /**
     * Affiche le formulaire d'ajout d'utilisateur ou enregistre l'utilisateur en Base de données.
     *
     * @Route("/create", name="admin_user_create", methods={"GET", "POST"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function create(Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');

        $user = $userManager->createUser();
        $user->setEnabled(true);

        $form = $this->createForm("App\Form\UserManagement\UserType", $user);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($user->getImage()) {
                    $extension = ($user->getImage()->guessExtension()) ? $user->getImage()->guessExtension() : 'png';

                    $nameFile = Utils::slug($user->getUsername(), '-') . '.' . $extension;

                    $user->getImage()->move('uploads/user/', $nameFile);
                    $user->setPhoto($nameFile);
                }
                

                $user->setCreateUser($this->getUser());
                $user->setUpdateUser($this->getUser());


                try {
                    $password = $user->getPlainPassword();
                    // update user
                    $userManager->updateUser($user);

                    // dispatcher RegistrationUserEvent
                    $event = new RegistrationUserEvent($user, $this->getUser(), $password);
                    $dispatcher = $this->get('event_dispatcher');
                    $dispatcher->dispatch(RegistrationUserEvent::NAME, $event);

                    $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('registration.flash.user_created', [], 'FOSUserBundle'));

                    $url = $this->generateUrl('admin_user_view', [
                            'username' => $user->getUsername()]);

                    return new RedirectResponse($url);
                } catch (\Doctrine\DBAL\DBALException $exception) {
                    // $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('user.flash.create_error'));
                    // $this->get('logger')->err($exception->getMessage());
                }
            } else {
                // $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('user.flash.create_error'));
            }
        }

        $manager = $this->getDoctrine()->getManager();
        $laboratories = $manager->getRepository('App\Entity\UserManagement\Laboratory')->findAll();
        $divisions = $manager->getRepository('App\Entity\UserManagement\Division')->findAll();
        $teams = $manager->getRepository('App\Entity\UserManagement\Team')->findAll();
        $groups = $manager->getRepository('App\Entity\UserManagement\Group')->findBy(['isValid' => 1]);

        return $this->render('UserManagement/User/create.html.twig', [
                'user' => $user,
                'groups' => $groups,
                'laboratories' => $laboratories,
                'divisions' => $divisions,
                'teams' => $teams,
                'form' => $form->createView(),
            ]);
    }

    /**
     * Affiche un utilisateur .
     *
     * @Route("/view/{username}", name="admin_user_view", methods={"GET", "POST"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function view($username)
    {
        $em = $this->getDoctrine()->getManager();
        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserByUsernameOrEmail($username);
        $userRole = $user->getRolesFromGroups();
        if (in_array('ROLE_RESPONSABLE_FORMATION',$userRole) or in_array('ROLE_SUPER_ADMIN',$userRole)) { 
            $roles='respoFormation';
        }elseif (in_array('ROLE_TUTEUR',$userRole)) { 
            $roles='tuteur';
        }else{
            $roles = 'autre';
        }

        $tutorFollow = $em->getRepository('App\Entity\UserManagement\User')->findApprenantFollowBy($user, $roles);

        if (!$user instanceof UserInterface) {
            throw new BadRequestHttpException($this->get('translator')->trans('global.badRequestHttpException'));
        }

        return $this->render('UserManagement/User/view.html.twig', [
            'user' => $user,
            'tutorFollow' => $tutorFollow
        ]);
    }

    /**
     * Affiche le formulaire de modification d'utilisateur.
     *
     * @Route("/edit/{username}", name="admin_user_edit", methods={"GET", "POST"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function edit(Request $request, $username)
    {
        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserByUsernameOrEmail($username);

        if (!$user instanceof \FOS\UserBundle\Model\UserInterface) {
            throw new BadRequestHttpException($this->get('translator')->trans('global.badRequestHttpException'));
        }

        // role_admin ne peut editer le user qui a le ROLE_SUPER_ADMIN
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            if (!in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
                $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN', null, 'Unable to access this page!');
            }
        }

        $old_logo = $user->getPhoto();
        $old_user = clone $user;

        $form = $this->createForm("App\Form\UserManagement\UserType", $user);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // user photo
                if ($user->getImage()) {
                    $extension = ($user->getImage()->guessExtension()) ? $user->getImage()->guessExtension() : 'png';

                    $nameFile = Utils::slug($user->getUsername(), '-') . '_' . $user->getRevision() . '.' . $extension;

                    $user->getImage()->move('uploads/user/', $nameFile);
                    $user->setPhoto($nameFile);

                    $fileSystem = new Filesystem();

                    if (!empty($old_logo) && $fileSystem->exists($this->get('kernel')->getProjectDir() . '/public/uploads/user/' . $old_logo)) {
                        try {
                            $fileSystem->remove([$this->get('kernel')->getProjectDir() . '/public/uploads/user/' . $old_logo]);
                        } catch (IOExceptionInterface $exception) {
                            $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('user.flash.delete_image_error'));
                        }
                    }
                }
                $user->setUpdateUser($this->getUser());
                $user->setRevision($user->getRevision() + 1);

                try {
                    $userManager->updateUser($user);

                    $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('profile.flash.updated', [], 'FOSUserBundle'));

                    $url = $this->generateUrl('admin_user_view', [
                                    'username' => $user->getUsername()]);

                    // dispatcher audit trail event
                    $dispatcher = $this->get('event_dispatcher');
                    $event = new AuditTrailEvent($old_user, $user, 'edit', $this->getUser());
                    $dispatcher->dispatch(AuditTrailEvent::NAME, $event);

                    return new RedirectResponse($url);
                } catch (\Doctrine\DBAL\DBALException $exception) {
                    $this->get('session')->getFlashBag()->add('error', $exception->getMessage());
                }
            } else {
                $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('user.flash.updated_error'));
            }
        }

        $groupManager = $this->get('fos_user.group_manager');

        $manager = $this->getDoctrine()->getManager();
        $laboratories = $manager->getRepository('App\Entity\UserManagement\Laboratory')->findAll();
        $divisions = $manager->getRepository('App\Entity\UserManagement\Division')->findAll();
        $teams = $manager->getRepository('App\Entity\UserManagement\Team')->findAll();
        $groups = $manager->getRepository('App\Entity\UserManagement\Group')->findBy(['isValid' => 1]);

        return $this->render('UserManagement/User/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'laboratories' => $laboratories,
            'divisions' => $divisions,
            'teams' => $teams,
            'groups' => $groups,
        ]);
    }

    /**
     * activer ou desactiver le user.
     *
     * @Route("/active/{username}", name="admin_user_active", methods={"GET", "POST"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function active($username)
    {
        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserByUsernameOrEmail($username);

        if (!$user instanceof \FOS\UserBundle\Model\UserInterface) {
            throw new BadRequestHttpException($this->get('translator')->trans('global.badRequestHttpException'));
        }

        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            if (!in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
                $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN', null, 'Unable to access this page!');
            }
        }

        $old_user = clone $user;

        if (isset($user) && $user->isEnabled()) {
            $action = 'disable';
            $user->setEnabled(false);

            $userManager->updateUser($user);
        } elseif (isset($user) && !$user->isEnabled()) {
            $action = 'enable';
            $user->setEnabled(true);

            $userManager->updateUser($user);
        }

        $event = new AuditTrailEvent($old_user, $user, $action, $this->getUser());
        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch(AuditTrailEvent::NAME, $event);

        $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('profile.flash.updated', [], 'FOSUserBundle'));

        return $this->redirect($this->generateUrl('admin_user_index', []));
    }
}
