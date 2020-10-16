<?php

namespace App\Controller\FormationManagement;

use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\ModuleFile;
use App\Manager\AuditManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/admin/formationManagement/moduleFile")
 */
class ModuleFileController extends AbstractController
{
    /**
     * Show list of module file with module slug
     * @Route("/list/{module}", name="admin_module_file_list", methods={"GET","POST"})
     *
     * @Security("has_role('ROLE_GESTION_MODULES')")
     */
    public function list(Request $request, Module $module, TranslatorInterface $translator, SessionInterface $session)
    {
        $em = $this->getDoctrine()->getManager();

        $moduleFile = new ModuleFile();

        $form = $this->createForm('App\Form\FormationManagement\ModuleFileType', $moduleFile);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $moduleFile->setModule($module);
            $em->persist($moduleFile);
            $em->flush();

            $session->getFlashBag()->add('succès', $translator->trans('ajouté'));

            return $this->redirectToRoute('admin_module_file_list', [
                'module' => $module->getId(),
            ]);
        }

        return $this->render('FormationManagement/ModuleFile/list.html.twig', [
            'form' => $form->createView(),
            'module' => $module,
            'validationModes' => $em->getRepository('App\Entity\LovManagement\ValidationMode')->findBy(['isValid' => 1]),
            'typeTests' => $em->getRepository('App\Entity\LovManagement\TypeTest')->findAll(),
            'nbFormationLinked' => $module->getNbFormationLinked(),
        ]);
    }

    /**
     * @Route("/update/{moduleFile}/{action}", name="admin_module_file_update", methods={"POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PAGE')")
     */
    public function update(Request $request, ModuleFile $moduleFile, string $action, TranslatorInterface $translator, SessionInterface $session): JsonResponse
    {
        $entityManager = $this->getDoctrine()->getManager();

        try {
            if ($moduleFile->getIsValid()) {
                $moduleFile->setIsValid(false);
            } else {
                $moduleFile->setIsValid(true);
            }

            $entityManager->persist($moduleFile);
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
            'message' => 'admin_module_file_update',
            'success' => 1
        ]);
    }

    /**
     * @Route("/edit_flash/{moduleFile}", name="admin_module_file_edit_flash", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PAGE')")
     */
    public function editFlash(Request $request, ModuleFile $moduleFile, AuditManager $auditManager, SessionInterface $sfSession)
    {
        $em = $this->getDoctrine()->getManager();

        $output = [];

        $output['id'] = $request->get('pk');
        $output['name'] = $request->get('name');
        $output['value'] = $request->get('value');

        switch ($request->get('name')) {
            case 'name':
                $moduleFile->setName($request->get('value'));
                break;
            case 'isDownload':
                $moduleFile->setIsDownload($request->get('value'));
                break;
            default:
                break;
        }
        $em->persist($moduleFile);
        $em->flush($moduleFile);

        return new JsonResponse($output, 200);
    }
}
