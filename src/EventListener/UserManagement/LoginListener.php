<?php

namespace App\EventListener\UserManagement;

use App\Entity\UserManagement\Tracking;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * Listener responsible to change the redirection when a form is successfully filled
 */
class LoginListener implements EventSubscriberInterface
{
    private $router;
    private $dispatcher;
    private $container;

    public function __construct(Router $router, EventDispatcherInterface $dispatcher, ContainerInterface $container)
    {
        //$this->security = $security;
        $this->router = $router;
        $this->dispatcher = $dispatcher;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FOSUserEvents::SECURITY_IMPLICIT_LOGIN => 'onLogin',
            SecurityEvents::INTERACTIVE_LOGIN => 'onLogin', // this work
        ];
    }

    public function onLogin($event)
    {
        $request = $event->getRequest();
        $session = $request->getSession();
        $route = $request->get('_route');

        if ($event instanceof UserEvent) {
            $user = $event->getUser();
        }
        if ($event instanceof InteractiveLoginEvent) {
            $user = $event->getAuthenticationToken()->getUser();
        }

        $container = $this->container;

        // login stats
        $entity = new Tracking();
        $clientIp = $request->getClientIp();
        $method = $request->getMethod();
        $request_all = $request->request->all();
        $query_all = $request->query->all();
        $pathInfo = $request->getPathInfo();
        $entity->setAction('loginAction');
        $lastUsername = $container->get('security.token_storage')->getToken()->getUser()->getUsername();
        $tracking = ['lastUsername' => $lastUsername, 'error' => ''];
        $entity->setQueryRequest(json_encode($tracking));
        $entity->setPathInfo('/fr/login');
        $entity->setHttpMethod($request->getMethod());
        $entity->setIpRequest($request->getClientIp());
        $entity->setLang($request->get('translator'));
        $entity->setUriRequest($request->getUri());
        $date = new \DateTime('now');
        $entity->setCreated($date);

        $em = $container->get('doctrine')->getManager();
        $em->persist($entity);
        $em->flush();

        // check password expiration
        if ($route != 'fos_user_change_password' && $route != null && $container->get('security.token_storage')->getToken() && $container->get('security.token_storage')->getToken()->getUser()) {
            if ($container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
                $user = $container->get('security.token_storage')->getToken()->getUser();
                $duration = 0;
                // if ($user->getLastChangePassword()) {
                //     $now = new \DateTime();
                //     $interval = $user->getLastChangePassword()->diff($now);
                //     $duration = $interval->format('%a');
                // } 
                //echo $duration; exit;
                if (($user->getLastChangePassword() == null || $duration >= 90) && $user->getLdapUser() == false ) {
                    $this->dispatcher->addListener(KernelEvents::RESPONSE, array(
                            $this,
                            'onKernelResponse'
                    ) );
					
                    $user->setLastChangePassword(new \DateTime());
                    $em->persist($user);
                    $em->flush();
					
                }
            }
        }
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = new RedirectResponse($this->router->generate('fos_user_change_password'));
        $event->setResponse($response);
    }
}
