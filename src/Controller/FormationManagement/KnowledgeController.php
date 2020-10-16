<?php

namespace App\Controller\FormationManagement;

use App\Entity\FormationManagement\Course;
use App\Entity\FormationManagement\Knowledge;
use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\Page;
use App\Form\FormationManagement\KnowledgeType;
use App\Manager\AuditManager;
use App\Repository\FormationManagement\KnowledgeRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/admin/formationManagement/knowledge")
 */
class KnowledgeController extends AbstractController
{
    /**
     * @Route("/index/{module}/{course}/{page}", name="admin_page_knowledge", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PAGE')")
     */
    public function pageKnowledge(Request $request, Module $module, Course $course, Page $page, KnowledgeRepository $knowledgeRepository, TranslatorInterface $translator, SessionInterface $session): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        $knowledge = new Knowledge();

        $form = $this->createForm(KnowledgeType::class, $knowledge);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $knowledge->addPage($page);
            $knowledge->setModule($module);
            $entityManager->persist($knowledge);
            $entityManager->flush();

            $session->getFlashBag()->add('succès', $translator->trans('ajouté'));

            return $this->redirectToRoute('admin_page_knowledge', [
                'module' => $module->getId(),
                'page' => $page->getId(),
                'course' => $course->getId(),
            ]);
        }

        return $this->render('FormationManagement/Knowledge/page_knowledge.html.twig', [
            'knowledges' => $knowledgeRepository->findBy(['module' => $module]),
            'form' => $form->createView(),
            'course' => $course,
            'module' => $module,
            'formationPath' => $module->getFormationPath(),
            'typeTests' => $entityManager->getRepository('App\Entity\LovManagement\TypeTest')->findAll(),
            'page' => $page,
        ]);
    }

    /**
     * @Route("/update/{page}/{knowledge}/{action}", name="admin_page_knowledge_update", methods={"POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PAGE')")
     */
    public function update(Request $request, Page $page, Knowledge $knowledge, string $action, KnowledgeRepository $knowledgeRepository, TranslatorInterface $translator, SessionInterface $session): JsonResponse
    {
        $entityManager = $this->getDoctrine()->getManager();

        try {
            if ($action == 'selected') {
                $knowledge->addPage($page);
            } else {
                $knowledge->removePage($page);
            }

            $entityManager->persist($knowledge);
            $entityManager->flush();

            return new JsonResponse([
                'message' => 'Connaissances associées sont mise à jour',
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
            'message' => 'admin_page_knowledge_update',
            'success' => 1
        ]);
    }

    /**
     * @Route("/edit_flash/{knowledge}", name="admin_page_knowledge_edit_flash", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PAGE')")
     */
    public function editFlash(Request $request, Knowledge $knowledge, AuditManager $auditManager, SessionInterface $sfSession)
    {
        $em = $this->getDoctrine()->getManager();

        $output = [];

        $output['id'] = $request->get('pk');
        $output['name'] = $request->get('name');
        $output['value'] = $request->get('value');

        switch ($request->get('name')) {
        case 'title':
            $knowledge->setTitle($request->get('value'));
            break;
        default:
            break;
      }
        $em->persist($knowledge);
        $em->flush($knowledge);

        return new JsonResponse($output, 200);
    }
}
