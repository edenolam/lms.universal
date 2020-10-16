<?php

namespace App\Controller\UserManagement;

use App\Entity\UserManagement\Tracking;
use App\Repository\UserManagement\TrackingRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class TrackingController
 */
class TrackingController extends Controller
{
    public const max_tracking_per_page = 15;

    /**
     * Tracking liste
     *
     * @Route("/admin/UserManagement/tracking/list", name="admin_tracking_list", defaults={"page": "1"},  methods={"GET"})
     * @Route("/admin/UserManagement/tracking/list/{page}", requirements={"page"="\d+"}, name="admin_tracking_list_paginated")
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param int $page
     * @param TrackingRepository $trackingRepository
     *
     * @throws NotFoundHttpException
     *
     * @return Response
     */
    public function list(int $page, TrackingRepository $trackingRepository): Response
    {
        $total = $trackingRepository->getTotal();

        $pagination = [
            'page' => $page,
            'route' => 'admin_tracking_list_paginated',
            'pages_count' => ceil($total / self::max_tracking_per_page),
            'route_params' => []
        ];

        $trackings = $trackingRepository->findAllValid($page, self::max_tracking_per_page);

        return $this->render('UserManagement/Tracking/list.html.twig', [
                'trackings' => $trackings,
                'pagination' => $pagination
        ]);
    }

    /**
     * Tracking download
     *
     * @Route("/admin/UserManagement/tracking/download", name="admin_tracking_download",  methods={"GET"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param TrackingRepository $trackingRepository
     *
     * @throws NotFoundHttpException
     *
     * @return Response
     */
    public function trackingDownload(TrackingRepository $trackingRepository)
    {
        $trackings = $trackingRepository->findAll();

        $spreadsheet = new Spreadsheet();

        $spreadsheet->getProperties()
            ->setCreator('Universal Medica')
            ->setLastModifiedBy('Universal Medica')
            ->setTitle('Export tracking - ' . date('Y-m-d_H'))
            ->setSubject('Export tracking')
            ->setDescription('Export tracking')
            ->setKeywords('tracking')
            ->setCategory('tracking');

        $translator = $this->get('translator');

        // Get the active sheet.
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', $translator->trans('tracking.created'))
                ->setCellValue('B1', $translator->trans('tracking.path_info'))
                ->setCellValue('C1', $translator->trans('tracking.lang'))
                ->setCellValue('D1', $translator->trans('tracking.ip_request'))
                ->setCellValue('E1', $translator->trans('tracking.user'))
                ;

        $line = 2;
        foreach ($trackings as $key => $t) {
            if (is_object($t->getUser())) {
                $sheet->setCellValue('A' . $line, $t->getCreated()->format('Y-m-d H:i:s'))
                        ->setCellValue('B' . $line, $t->getPathInfo())
                        ->setCellValue('C' . $line, $t->getLang())
                        ->setCellValue('D' . $line, $t->getIpRequest())
                        ->setCellValue('E' . $line, $t->getUser()->getUsername())
                    ;
                $line++;
            }
        }

        $response = new Response();
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', 'attachment; filename=export_tracking_' . date('Y-m-d_H') . '.xlsx');

        // Get the writer and export in memory.
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        // Memory cleanup.
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        $response->setContent($content);

        return $response;
    }

    /**
     * Tracking liste
     *
     * @Route("/admin/UserManagement/tracking/user/{username}", name="admin_tracking_user",  defaults={"page": "1"}, methods={"GET"})
     * @Route("/admin/UserManagement/tracking/user/{username}/page/{page}", requirements={"page"="\d+"}, name="admin_tracking_user_paginated")
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param string $string username
     * @param int $page page number
     *
     * @return Response
     */
    public function userTracking(string $username, int $page, TrackingRepository $trackingRepository): Response
    {
        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserByUsernameOrEmail($username);

        if (!$user instanceof \FOS\UserBundle\Model\UserInterface) {
            throw new BadRequestHttpException($this->get('translator')->trans('global.badRequestHttpException'));
        }

        $trackings = $trackingRepository->getByUser($user->getId());

        $total = $trackingRepository->getTotalByUser($user->getId());

        $pagination = [
            'page' => $page,
            'route' => 'admin_tracking_user_paginated',
            'pages_count' => ceil($total / self::max_tracking_per_page),
            'route_params' => []
        ];

        return $this->render('UserManagement/Tracking/user.html.twig', [
                'user' => $user,
                'total' => $total,
                'trackings' => $trackings,
                'pagination' => $pagination
        ]);
    }

     /**
     * message log liste 
     *
     * @Route("/admin/UserManagement/mailLog/list", name="admin_mail_log_list", defaults={"page": "1"},  methods={"GET"})
     * @Route("/admin/UserManagement/mailLog/list/{page}", requirements={"page"="\d+"}, name="admin_mail_log_list_paginated")
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @throws NotFoundHttpException
     *
     * @return Response
     */
    public function listMailLog(int $page): Response
    {
        $manager = $this->getDoctrine()->getManager();
        $total = $manager->getRepository('App\Entity\UserManagement\LoggedMessage')->getMailHistoryTotal();

        $pagination = [
            'page' => $page,
            'route' => 'admin_mail_log_list_paginated',
            'pages_count' => ceil($total / self::max_tracking_per_page),
            'route_params' => []
        ];

        $loggedMessages = $manager->getRepository('App\Entity\UserManagement\LoggedMessage')->getMailHistoryByPage($page, self::max_tracking_per_page);

        return $this->render('UserManagement/Tracking/listMailLog.html.twig', [
                'loggedMessages' => $loggedMessages,
                'pagination' => $pagination
        ]);
    }

}
