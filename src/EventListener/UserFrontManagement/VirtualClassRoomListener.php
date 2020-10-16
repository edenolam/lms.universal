<?php

namespace App\EventListener\UserFrontManagement;

use App\Entity\PlanningManagement\VirtualClassRoom;
use Metaer\CurlWrapperBundle\CurlWrapper;
use Metaer\CurlWrapperBundle\CurlWrapperException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class VirtualClassRoomListener
{
    private $dispatcher;
    private $container;
    private $logger;

    private $url = null;

    public function __construct(EventDispatcherInterface $dispatcher, ContainerInterface $container, LoggerInterface $logger) // this is @service_container
    {
        $this->dispatcher = $dispatcher;
        $this->container = $container;
        $this->logger = $logger;
    }

    /**
     * Determines and sets the Request format.
     *
     * @param GetResponseEvent $event The event
     *
     * @throws NotAcceptableHttpException
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $container = $this->container;

        $em = $container->get('doctrine')->getManager();

        if ($container->get('security.token_storage')->getToken() && $container->get('security.token_storage')->getToken()->getUser()) {
            $user = $container->get('security.token_storage')->getToken()->getUser();

            if (is_object($user)) {
                if ($container->get('security.authorization_checker')->isGranted('ROLE_TUTEUR')) {
                    $currentVirtualClassRoom = $em->getRepository('App\Entity\PlanningManagement\VirtualClassRoom')->findTeacherVirtualClassRoom($user, VirtualClassRoom::NOW);
                    $isTeacher = 1;
                } else {
                    $currentVirtualClassRoom = $em->getRepository('App\Entity\PlanningManagement\VirtualClassRoom')->findStudentVirtualClassRoom($user, VirtualClassRoom::NOW);
                    $isTeacher = 0;
                }

                if ($currentVirtualClassRoom) {
                    if (is_array($currentVirtualClassRoom)) {
                        $vc = $currentVirtualClassRoom[0];
                    } else {
                        $vc = $currentVirtualClassRoom;
                    }

                    $data = [
                        'userId' => rand(),
                        'class_id' => $vc->getClassId(),
                        'task' => 'getclasslaunch',
                        'isTeacher' => $isTeacher,
                        'userName' => $user->getFirstname(),
                        'courseName' => 'one',
                        'lessonName' => 'one',
                    ];

                    $data_string = http_build_query($data);
                    $options = [
                        CURLOPT_URL => $container->getParameter('api_braincert'),
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => $data_string,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_SSL_VERIFYPEER => false
                    ];

                    try {
                        $curlWrapper = new CurlWrapper();

                        $api_result = $curlWrapper->getQueryResult($options);
                        $reponse = json_decode($api_result, true);

                        if ($reponse['status'] == 'error') {
                            $container->get('session')->getFlashBag()->add('error', $reponse['error']);
                        } else {
                            // launchurl = $reponse['launchurl']
                            $this->url = $reponse['launchurl'];
                            $this->dispatcher->addListener(KernelEvents::RESPONSE, [
                                    $this,
                                    'onKernelResponse'
                            ]);
                        }
                    } catch (CurlWrapperException $e) {
                        $container->get('session')->getFlashBag()->add('error', $e->getMessage());
                    }
                }
            }
        }

        return;
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = new RedirectResponse($this->url);
        $event->setResponse($response);
    }
}
