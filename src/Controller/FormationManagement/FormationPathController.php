<?php

namespace App\Controller\FormationManagement;

use App\Entity\FormationManagement\FormationPath;
use App\Entity\FormationManagement\FormationPathModule;
use App\Form\FormationManagement\OrderModuleType;
use App\Manager\AuditManager;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/admin/formationManagement/formationPath")
 */
class FormationPathController extends AbstractController
{
    /**
     * Show list of formationsPath
     *
     * @Route("/list", name="admin_formation_path_list", defaults={"page": "1"}, methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PARCOURS_FORMATION')")
     */
    public function list(Request $request, int $page)
    {
        $em = $this->getDoctrine()->getManager();

        $formationPaths = $em->getRepository('App\Entity\FormationManagement\FormationPath')->findAll();

        return $this->render('FormationManagement/FormationPath/list.html.twig', [
      'formationPaths' => $formationPaths,
    ]);
    }

    /**
     * Show form for formationPath creatation
     *
     * @Route("/create", name="admin_formation_path_create", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PARCOURS_FORMATION')")
     */
    public function create(Request $request, TranslatorInterface $translator, AuditManager $auditManager)
    {
        $formationPath = new FormationPath();

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm('App\Form\FormationManagement\FormationPathType', $formationPath);

        $form->handleRequest($request);

        $noSession = true;

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($formationPath);
            $em->flush();

            $auditManager->generateAudit(null, $formationPath, 'add', $this->getUser());

            $this->get('session')->getFlashBag()->add('success', $translator->trans('created'));

            return $this->redirect($this->generateUrl('admin_formation_path_edit_module', [
            'slug' => $formationPath->getSlug()
          ]));
        }

        return $this->render('FormationManagement/FormationPath/create.html.twig', [
          'form' => $form->createView(),
          'noSession' => $noSession,
        ]);
    }

    /**
     * Show form to edit saved formationPath
     * @Route("/edit_module/{slug}", name="admin_formation_path_edit_module", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PARCOURS_FORMATION')")
     */
    public function editModule(Request $request, $slug, TranslatorInterface $translator, AuditManager $auditManager)
    {
        $em = $this->getDoctrine()->getManager();

        $formationPath = $em->getRepository('App\Entity\FormationManagement\FormationPath')->findOneBy(['slug' => $slug]);
        $old_entity = clone $formationPath;
        $moduleSondageUmgs = $em->getRepository('App\Entity\FormationManagement\Module')->findBy(['isDefaultModule' => true, 'isValid' =>true]);
        foreach($moduleSondageUmgs as $moduleSondageUmg){
            $existingFormationPathModule = $em->getRepository('App\Entity\FormationManagement\FormationPathModule')->findByModuleANDFormationPathId($moduleSondageUmg->getId(), $formationPath->getId());
            if($existingFormationPathModule == null){
                $formationPathModule = new FormationPathModule();
                $formationPathModule->setModule($moduleSondageUmg);
                $formationPathModule->setSort(100);
                $formationPathModule->setFormationPath($formationPath);
                $formationPathModule->setTitle($formationPath->getTitle() . '_' . $moduleSondageUmg->getTitle());
                $em->persist($formationPathModule);
                $em->flush();
            }
        }

        $form = $this->createForm('App\Form\FormationManagement\FormationPathType', $formationPath);

        $oldFormationPathModules = $em->getRepository('App\Entity\FormationManagement\FormationPathModule')->findByFormationPathId($formationPath->getId());

        $form->handleRequest($request);
        $noSession = $formationPath->checkActiveSessions();

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($oldFormationPathModules as $oldFormationPathModule) {
                // if (false === in_array($oldFormationPathModule->getModule(),$modules)) {
                $module = $oldFormationPathModule->getModule();
                $module->removeFormationPathModules($oldFormationPathModule);
                $em->persist($module);
                $em->flush();
                $em->remove($oldFormationPathModule);
                $em->persist($oldFormationPathModule);
                $em->flush();

                // }
            }

            // exit();
            //CHECK REMOVED MODULES
            $newFormationPathModuleData = [];
            if ($request->request->get('formation_path') != null && array_key_exists('formationPathModules', $request->request->get('formation_path'))) {
                $newFormationPathModuleData = $request->request->get('formation_path')['formationPathModules'];
            }

            foreach ($newFormationPathModuleData as $data) {
                $module = $em->getRepository('App\Entity\FormationManagement\Module')->find($data['module']);
                //GET FORMATIONPATHMODULE WITH FORMATIONPATH ID & MODULE ID
                $existingFormationPathModule = $em->getRepository('App\Entity\FormationManagement\FormationPathModule')->findByModuleANDFormationPathId($module->getId(), $formationPath->getId());
                if ($existingFormationPathModule == null) {
                    $formationPathModule = new FormationPathModule();
                    $formationPathModule->setModule($module);
                    $formationPathModule->setSort($data['sort']);
                    $formationPathModule->setFormationPath($formationPath);
                    $formationPathModule->setTitle($formationPath->getTitle() . '_' . $module->getTitle());
                    $em->persist($formationPathModule);
                    $em->flush();
                }
            }

            $em->persist($formationPath);
            $em->flush();

            $auditManager->generateAudit($old_entity, $formationPath, 'edit module', $this->getUser());

            $this->get('session')->getFlashBag()->add('success', $translator->trans('updated'));

            return $this->redirect($this->generateUrl('admin_formation_path_view', [
              'slug' => $formationPath->getSlug()
            ]));
        }
        $moduleListe = $em->getRepository('App\Entity\FormationManagement\Module')->findBy(['isValid' => 1]);
        $listOldModule = [];
        foreach ($formationPath->getFormationPathModules() as $foarmtionPathModuleL) {
            $listOldModule[] = $foarmtionPathModuleL->getModule();
        }

        $listNewModule = [];
        foreach ($moduleListe as $modulel) {
            if (!in_array($modulel, $listOldModule)) {
                $listNewModule[] = $modulel;
            }
        }

        return $this->render('FormationManagement/FormationPath/edit_module.html.twig', [
          'form' => $form->createView(),
          'formationPath' => $formationPath,
          'noSession' => $noSession,
          'newModuleListe' => $listNewModule,
          'oldModuleListe' => $listOldModule,
        ]);
    }

    /**
     * Show form to edit saved formationPath
     * @Route("/edit/{slug}", name="admin_formation_path_edit", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PARCOURS_FORMATION')")
     */
    public function edit(Request $request, $slug, TranslatorInterface $translator, AuditManager $auditManager)
    {
        $em = $this->getDoctrine()->getManager();

        $formationPath = $em->getRepository('App\Entity\FormationManagement\FormationPath')->findOneBy(['slug' => $slug]);

        $old_entity = clone $formationPath;

        $form = $this->createForm('App\Form\FormationManagement\FormationPathType', $formationPath);

        //PREPARE MODULES FOR FORMS
        $modules = new ArrayCollection();
        foreach ($formationPath->getFormationPathModules() as $formationPathModule) {
            $modules->add($formationPathModule->getModule());
        }

        //$form->get('modules')->setData($modules);
        $form->handleRequest($request);
        //$noSession = $formationPath->checkActiveSessions();

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($formationPath);
            $em->flush();

            $auditManager->generateAudit($old_entity, $formationPath, 'edit', $this->getUser());

            $this->get('session')->getFlashBag()->add('success', $translator->trans('updated'));
            
            return $this->redirect($this->generateUrl('admin_formation_path_edit', [
              'slug' => $formationPath->getSlug()
            ]));
        }

        return $this->render('FormationManagement/FormationPath/edit.html.twig', [
          'form' => $form->createView(),
          'formationPath' => $formationPath,
          //'noSession' => $noSession,
        ]);
    }

    /**
     * Show formationPath view
     *
     * @Route("/view/{slug}", name="admin_formation_path_view", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PARCOURS_FORMATION')")
     */
    public function view(Request $request, $slug)
    {
        $em = $this->getDoctrine()->getManager();

        $formationPath = $em->getRepository('App\Entity\FormationManagement\FormationPath')->findOneBy([
          'slug' => $slug
        ]);

        $form = $this->createForm('App\Form\FormationManagement\FormationPathType', $formationPath);

        if ($formationPath) {
            $formationPathModules = $em->getRepository('App\Entity\FormationManagement\FormationPathModule')->findByFormationPathId($formationPath->getId());

            return $this->render('FormationManagement/FormationPath/view.html.twig', [
              'formationPath' => $formationPath,
              'checknoSession' => $formationPath->checkActiveSessions(),
              'form' => $form->createView(),
            ]);
        } else {
            return $this->redirect($this->generateUrl('admin_formation_path_list'));
        }
    }

    /**
     * SEARCH FORMATION PATHS
     *
     * @Route("/search/{keywords}", name="admin_formation_path_search", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PARCOURS_FORMATION')")
     */
    public function _search(Request $request, string $keywords)
    {
        $keywords = trim(urldecode($keywords));

        $em = $this->getDoctrine()->getManager();

        $formationPaths = $em->getRepository('App\Entity\FormationManagement\FormationPath')->findBySearchQuery($keywords);

        return $this->render('FormationManagement/FormationPath/search.html.twig', [
      'formationPaths' => $formationPaths,
      'keywords' => $keywords
    ]);
    }

    /**
     * Show form to edit saved formationPath
     * @Route("/duplicate/{slug}", name="admin_formation_path_duplicate", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PARCOURS_FORMATION')")
     */
    public function _duplicate(Request $request, $slug)
    {
        $em = $this->getDoctrine()->getManager();
        $formationPath = $em->getRepository('App\Entity\FormationManagement\FormationPath')->findOneBy([
      'slug' => $slug
    ]);

        $duplicate = new FormationPath();

        $duplicate->setTitle($formationPath->getTitle());
        $duplicate->setDescription($formationPath->getDescription());
        $duplicate->setRealisationTime($formationPath->getRealisationTime());
        $duplicate->setCreateDate();
        $duplicate->setUpdateDate();
        $duplicate->setRevision($formationPath->getRevision());

        $form = $this->createForm('App\Form\FormationManagement\FormationPathType', $duplicate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //ADD FORMATIONPATHMODULE WITH MODULE
            foreach ($formationPath->getFormationPathModules() as $formationPathModule) {
                $duplicateFormationPathModule = new FormationPathModule();
                $module = $em->getRepository('App\Entity\FormationManagement\Module')->find($formationPathModule->getModule()->getId());
                $duplicateFormationPathModule->setModule($module);
                $duplicateFormationPathModule->setFormationPath($duplicate);
                $duplicateFormationPathModule->setSort($formationPathModule->getSort());
                $em->persist($duplicateFormationPathModule);
            }

            $em->persist($duplicate);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_formation_path_list'));
        }

        return $this->render('FormationManagement/FormationPath/duplicate.html.twig', [
      'form' => $form->createView(),
      'formationPath' => $formationPath
    ]);
    }

    /**
     * Show all modules link with the formationPath & allow to order them
     * @Route("/order/{slug}", name="admin_formation_path_module_order", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PARCOURS_FORMATION')")
     */
    public function order(Request $request, $slug, AuditManager $auditManager)
    {
        $em = $this->getDoctrine()->getManager();

        $formationPath = $em->getRepository('App\Entity\FormationManagement\FormationPath')->findOneBy([
      'slug' => $slug
    ]);

        $old_entity = clone $formationPath;

        $modules = [];

        foreach ($formationPath->getFormationPathModules() as $formationPathModule) {
            array_push($modules, $formationPathModule->getModule());
        }

        $form = $this->createForm(OrderModuleType::class, $formationPath);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($formationPath->getFormationPathModules() as $formationPathModule) {
                $em->persist($formationPathModule);
                $auditManager->generateAudit($old_entity, $formationPath, 'order', $this->getUser());
            }
            $em->flush();

            return $this->redirect($this->generateUrl('admin_formation_path_view', ['slug' => $formationPath->getSlug()]));
        }

        return $this->render('FormationManagement/FormationPath/order.html.twig', [
      'formationPath' => $formationPath,
      'modules' => $modules,
      'form' => $form->createView()
    ]);
    }

    /**
     * Desactivate formationPath with ajax
     *
     * @Route("/desactivate/{slug}", name="admin_formation_path_desactivate", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_PARCOURS_FORMATION')")
     */
    public function desactivate(Request $request, $slug, AuditManager $auditManager)
    {
        $em = $this->getDoctrine()->getManager();

        $formationPath = $em->getRepository('App\Entity\FormationManagement\FormationPath')->findOneBy([
      'slug' => $slug
    ]);

        $old_entity = clone $formationPath;

        if ($formationPath->checkActiveSessions()) {
            if ($formationPath->getIsValid()) {
                $formationPath->setIsValid(false);
                $action = 'disable';
            } else {
                $formationPath->setIsValid(true);
                $action = 'enable';
            }

            $em->persist($formationPath);
            $em->flush();

            $auditManager->generateAudit($old_entity, $formationPath, $action, $this->getUser());

            return new Response('OK');
        } else {
            return new Response('KO');
        }
    }
}
