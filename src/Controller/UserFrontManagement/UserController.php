<?php

namespace App\Controller\UserFrontManagement;

use App\Utils\Utils;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /**
     * user profile edit
     *
     * @Route("/profile", name="user_profile",methods={"GET", "POST"}))
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function profile(Request $request, LoggerInterface $logger)
    {
        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserByUsernameOrEmail($this->getUser()->getUsername());

        $_oldLogo = $this->getUser()->getPhoto();

        $form = $this->createForm("App\Form\UserManagement\UserProfileType", $user);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($user->getImage()) {
                    $extension = ($user->getImage()->guessExtension()) ? $user->getImage()->guessExtension() : 'png';

                    $nameFile = Utils::slug($user->getFirstname() . $user->getLastname(), '-') . '_' . $user->getRevision() . '.' . $extension;

                    $user->getImage()->move('uploads/user/', $nameFile);
                    $user->setPhoto($nameFile);

                    $fileSystem = new Filesystem();

                    if (!empty($_oldLogo) && $fileSystem->exists($this->get('kernel')->getProjectDir() . '/public/uploads/user/' . $_oldLogo)) {
                        try {
                            $fileSystem->remove([$this->get('kernel')->getProjectDir() . '/public/uploads/user/' . $_oldLogo]);
                        } catch (IOExceptionInterface $exception) {
                            $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('user.flash.delete_image_error'));
                        }
                    }
                }

                $user->setUpdateUser($this->getUser());
                $user->setRevision($user->getRevision() + 1);

                try {
                    // update user
                    $userManager->updateUser($user);

                    $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('registration.flash.user_updated', [], 'FOSUserBundle'));

                    $url = $this->generateUrl('fos_user_profile_show');

                    return new RedirectResponse($url);
                } catch (\Doctrine\DBAL\DBALException $exception) {
                    $this->get('session')->getFlashBag()->add('error', $exception->getMessage());
                    $logger->err($exception->getMessage());
                }
            } else {
                $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('user.flash.create_error'));
            }
        }

        return $this->render('UserFrontManagement/profile.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
