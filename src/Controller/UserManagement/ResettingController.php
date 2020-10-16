<?php

namespace App\Controller\UserManagement;

use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseNullableUserEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Form\Factory\FactoryInterface;
use FOS\UserBundle\FOSUserEvents;
// use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\UserBundle\Controller\ResettingController as BaseController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use App\Service\MailService;
use Symfony\Component\Translation\TranslatorInterface;


class ResettingController extends BaseController
{

    /**
     * @var MailService
     */
    private $mailService;
    private $translator;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param FactoryInterface         $formFactory
     * @param UserManagerInterface     $userManager
     * @param TokenGeneratorInterface  $tokenGenerator
     * @param MailerInterface          $mailer
     * @param int                      $retryTtl
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, FactoryInterface $formFactory, UserManagerInterface $userManager, TokenGeneratorInterface $tokenGenerator, $retryTtl, MailService $mailService, TranslatorInterface $translator)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->formFactory = $formFactory;
        $this->userManager = $userManager;
        $this->tokenGenerator = $tokenGenerator;
        $this->mailService = $mailService;
        $this->retryTtl = $retryTtl;
        $this->translator = $translator;
    }

    // /**
    //  * Envoyer le mail
    //  *
    //  * @Route("/user/password/resetaction", name="user_reset_pasword", methods={"GET", "POST"})
    //  *
    //  * @Security("has_role('ROLE_USER')")
    //  */
    // public function requestAction()
    // {
        
    //     return $this->render('@FOSUser/Resetting/request.html.twig');
    // }

    /**
     * Envoyer le mail
     *
     * @Route("/resetting/password/resetmail", name="user_reset_pasword_mail", methods={"GET", "POST"})
     *
     */
    public function sendEmailAction(Request $request)
    {
        $username = $request->request->get('username');

        $user = $this->userManager->findUserByUsernameOrEmail($username);

        $event = new GetResponseNullableUserEvent($user, $request);
        $this->eventDispatcher->dispatch(FOSUserEvents::RESETTING_SEND_EMAIL_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }
        // var_dump(null !== $user);
        // var_dump(!$user->isPasswordRequestNonExpired($this->retryTtl));
        // exit();
        if (null !== $user && !$user->isPasswordRequestNonExpired($this->retryTtl)) {
            $event = new GetResponseUserEvent($user, $request);
            $this->eventDispatcher->dispatch(FOSUserEvents::RESETTING_RESET_REQUEST, $event);

            if (null !== $event->getResponse()) {
                return $event->getResponse();
            }

            if (null === $user->getConfirmationToken()) {
                $user->setConfirmationToken($this->tokenGenerator->generateToken());
            }

            $event = new GetResponseUserEvent($user, $request);
            $this->eventDispatcher->dispatch(FOSUserEvents::RESETTING_SEND_EMAIL_CONFIRM, $event);

            if (null !== $event->getResponse()) {
                return $event->getResponse();
            }

            $confirmationUrl = $this->generateUrl('fos_user_resetting_reset', array('token' => $user->getConfirmationToken()), UrlGeneratorInterface::ABSOLUTE_URL);
            
            $subject = $this->translator->trans('global.welcom.rest_psw_mail');
            $from = ['info@universalmedica.com' => 'universalmedica'];
            $body = $this->renderView('bundles/FOSUserBundle/Resetting/email.html.twig', [
                    'user' => $user,
                    'confirmationUrl' => $confirmationUrl,
                ]);
                
            $this->mailService->sendAMail($from, $user->getEmail(), $subject, $body);

            //$this->mailer->sendResettingEmailMessage($user);
            $user->setPasswordRequestedAt(new \DateTime());
            $this->userManager->updateUser($user);

            $event = new GetResponseUserEvent($user, $request);
            $this->eventDispatcher->dispatch(FOSUserEvents::RESETTING_SEND_EMAIL_COMPLETED, $event);

            if (null !== $event->getResponse()) {
                return $event->getResponse();
            }
        }
        

        return new RedirectResponse($this->generateUrl('fos_user_resetting_check_email', array('username' => $username)));
    }

//   /**
//      * Envoyer le mail
//      *
//      * @Route("/user/password/resetcheckmail", name="user_reset_pasword_checkmail", methods={"GET", "POST"})
//      *
//      * @Security("has_role('ROLE_USER')")
//      */
//     public function checkEmailAction(Request $request)
//     {
//         $username = $request->query->get('username');

//         if (empty($username)) {
//             // the user does not come from the sendEmail action
//             return new RedirectResponse($this->generateUrl('user_reset_pasword'));
//         }

//         return $this->render('bundles/FOSUserBundleResetting/Resetting/check_email.html.twig', array(
//             'tokenLifetime' => ceil($this->retryTtl / 3600),
//         ));
//     }

    // /**
    //  * Reset user password.
    //  *
    //  * @param Request $request
    //  * @param string  $token
    //  *
    //  * @return Response
    //  */
    // public function resetAction(Request $request, $token)
    // {
    //     $user = $this->userManager->findUserByConfirmationToken($token);

    //     if (null === $user) {
    //         return new RedirectResponse($this->container->get('router')->generate('fos_user_security_login'));
    //     }

    //     $event = new GetResponseUserEvent($user, $request);
    //     $this->eventDispatcher->dispatch(FOSUserEvents::RESETTING_RESET_INITIALIZE, $event);

    //     if (null !== $event->getResponse()) {
    //         return $event->getResponse();
    //     }

    //     $form = $this->formFactory->createForm();
    //     $form->setData($user);

    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $event = new FormEvent($form, $request);
    //         $this->eventDispatcher->dispatch(FOSUserEvents::RESETTING_RESET_SUCCESS, $event);

    //         $this->userManager->updateUser($user);

    //         if (null === $response = $event->getResponse()) {
    //             $url = $this->generateUrl('fos_user_profile_show');
    //             $response = new RedirectResponse($url);
    //         }

    //         $this->eventDispatcher->dispatch(
    //             FOSUserEvents::RESETTING_RESET_COMPLETED,
    //             new FilterUserResponseEvent($user, $request, $response)
    //         );

    //         return $response;
    //     }

    //     return $this->render('UserManagement/Resetting/reset.html.twig', array(
    //         'token' => $token,
    //         'form' => $form->createView(),
    //     ));
    // }
}
