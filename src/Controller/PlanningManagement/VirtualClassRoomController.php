<?php

namespace App\Controller\PlanningManagement;

use App\Entity\PlanningManagement\VirtualClassRoom;
use App\Form\PlanningManagement\VirtualClassRoomType;
use App\Repository\PlanningManagement\VirtualClassRoomRepository;
use Metaer\CurlWrapperBundle\CurlWrapper;
use Metaer\CurlWrapperBundle\CurlWrapperException;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/planningManagement/virtualClassRoom")
 */
class VirtualClassRoomController extends AbstractController
{
    /**
     * @Route("/", name="admin_virtual_class_room_index", methods={"GET"})
     *
     * @Security("has_role('ROLE_GESTION_SESSION')")
     */
    public function index(VirtualClassRoomRepository $virtualClassRoomRepository): Response
    {
        return $this->render('PlanningManagement/VirtualClassRoom/index.html.twig', [
            'virtual_class_rooms' => $virtualClassRoomRepository->findBy([], ['closingDate' => 'DESC']),
        ]);
    }

    /**
     * @Route("/new", name="admin_virtual_class_room_new", methods={"GET","POST"})
     *
     * @Security("has_role('ROLE_GESTION_SESSION')")
     */
    public function new(Request $request, CurlWrapper $curlWrapper, SessionInterface $sfSession, ParameterBagInterface $params): Response
    {
        $virtualClassRoom = new VirtualClassRoom(Uuid::uuid5(Uuid::NAMESPACE_DNS, (new \DateTime())->format('Y-m-d H:i:s')));

        $form = $this->createForm(VirtualClassRoomType::class, $virtualClassRoom);
        $form->handleRequest($request);

        $api_result = null;
        $data = null;
        if ($form->isSubmitted() && $form->isValid()) {
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

                    return $this->redirectToRoute('admin_virtual_class_room_index', [
                            'id' => $virtualClassRoom->getId(),
                        ]);
                }
            } catch (CurlWrapperException $e) {
                $sfSession->getFlashBag()->add('error', $e->getMessage());
            }
        }

        return $this->render('PlanningManagement/VirtualClassRoom/new.html.twig', [
            'virtual_class_room' => $virtualClassRoom,
            'form' => $form->createView(),
            'result' => $api_result,
            'data' => $data
        ]);
    }

    /**
     * @Route("/show/{id}/{action}", name="admin_virtual_class_room_show", methods={"GET"})
     *
     * @Security("has_role('ROLE_GESTION_SESSION')")
     */
    public function show(Request $request, string $action, VirtualClassRoom $virtualClassRoom): Response
    {
        return $this->render('PlanningManagement/VirtualClassRoom/show.html.twig', [
            'virtual_class_room' => $virtualClassRoom,
            'action' => $action
        ]);
    }

    /**
     * @Route("/{id}/edit", name="admin_virtual_class_room_edit", methods={"GET","POST"})
     * @Security("has_role('ROLE_GESTION_SESSION')")
     */
    public function edit(Request $request, VirtualClassRoom $virtualClassRoom, CurlWrapper $curlWrapper, SessionInterface $sfSession, ParameterBagInterface $params): Response
    {
        $form = $this->createForm(VirtualClassRoomType::class, $virtualClassRoom);
        $form->handleRequest($request);

        $api_result = null;
        $data = null;

        if ($form->isSubmitted() && $form->isValid()) {
            // first remove class
            $data = [
                        'cid' => $virtualClassRoom->getClassId(),
                        'task' => 'removeclass',
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
                    goto isError;
                }
            } catch (CurlWrapperException $e) {
                $sfSession->getFlashBag()->add('error', $e->getMessage());
            }

            // 2 create a new class
            $data = [
                        'title' => $virtualClassRoom->getTitle(),
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

                    return $this->redirectToRoute('admin_virtual_class_room_index', [
                        'id' => $virtualClassRoom->getId(),
                    ]);
                }
            } catch (CurlWrapperException $e) {
                $sfSession->getFlashBag()->add('error', $e->getMessage());
            }
        }

        isError:
            return $this->render('PlanningManagement/VirtualClassRoom/edit.html.twig', [
                'virtual_class_room' => $virtualClassRoom,
                'form' => $form->createView(),
                'result' => $api_result,
                'data' => $data
            ]);
    }

    /**
     * @Route("/{id}", name="admin_virtual_class_room_delete", methods={"DELETE"})
     *
     * @Security("has_role('ROLE_GESTION_SESSION')")
     */
    public function delete(Request $request, VirtualClassRoom $virtualClassRoom, CurlWrapper $curlWrapper, SessionInterface $sfSession, ParameterBagInterface $params): Response
    {
        if ($this->isCsrfTokenValid('delete' . $virtualClassRoom->getId(), $request->request->get('_token'))) {
            $data = [
                        'cid' => $virtualClassRoom->getClassId(),
                        'task' => 'removeclass',
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
                    goto isError;
                }
            } catch (CurlWrapperException $e) {
                $sfSession->getFlashBag()->add('error', $e->getMessage());
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($virtualClassRoom);
            $entityManager->flush();
        }

        isError:
            return $this->redirectToRoute('admin_virtual_class_room_index');
    }
}
