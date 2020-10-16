<?php

namespace App\Controller\UserFrontManagement;

use App\Entity\PlanningManagement\VirtualClassRoom;
use App\Repository\PlanningManagement\VirtualClassRoomRepository;
use App\Repository\UserManagement\UserRepository;
use FOS\UserBundle\Model\GroupManagerInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Metaer\CurlWrapperBundle\CurlWrapper;
use Metaer\CurlWrapperBundle\CurlWrapperException;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user")
 *
 * @author free
 */
class UserVirtualClassRoomController extends AbstractController
{
    /**
     * VirtualClassRoom
     *
     * @Route("/virtual_class_room", name="user_virtual_class_room", methods="GET")
     *
     * @Security("has_role('ROLE_USER')")
     */
    public function virtualClassRoom(Request $request, VirtualClassRoomRepository $virtualClassRoomRepository, GroupManagerInterface $groupManager)
    {
        if ($this->get('security.authorization_checker')->isGranted('ROLE_TUTEUR')) {
            $isTeacher = 1;
        } else {
            $isTeacher = 0;
        }

        return $this->render('UserFrontManagement/virtual_class_room.html.twig', [
                    'currentVirtualClassRoom' => $virtualClassRoomRepository->findVirtualClassRoom(VirtualClassRoom::NOW),
                    'futureVirtualClassRooms' => $virtualClassRoomRepository->findVirtualClassRoom(VirtualClassRoom::FUTURE),
                    'passedVirtualClassRooms' => $virtualClassRoomRepository->findVirtualClassRoom(VirtualClassRoom::PASSED),
                    'isTeacher' => $isTeacher
                ]);
    }

    /**
     * VirtualClassRoom demo only
     *
     * @Route("/virtual_class_room_demo", name="user_virtual_class_room_demo", methods="GET")
     *
     * @Security("has_role('ROLE_USER')")
     */
    public function virtualClassRoomDemo(Request $request, VirtualClassRoomRepository $virtualClassRoomRepository, UserManagerInterface $userManager, UserRepository $userRepository, CurlWrapper $curlWrapper, SessionInterface $sfSession, ParameterBagInterface $params)
    {
        // créer vc
        $virtualClassRoom = new VirtualClassRoom(Uuid::uuid5(Uuid::NAMESPACE_DNS, (new \DateTime())->format('Y-m-d H:i:s')));
        $now = new \DateTime('now');
        $end = new \DateTime('now');
        $end->add(new \DateInterval('PT5M'));

        $admin = $userManager->findUserBy(['id' => 1]);

        $virtualClassRoom->setTitle('demo_' . $this->getUser()->getUsername());
        $virtualClassRoom->setOpeningDate($now);
        $virtualClassRoom->setClosingDate($end);
        $virtualClassRoom->setTeacher($admin);

        $users = $userRepository->findAll();
        foreach ($users as $u) {
            if ($u != $admin) {
                $virtualClassRoom->addStudent($u);
            }
        }

        $data = [
                'title' => $virtualClassRoom->getTitle(), //demo
                'timezone' => '53',
                'date' => $virtualClassRoom->getOpeningDate()->format('Y-m-d'),
                'start_time' => $virtualClassRoom->getOpeningDate()->format('h:iA'),
                'end_time' => $virtualClassRoom->getClosingDate()->format('h:iA'),
                'currency' => 'usd',
                'ispaid' => '0',
                'is_recurring' => '0',
                'repeat' => '6',
                'weekdays' => '1,2,3',
                'end_classes_count' => '1',
                'seat_attendees' => '1',
                'record' => '0'
        ];

        $data_string = http_build_query($data);
        $options = [
            CURLOPT_URL => $params->get('api_braincert'),
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data_string,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false
        ];

        try {
            $api_result = $curlWrapper->getQueryResult($options);
            $reponse = json_decode($api_result, true);
            if ($reponse['status'] == 'error') {
                $sfSession->getFlashBag()->add('error', $reponse['error']);
            } else {
                $entityManager = $this->getDoctrine()->getManager();
                $virtualClassRoom->setClassId($reponse['class_id']);
                $entityManager->persist($virtualClassRoom);
                $entityManager->flush();

                // redirection
                $data_vc = [
                    'userId' => rand(),
                    'class_id' => $reponse['class_id'],
                    'task' => 'getclasslaunch',
                    'isTeacher' => 0,
                    'userName' => $this->getUser()->getFirstname(),
                    'courseName' => 'one',
                    'lessonName' => 'one',
                ];

                $data_string_vc = http_build_query($data_vc);
                $options_vc = [
                    CURLOPT_URL => $params->get('api_braincert'),
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $data_string_vc,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_SSL_VERIFYPEER => false
                ];

                try {
                    $api_result_vc = $curlWrapper->getQueryResult($options_vc);
                    $reponse_vc = json_decode($api_result_vc, true);

                    if ($reponse_vc['status'] == 'error') {
                        $sfSession->getFlashBag()->add('error', $reponse_vc['error']);
                    } else {
                        $this->redirect($reponse_vc['launchurl']);
                    }
                } catch (CurlWrapperException $e) {
                    $sfSession->getFlashBag()->add('error', $e->getMessage());
                }
            }
        } catch (CurlWrapperException $e) {
            $sfSession->getFlashBag()->add('error', $e->getMessage());
        }

        return $this->render('UserFrontManagement/virtual_class_room.html.twig', [
                    'currentVirtualClassRoom' => $virtualClassRoomRepository->findVirtualClassRoom(VirtualClassRoom::NOW),
                    'futureVirtualClassRooms' => $virtualClassRoomRepository->findVirtualClassRoom(VirtualClassRoom::FUTURE),
                    'passedVirtualClassRooms' => $virtualClassRoomRepository->findVirtualClassRoom(VirtualClassRoom::PASSED),
                ]);
    }
}
