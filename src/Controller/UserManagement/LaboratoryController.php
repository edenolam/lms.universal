<?php

namespace App\Controller\UserManagement;

use App\Entity\UserManagement\Laboratory;
use App\Manager\AuditManager;
use App\Utils\Utils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class LaboratoryController
 */
class LaboratoryController extends AbstractController
{
    /**
     * Affiche la liste des Laboratories
     *
     * @Route("/admin/UserManagement/laboratory/list", name="admin_laboratory_list", methods={"GET"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function list(Request $request)
    {
        $manager = $this->getDoctrine()->getManager();

        $laboratories = $manager->getRepository('App\Entity\UserManagement\Laboratory')->findAll();
        // $countUsers = [];
        // foreach ($laboratories as $laboratory) {
        //     $CountUserLab = $manager->getRepository('App\Entity\UserManagement\Laboratory')->getUsersLab($laboratory);
        //     $countUsers[$laboratory->getSlug()] = $CountUserLab;
        // }

        return $this->render('UserManagement/Laboratory/list.html.twig', [
            'laboratories' => $laboratories,
           // 'CountUserLab' => $countUsers,
        ]);
    }

    /**
     *  Affiche le formulaire d'ajout d'une Laboratory en Base de données.
     *
     * @Route("/admin/UserManagement/laboratory/create", name="admin_laboratory_create", methods={"GET", "POST"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function create(Request $request, SessionInterface $session, TranslatorInterface $translator, AuditManager $auditManager)
    {
        $laboratory = new Laboratory();

        $manager = $this->getDoctrine()->getManager();

        $form = $this->createForm("App\Form\UserManagement\LaboratoryType", $laboratory);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($laboratory->getImage()) {
                    $extension = ($laboratory->getImage()->guessExtension()) ? $laboratory->getImage()->guessExtension() : 'png';

                    $nameFile = Utils::slug($laboratory->getTitle(), '-') . '.' . $extension;

                    $laboratory->getImage()->move('uploads/laboratory/', $nameFile);
                    $laboratory->setLogo($nameFile);
                }

                $laboratory->setCreateUser($this->getUser());
                $laboratory->setUpdateUser($this->getUser());

                $manager->persist($laboratory);
                $manager->flush();

                $auditManager->generateAudit(null, $laboratory, 'add', $this->getUser());

                $session->getFlashBag()->add('success', $translator->trans('laboratory.flash.created'));

                return $this->redirect($this->generateUrl('admin_laboratory_view', [
                    'slug' => $laboratory->getSlug()]));
            } else {
                $session->getFlashBag()->add('error', $translator->trans('laboratory.flash.created_error'));
            }
        }

        return $this->render('UserManagement/Laboratory/create.html.twig', [
            'form' => $form->createView(),
            'laboratory' => $laboratory,
        ]);
    }

    /**
     *  Affiche une Laboratory .
     *
     * @Route("/admin/UserManagement/laboratory/view/{slug}", name="admin_laboratory_view", methods={"GET"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function view($slug)
    {
        $manager = $this->getDoctrine()->getManager();

        $laboratory = $manager->getRepository('App\Entity\UserManagement\Laboratory')->findOneBy(['slug' => $slug]);

        if (!$laboratory instanceof Laboratory) {
            throw new BadRequestHttpException($this->get('translator')->trans('global.badRequestHttpException'));
        }

        return $this->render('UserManagement/Laboratory/view.html.twig', [
            'laboratory' => $laboratory,
        ]);
    }

    /**
     *  Affiche le formulaire de modification d'une Laboratory.
     *
     * @Route("/admin/UserManagement/laboratory/edit/{slug}", name="admin_laboratory_edit", methods={"GET", "POST"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function edit(Request $request, $slug, SessionInterface $session, TranslatorInterface $translator, KernelInterface $kernel, AuditManager $auditManager)
    {
        $manager = $this->getDoctrine()->getManager();

        $laboratory = $manager->getRepository('App\Entity\UserManagement\Laboratory')->findOneBy(['slug' => $slug]);

        $old_laboratory = clone $laboratory;

        if (!$laboratory instanceof Laboratory) {
            throw new BadRequestHttpException($this->get('translator')->trans('global.badRequestHttpException'));
        }

        $form = $this->createForm("App\Form\UserManagement\LaboratoryType", $laboratory);

        $old_logo = $laboratory->getLogo();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($laboratory->getImage()) {
                    $extension = ($laboratory->getImage()->guessExtension()) ? $laboratory->getImage()->guessExtension() : 'png';

                    $nameFile = Utils::slug($laboratory->getTitle(), '-') . '_' . $laboratory->getRevision() . '.' . $extension;

                    $laboratory->getImage()->move('uploads/laboratory/', $nameFile);
                    $laboratory->setLogo($nameFile);

                    $fileSystem = new Filesystem();

                    if (!empty($old_logo) && $fileSystem->exists($kernel->getProjectDir() . '/public/uploads/laboratory/' . $old_logo)) {
                        try {
                            $fileSystem->remove([$kernel->getProjectDir() . '/public/uploads/laboratory/' . $old_logo]);
                        } catch (IOExceptionInterface $exception) {
                            $session->getFlashBag()->add('error', $this->get('translator')->trans('laboratory.flash.delete_image_error'));
                        }
                    }
                }

                $laboratory->setCreateUser($this->getUser());
                $laboratory->setUpdateUser($this->getUser());

                $laboratory->setRevision($laboratory->getRevision() + 1);

                $manager->persist($laboratory);
                $manager->flush();

                $auditManager->generateAudit($old_laboratory, $laboratory, 'edit', $this->getUser());

                $session->getFlashBag()->add('success', $translator->trans('laboratory.flash.updated'));

                return $this->redirect($this->generateUrl('admin_laboratory_view', [
                    'slug' => $laboratory->getSlug()]));
            } else {
                $session->getFlashBag()->add('error', $translator->trans('laboratory.flash.updated_error'));
            }
        }

        return $this->render('UserManagement/Laboratory/edit.html.twig', [
            'laboratory' => $laboratory,
            'form' => $form->createView(),
        ]);
    }

    /**
     * activer ou desactiver le laboratory.
     *
     * @Route("/admin/UserManagement/laboratory/active/{slug}", name="admin_laboratory_active", methods={"GET", "POST"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function active($slug, SessionInterface $session, TranslatorInterface $translator, AuditManager $auditManager)
    {
        $manager = $this->getDoctrine()->getManager();

        $laboratory = $manager->getRepository('App\Entity\UserManagement\Laboratory')->findOneBy(['slug' => $slug]);

        $users = $manager->getRepository('App\Entity\UserManagement\User')->findBy(['laboratory' => $laboratory]);

        $old_laboratory = clone $laboratory;

        if (!$laboratory instanceof Laboratory) {
            throw new BadRequestHttpException($translator->trans('global.badRequestHttpException'));
        }
        if (count($users) == 0) {
            if ($laboratory->getIsValid()) {
                $laboratory->setIsValid(false);
                $manager->persist($laboratory);
                $manager->flush();
                $action = 'disable';
            } else {
                $laboratory->setIsValid(true);
                $manager->persist($laboratory);
                $manager->flush();
                $action = 'enable';
            }

            $auditManager->generateAudit($old_laboratory, $laboratory, $action, $this->getUser());

            $session->getFlashBag()->add('success', $translator->trans('laboratory.flash.updated'));
        }

        return $this->redirect($this->generateUrl('admin_laboratory_list'));
    }
}
