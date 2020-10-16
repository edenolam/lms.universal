<?php

namespace App\Controller\FormationManagement;

use App\Entity\FormationManagement\Course;
use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\Page;
use App\Entity\LovManagement\PageType;
use App\Manager\AuditManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/admin/formationManagement/page")
 */
class PageController extends AbstractController
{
    /**
     * Show form for page creation
     *
     * @Route("/create/{slugm}/{slugc}", name="admin_page_create", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PAGE')")
     */
    public function create(Request $request, $slugm, $slugc, TranslatorInterface $translator, AuditManager $auditManager, SessionInterface $session)
    {
        $em = $this->getDoctrine()->getManager();

        $course = $em->getRepository('App\Entity\FormationManagement\Course')->findOneBy(['slug' => $slugc]);

        $module = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBy(['slug' => $slugm]);

        if (!$module instanceof Module || !$course instanceof Course) {
            throw new NotFoundHttpException('Module or Course Not Found Exception');
        }
        $page = new Page();

        $page->setPageType($em->getRepository('App\Entity\LovManagement\PageType')->findOneBy(['conditional' => PageType::Expert]));

        $form = $this->createForm('App\Form\FormationManagement\PageType', $page, ['module' => $module]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $page->setContentCode($request->request->get('js'));
            $page->setAuthor($this->getUser());
            $em->persist($page);
            $em->flush();

            $auditManager->generateAudit(null, $page, 'add', $this->getUser());
            $session->getFlashBag()->add('Réussite', '');

                return $this->redirect($this->generateUrl(
                    'admin_page_edit',
                    [
                        'slugm' => $module->getSlug(),
                        'slugc' => $course->getSlug(),
                        'slug' => $page->getSlug()]
                ));
        }

        $typeTests = $em->getRepository('App\Entity\LovManagement\TypeTest')->findAll();

        return $this->render('FormationManagement/Page/create.html.twig', [
              'form' => $form->createView(),
              'course' => $course,
              'module' => $module,
              'typeTests' => $typeTests,
              'page' => $page,
          ]);
    }

    /**
     * Show form for page edit
     *
     * @Route("/edit/{slugm}/{slugc}/{slug}", name="admin_page_edit", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PAGE') or is_granted('ROLE_PUBLICATION_MODULE')")
     */
    public function edit(Request $request, $slugm, $slugc, $slug, TranslatorInterface $translator, AuditManager $auditManager, SessionInterface $session)
    {
        $em = $this->getDoctrine()->getManager();

        $page = $em->getRepository('App\Entity\FormationManagement\Page')->findOneBy(['slug' => $slug]);
        $old_page = clone $page;

        $course = $em->getRepository('App\Entity\FormationManagement\Course')->findOneBy(['slug' => $slugc]);

        $module = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBy(['slug' => $slugm]);

        $form = $this->createForm('App\Form\FormationManagement\PageType', $page, ['module' => $module]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($page);
            $em->flush();

            $auditManager->generateAudit($old_page, $page, 'edit', $this->getUser());

            $session->getFlashBag()->add('success', $translator->trans('updated'));

            return $this->redirect($this->generateUrl(
                'admin_page_edit',
                [
                'slugm' => $module->getSlug(),
                'slugc' => $course->getSlug(),
                'slug' => $page->getSlug(),
                'success' => true]
            ));
        }

        return $this->render('FormationManagement/Page/edit.html.twig', [
            'form' => $form->createView(),
            'page' => $page,
            'course' => $course,
            'module' => $module,
            'formationPath' => $module->getFormationPath(),
            'typeTests' => $em->getRepository('App\Entity\LovManagement\TypeTest')->findAll(),
        ]);
    }

    /**
     * Desactivate page with ajax
     *
     * @Route("/page/desactivate/{slug}/{slugm}", name="admin_page_desactivate", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PAGE')")
     */
    public function desactivate(Request $request, $slug, $slugm, AuditManager $auditManager, SessionInterface $session, TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();

        $page = $em->getRepository("App\Entity\FormationManagement\Page")->findOneBy(['slug' => $slug]);
        $module = $em->getRepository("App\Entity\FormationManagement\Module")->findOneBy(['slug' => $slugm]);

        $old_page = clone $page;

        if ($page->getIsValid()) {
            $page->setIsValid(false);
            $action = 'disable';
        } else {
            $page->setIsValid(true);
            $action = 'enable';
        }

        $em->persist($page);
        $em->flush();

        $auditManager->generateAudit($old_page, $page, $action, $this->getUser());

        $session->getFlashBag()->add('success', $translator->trans('updated'));

        return $this->redirect($this->generateUrl('admin_course_edit', [
            'slug' => $page->getCourse()->getSlug(),
            'slugm' => $module->getSlug(),
        ]));
    }

    /**
     * Supprimer une page
     *
     * @Route("/delete/{slug}/{slugm}", name="admin_page_delete", methods={"GET","POST"})
     *
     * @Security("has_role('ROLE_GESTION_PAGE')")
     */
    public function delete(string $slug, string $slugm, TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();

        $page = $em->getRepository("App\Entity\FormationManagement\Page")->findOneBy(['slug' => $slug]);
        $module = $em->getRepository("App\Entity\FormationManagement\Module")->findOneBy(['slug' => $slugm]);
        $course = $page->getCourse();

        if ($page) {
            $page->setIsValid(false);
            $page->setIsDeleted(true);
            $em->persist($page);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', $translator->trans('deleted'));
        }

        return $this->redirect($this->generateUrl('admin_course_edit', [
                        'slug' => $course->getSlug(),
                        'slugm' => $module->getSlug(),
                    ]));
    }
}
