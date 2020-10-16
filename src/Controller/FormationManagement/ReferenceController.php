<?php

namespace App\Controller\FormationManagement;

use App\Entity\FormationManagement\Course;
use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\Page;
use App\Entity\FormationManagement\PageReference;
use App\Entity\FormationManagement\Reference;
use App\Form\FormationManagement\ReferenceLiteType;
use App\Manager\AuditManager;
use App\Repository\FormationManagement\ReferenceRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

/**
 * @Route("/admin/formationManagement/reference")
 */
class ReferenceController extends AbstractController
{
    /**
     * @Route("/ref/{module}/{course}/{page}", name="admin_page_reference", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PAGE')")
     */
    public function pageReference(Request $request, Module $module, Course $course, Page $page, ReferenceRepository $referenceRepository, TranslatorInterface $translator, SessionInterface $session): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        $reference = new Reference();

        $form = $this->createForm(ReferenceLiteType::class, $reference);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //$knowledge->addPage($page);
            $pageReference = new PageReference();
            $pageReference->setPage($page);
            $pageReference->setSort($page->getMaxReferenceSort());
            $pageReference->setReference($reference);

            $reference->setModule($module);
            $entityManager->persist($reference);
            $entityManager->flush();

            $session->getFlashBag()->add('succès', $translator->trans('ajouté'));

            return $this->redirectToRoute('admin_page_reference', [
                'module' => $module->getId(),
                'page' => $page->getId(),
                'course' => $course->getId(),
            ]);
        }

        return $this->render('FormationManagement/Reference/page_reference.html.twig', [
            'references' => $referenceRepository->findBy(['module' => $module]),
            'form' => $form->createView(),
            'course' => $course,
            'module' => $module,
            'formationPath' => $module->getFormationPath(),
            'typeTests' => $entityManager->getRepository('App\Entity\LovManagement\TypeTest')->findAll(),
            'page' => $page,
        ]);
    }

    /**
     * @Route("/selected/{page}/{reference}", name="admin_page_reference_selected", methods={"POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PAGE')")
     */
    public function selected(Request $request, Page $page, Reference $reference, TranslatorInterface $translator, SessionInterface $session, LoggerInterface $logger): JsonResponse
    {
        $entityManager = $this->getDoctrine()->getManager();
        // $pageToLin = $entityManager->getRepository('App\Entity\FormationManagement\Page')->findOneById($page);
        // $refToLin = $entityManager->getRepository('App\Entity\FormationManagement\Reference')->findOneById($reference);
         try {
            $pageReference = new PageReference();
            $pageReference->setPage($page);
            $pageReference->setSort($page->getMaxReferenceSort());
            $pageReference->setReference($reference);
            $entityManager->persist($pageReference);
            // $page->addPageReference($pageReference);
            // $entityManager->persist($page);

            $entityManager->flush();

            return new JsonResponse([
                'message' => 'Référence bibliographiques sont mise à jour',
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
     * @Route("/unselected/{page}/{pageReference}/{reference}", name="admin_page_reference_unselected", methods={"POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PAGE')")
     */
    public function unselected(Request $request, Page $page, PageReference $pageReference, Reference $reference, TranslatorInterface $translator, SessionInterface $session): JsonResponse
    {
        $entityManager = $this->getDoctrine()->getManager();

        try {
            $page->removePageReference($pageReference);
            $entityManager->persist($page);
            $entityManager->remove($pageReference);

            $entityManager->flush();

            return new JsonResponse([
                'message' => 'Référence bibliographiques sont mise à jour',
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
     * @Route("/edit_flash/{reference}", name="admin_page_reference_edit_flash", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PAGE')")
     */
    public function editFlash(Request $request, Reference $reference, AuditManager $auditManager, SessionInterface $sfSession)
    {
        $em = $this->getDoctrine()->getManager();

        $output = [];

        $output['id'] = $request->get('pk');
        $output['name'] = $request->get('name');
        $output['value'] = $request->get('value');

        switch ($request->get('name')) {
        case 'title':
            $reference->setTitle($request->get('value'));
            break;
        case 'author':
            $reference->setAuthor($request->get('value'));
            break;
        case 'date':
            $reference->setDate(new \Datetime($request->get('value')));
            break;
        default:
            break;
      }
        $em->persist($reference);
        $em->flush($reference);

        return new JsonResponse($output, 200);
    }
}
