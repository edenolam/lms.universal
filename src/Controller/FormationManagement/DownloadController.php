<?php

namespace App\Controller\FormationManagement;

use App\Entity\FormationManagement\Course;
use App\Entity\FormationManagement\Download;
use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\Page;
use App\Form\FormationManagement\DownloadType;
use App\Manager\AuditManager;
use App\Repository\FormationManagement\DownloadRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/admin/formationManagement/download")
 */
class DownloadController extends AbstractController
{
    /**
     * @Route("/index/{module}/{course}/{page}", name="admin_page_download", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PAGE')")
     */
    public function pageDownload(Request $request, Module $module, Course $course, Page $page, DownloadRepository $downloadRepository, TranslatorInterface $translator, SessionInterface $session): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        $download = new Download();

        $form = $this->createForm(DownloadType::class, $download);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $download->addPage($page);
            $download->setModule($module);
            $entityManager->persist($download);
            $entityManager->flush();

            $session->getFlashBag()->add('succès', $translator->trans('ajouté'));

            return $this->redirectToRoute('admin_page_download', [
                'module' => $module->getId(),
                'page' => $page->getId(),
                'course' => $course->getId(),
            ]);
        }

        return $this->render('FormationManagement/Download/page_download.html.twig', [
            'downloads' => $downloadRepository->findBy(['module' => $module]),
            'form' => $form->createView(),
            'course' => $course,
            'module' => $module,
            'formationPath' => $module->getFormationPath(),
            'typeTests' => $entityManager->getRepository('App\Entity\LovManagement\TypeTest')->findAll(),
            'page' => $page,
        ]);
    }

    /**
     * @Route("/update/{page}/{download}/{action}", name="admin_page_download_update", methods={"POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PAGE')")
     */
    public function update(Request $request, Page $page, Download $download, string $action, DownloadRepository $downloadRepository, TranslatorInterface $translator, SessionInterface $session): JsonResponse
    {
        $entityManager = $this->getDoctrine()->getManager();

        try {
            if ($action == 'selected') {
                $download->addPage($page);
            } else {
                $download->removePage($page);
            }

            $entityManager->persist($download);
            $entityManager->flush();

            return new JsonResponse([
                'message' => 'Attachement associées sont mise à jour',
                'success' => 1
            ]);
        } catch (\Doctrine\DBAL\DBALException $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
                'success' => 0
            ]);
        } catch (\Exception $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
                'success' => 0
            ]);
        }

        return new JsonResponse([
            'message' => 'admin_page_download_update',
            'success' => 1
        ]);
    }

    /**
     * @Route("/edit_flash/{download}", name="admin_page_download_edit_flash", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PAGE')")
     */
    public function editFlash(Request $request, Download $download, AuditManager $auditManager, SessionInterface $sfSession)
    {
        $em = $this->getDoctrine()->getManager();

        $output = [];

        $output['id'] = $request->get('pk');
        $output['name'] = $request->get('name');
        $output['value'] = $request->get('value');

        switch ($request->get('name')) {
        case 'title':
            $download->setTitle($request->get('value'));
            break;
        case 'isDownload':
            $download->setIsDownload($request->get('value'));
            break;
        default:
            break;
      }
        $em->persist($download);
        $em->flush($download);

        return new JsonResponse($output, 200);
    }
}
