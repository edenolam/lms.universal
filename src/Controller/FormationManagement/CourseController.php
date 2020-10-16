<?php

namespace App\Controller\FormationManagement;

use App\Entity\FormationManagement\Course;
use App\Entity\FormationManagement\ModuleCourse;
use App\Entity\FormationManagement\Page;
use App\Manager\AuditManager;
use App\Utils\Utils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use setasign\Fpdi\Fpdi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use GravityMedia\Ghostscript\Ghostscript;
//use Symfony\Component\Process\Process;

/**
 * @Route("/admin/formationManagement/course")
 */
class CourseController extends AbstractController
{
    /**
     * Show form to create course
     *
     * @Route("/create/{slugm}", name="admin_course_create", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_UNITES_PEDAGOGIQUES')")
     */
    public function create(Request $request, $slugm, TranslatorInterface $translator, AuditManager $auditManager,KernelInterface $kernel)
    {
        $course = new Course();
        $course = new Course();

        $em = $this->getDoctrine()->getManager();

        $module = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBySlug($slugm);

        $form = $this->createForm('App\Form\FormationManagement\CourseType', $course);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $moduleCourse = new ModuleCourse();
            $moduleCourse->setSort($module->getMaxSort());
            $moduleCourse->setModule($module);
            $moduleCourse->setCourse($course);
            $em->persist($moduleCourse);

            $em->persist($course);
            $em->flush();

            $auditManager->generateAudit(null, $course, 'add', $this->getUser());

            $this->get('session')->getFlashBag()->add('success', $translator->trans('created'));
            // $this->addFlash() is equivalent to $request->getSession()->getFlashBag()->add()

            if ($form['file']->getData()) {
                $this->splitPDF($form['file']->getData(), $course, $course->getTitle(), $auditManager, $kernel);
            }

            if (sizeof($course->getPages()) > 0) {
                $page = $em->getRepository('App\Entity\FormationManagement\Page')->findFirstPage($course);
                if ($page) {
                    return $this->redirect($this->generateUrl('admin_page_edit', [
                        'slug' => $page[0]->getSlug(),
                        'slugm' => $module->getSlug(),
                        'slugc' => $course->getSlug(),
                    ]));
                }
            }

            return $this->redirect($this->generateUrl('admin_page_create', [
                'slugm' => $module->getSlug(),
                'slugc' => $course->getSlug(),
            ]));
        }

        return $this->render('FormationManagement/Course/create.html.twig', [
            'form' => $form->createView(),
            'typeTests' => $em->getRepository('App\Entity\LovManagement\TypeTest')->findAll(),
            'module' => $module,
        ]);
    }

    /**
     * Show form to create course
     *
     * @Route("/edit/{slugm}/{slug}", name="admin_course_edit", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_UNITES_PEDAGOGIQUES') or is_granted('ROLE_PUBLICATION_MODULE')")
     */
    public function edit(Request $request, $slug, $slugm, TranslatorInterface $translator, AuditManager $auditManager,KernelInterface $kernel)
    {
        $em = $this->getDoctrine()->getManager();

        $module = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBy(['slug' => $slugm]);
        $course = $em->getRepository('App\Entity\FormationManagement\Course')->findOneBy(['slug' => $slug]);

        $old_course = clone $course;

        $form = $this->createForm('App\Form\FormationManagement\CourseType', $course);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($course);
            $em->flush();

            $auditManager->generateAudit($old_course, $course, 'edit', $this->getUser());

            $this->get('session')->getFlashBag()->add('success', $translator->trans('updated'));

            if ($form['file']->getData()) {
                $this->splitPDF($form['file']->getData(), $course, $course->getTitle(), $auditManager, $kernel);
            }

            if (sizeof($course->getPages()) > 0) {
                $page = $em->getRepository('App\Entity\FormationManagement\Page')->findFirstPage($course);
                if ($page) {
                    return $this->redirect($this->generateUrl('admin_page_edit', [
                        'slug' => $page[0]->getSlug(),
                        'slugm' => $module->getSlug(),
                        'slugc' => $course->getSlug(),
                    ]));
                }
            }

            return $this->redirect($this->generateUrl('admin_page_create', [
                'slugm' => $module->getSlug(),
                'slugc' => $course->getSlug(),
            ]));
        }

        return $this->render('FormationManagement/Course/edit.html.twig', [
            'form' => $form->createView(),
            'course' => $course,
            'module' => $module,
            'typeTests' => $em->getRepository('App\Entity\LovManagement\TypeTest')->findAll(),
        ]);
    }

    /**
     * Show all pages and allow order them
     *
     * @Route("/order/{course}/{order}", name="admin_course_page_order", methods={"POST"})
     *
     * @Security("is_granted('ROLE_GESTION_UNITES_PEDAGOGIQUES')")
     */
    public function order(Request $request, Course $course, string $order, AuditManager $auditManager)
    {
        $em = $this->getDoctrine()->getManager();

        $orders = explode(',', $order);

        try {
            foreach ($orders as $key => $id) {
                $page = $em->getRepository('App\Entity\FormationManagement\Page')->find($id);
                $old_page = clone $page;
                if ($page) {
                    $page->setSort($key + 1);
                    $em->persist($page);

                    $auditManager->generateAudit($old_page, $page, 'order', $this->getUser());
                }
            }

            $em->flush();

            return new JsonResponse([
                'message' => 'Les ordres sont mise à jour',
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
            'message' => 'admin_course_page_order',
            'success' => 1
        ]);
    }

    private function splitPDF($fileinput, Course $course, $title, AuditManager $auditManager,KernelInterface $kernel)
    {
        $manager = $this->getDoctrine()->getManager();
        $pageType = $manager->getRepository('App\Entity\LovManagement\PageType')->findOneBy([
            'conditional' => 'pdf'
        ]);
        $Tempsystem = new Filesystem();
        if (!$Tempsystem->exists('uploads/temporary')) {
            try {
                $Tempsystem->mkdir('uploads/temporary/', 0700);
            } catch (IOExceptionInterface $exception) {
                echo 'An error occurred while creating your directory at ' . $exception->getPath();
            }
        }
        $new_pdf = $kernel->getProjectDir() .'/public/uploads/temporary/' . uniqid('', true) . '.pdf';
      
        shell_exec( "/usr/bin/gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -sOutputFile=".$new_pdf." ".$fileinput->getPathName()."");
        // var_dump($new_pdf);
        // var_dump($fileinput);
        //$file = file($new_pdf);
        //var_dump($file);

        //  $ghostscript = new Ghostscript([
        //      'quiet' => true
        //  ]);
        //  $device = $ghostscript->createPdfDevice();
        //  $device->setCompatibilityLevel(1.4);
        //  $file = $device->createProcess($fileinput);
        
        // $file = $device->createProcess($fileinput);

        $maxSort = $course->getMaxSort();

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($new_pdf);

        for ($i = 1; $i <= $pageCount; $i++) {
            $newPDF = new Fpdi();
            $newPDF->setSourceFile($new_pdf);

            $templateId = $newPDF->importPage($i);
            $size = $newPDF->getTemplateSize($templateId);

            if ($size['width'] > $size['height']) {
                $newPDF->AddPage('L', [$size['width'], $size['height']]);
            } else {
                $newPDF->AddPage('P', [$size['width'], $size['height']]);
            }
            $newPDF->useTemplate($templateId);

            $page = new Page();
            $page->setTitle($title . ' - ' . $i);

            $filesystem = new Filesystem();
            if (!$filesystem->exists('uploads/files')) {
                try {
                    $filesystem->mkdir('uploads/files/', 0700);
                } catch (IOExceptionInterface $exception) {
                    echo 'An error occurred while creating your directory at ' . $exception->getPath();
                }
            }

            $uniqFileName = 'uploads/files/' . uniqid('', true) . '.pdf';

            $newPDF->Output($uniqFileName, 'F');

            $pagePDF = new File($uniqFileName);
            $page->setFile($pagePDF);
            $page->setUri(str_replace('uploads/files/', '', $uniqFileName));
            $page->setCourse($course);
            $page->setSort($maxSort);
            $maxSort++;

            $page->setAuthor($this->getUser());
            $page->setPageType($pageType);

            $manager->persist($page);
            $manager->flush();

            $auditManager->generateAudit(null, $page, 'add pdf page', $this->getUser());
        }

        return;
    }

    /**
     * Desactivate question with ajax
     *
     * @Route("/course/desactivate/{slug}/{slugm}", name="admin_course_desactivate", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_UNITES_PEDAGOGIQUES')")
     */
    public function desactivate(Request $request, $slug, $slugm, AuditManager $auditManager)
    {
        $em = $this->getDoctrine()->getManager();

        $course = $em->getRepository("App\Entity\FormationManagement\Course")->findOneBy(['slug' => $slug]);
        $module = $em->getRepository("App\Entity\FormationManagement\Module")->findOneBy(['slug' => $slugm]);

        $old_course = clone $course;

        if ($course->getIsValid()) {
            $course->setIsValid(false);
            $action = 'disable';
            foreach ($course->getPages() as $page) {
                $old_page = clone $page;
                $page->setIsValid(false);
                $em->persist($page);
                $em->flush();
                $auditManager->generateAudit($old_page, $page, $action, $this->getUser());
            }
        } else {
            $course->setIsValid(true);
            $action = 'enable';
        }

        $em->persist($course);
        $em->flush();
        $auditManager->generateAudit($old_course, $course, $action, $this->getUser());

        return $this->redirect($this->generateUrl('admin_module_edit', [
            'slug' => $module->getSlug(),
        ]));
    }

    /**
     * Supprimer une page
     *
     * @Route("/delete/{slug}/{slugm}", name="admin_course_delete", methods={"GET","POST"})
     *
     * @Security("has_role('ROLE_GESTION_UNITES_PEDAGOGIQUES')")
     */
    public function delete(string $slug, string $slugm, TranslatorInterface $translator, AuditManager $auditManager)
    {
        $em = $this->getDoctrine()->getManager();

        $course = $em->getRepository("App\Entity\FormationManagement\Course")->findOneBy(['slug' => $slug]);
        $module = $em->getRepository("App\Entity\FormationManagement\Module")->findOneBy(['slug' => $slugm]);

        if ($course) {
            $old_course = clone $course;
            $course->setIsValid(false);
            $course->setIsDeleted(true);
            $em->persist($course);
            $em->flush();
            foreach ($course->getPages() as $page) {
                $old_page = clone $page;
                $page->setIsValid(false);
                $page->setIsDeleted(true);
                $em->persist($page);
                $em->flush();
                $auditManager->generateAudit($old_page, $page, "deleted", $this->getUser());
            }
            $auditManager->generateAudit($old_course, $course, "deleted", $this->getUser());
            $this->get('session')->getFlashBag()->add('success', $translator->trans('deleted'));
        }

        return $this->redirect($this->generateUrl('admin_module_edit', [
            'slug' => $module->getSlug(),
        ]));
    }
}
