<?php

namespace App\EventListener;

use App\Entity\UserManagement\Tracking;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class TrailRequestListener
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * TrailRequestListener constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param FilterControllerEvent $event
     * @return bool|void
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $seesion = $request->getSession();
        $container = $this->container;
        //$route = $request->get('_route');
        //var_dump($seesion->get(SecurityContext::LAST_USERNAME));
        if (!is_array($controller = $event->getController())) {
            return;
        }

        $clientIp = $request->getClientIp();
        $method = $request->getMethod();
        $request_all = $request->request->all();
        $query_all = $request->query->all();
        $pathInfo = $request->getPathInfo();

        //if ($clientIp != "127.0.0.1" && ($method == "POST" && !empty($request_all) || $method == "GET" && !empty($query_all) || $method == "GET" && $pathInfo == "/") && strpos($pathInfo, "/search") === false) {
        $exculdes = ['toolbarAction'];
        if (in_array($controller[1], $exculdes)) {
            return true;
        }

        $entity = new Tracking();
        $entity->setController(get_class($controller[0])); // controller

        $entity->setAction($controller[1]);

        if (!empty($request_all)) {
            $entity->setQueryRequest(json_encode($request->request->all()));
        } else {
            $entity->setQueryRequest(json_encode($request->query->all()));
        }

        $entity->setPathInfo($request->getPathInfo());
        $entity->setHttpMethod($request->getMethod());
        $entity->setIpRequest($request->getClientIp());
        $entity->setLang($request->get('translator'));
        $entity->setUriRequest($request->getUri());

        $date = new \DateTime('now');
        $entity->setCreated($date);

        $em = $container->get('doctrine')->getManager();

        if ($container->get('security.token_storage')->getToken() && $container->get('security.token_storage')->getToken()->getUser()) {
            if (is_object($container->get('security.token_storage')->getToken()->getUser())) {
                $user_id = $container->get('security.token_storage')->getToken()->getUser()->getId();
                $user = $em->getRepository('App\Entity\UserManagement\User')->find($user_id);
                $entity->setUser($user);
                $last_acces = $em->getRepository('App\Entity\UserManagement\Tracking')->getLastAccess($user_id);
                // update last access duration
                if (is_object($last_acces)) {
                    //exit(\Doctrine\Common\Util\Debug::dump($last_acces->getCreated()));
                    $now = new \DateTime('now');
                    $diff = $now->getTimestamp() - $last_acces->getCreated()->getTimestamp();
                    $interval = new \DateTime();
                    $interval->setTimestamp($diff);
                    $tracking_id = $last_acces->getId();
                    $tracking = $em->getRepository('App\Entity\UserManagement\Tracking')->find($tracking_id);
                    $tracking->setDuration($interval);
                    $em->persist($tracking);
                }
            }
        }

        $em->persist($entity);
        $em->flush();
        //}
    }
}
