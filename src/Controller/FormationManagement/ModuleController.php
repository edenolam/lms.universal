<?php

namespace App\Controller\FormationManagement;

use App\Entity\FormationManagement\Course;
use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\VersioningModule;
use Symfony\Component\Ldap\Ldap;
use App\Entity\LovManagement\ModuleType;
use App\Manager\AuditManager;
use App\Manager\ModuleManager;
use App\Manager\ScormManager;
use App\Repository\FormationManagement\ModuleRepository;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/admin/formationManagement/module")
 */
class ModuleController extends AbstractController
{
    /**
     * Show list of module
     *
     * @Route("/list", name="admin_module_list", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_MODULES') or is_granted('ROLE_PUBLICATION_MODULE')")
     */
    public function list(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $modulesUnArchived = $em->getRepository('App\Entity\FormationManagement\Module')->findby(['isArchived' => false]);
        $modulesArchived = $em->getRepository('App\Entity\FormationManagement\Module')->findby(['isArchived' => true]);

        return $this->render('FormationManagement/Module/list.html.twig', [
            'modules' => $modulesUnArchived,
            'modulesArchived' => $modulesArchived
        ]);
    }

    /**
     * Show form to create module
     *
     * @Route("/create", name="admin_module_create", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_MODULES')")
     */
    public function create(Request $request, ScormManager $scormManager, TranslatorInterface $translator, AuditManager $auditManager, SessionInterface $sfSession)
    {
        $em = $this->getDoctrine()->getManager();

        $module = new Module();

        $form = $this->createForm('App\Form\FormationManagement\ModuleType', $module);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($module->checkModuleValid() === false) {
                $sfSession->getFlashBag()->add('error', $translator->trans('formation.module.action.validationModeFalse'));
            } else {
                try {
                    $em->persist($module);
                    $em->flush();

                    $versioningModule = new VersioningModule();
                    $versioningModule->setModuleVersion($module->getVersion());
                    $versioningModule->setAction('Mise en Conception');
                    $versioningModule->setActor($this->getUser());
                    $versioningModule->setHasRequiredRole($this->isGranted('ROLE_GESTION_MODULES'));
                    $versioningModule->setModule($module);

                    $em->persist($versioningModule);
                    $em->flush();
                    $auditManager->generateAudit(null, $module, 'add', $this->getUser());

                    $sfSession->getFlashBag()->add('success', $translator->trans('formation.module.action.created'));
                } catch (\Doctrine\DBAL\DBALException $exception) {
                    $sfSession->getFlashBag()->add('error', $exception->getMessage());
                } catch (\Exception $e) {
                    $sfSession->getFlashBag()->add('error', $e->getMessage());
                }

                // if scrom module
                if ($module->getScormZip()) {

                        $ds = DIRECTORY_SEPARATOR;
                        $hashName = Uuid::uuid4()->toString() . '.zip';
                        $scormData = $scormManager->parseScormArchive($module->getScormZip());
                        $scormManager->unzipScormArchive($module, $module->getScormZip(), $hashName);

                        $module->setScormPath($ds . 'scorm' . $ds . $module->getId() . $ds . $hashName . $ds . 'index.html');
                        $module->setIsScorm(1);
                        $em->persist($module);
                        $em->flush();

                        $serializedScorm = $scormManager->createScorm($module, [
                            'hashName' => $hashName,
                            'version' => $scormData['version'],
                            'scos' => $scormData['scos'],
                        ]);
                        return $this->redirect($this->generateUrl('admin_module_edit', ['slug' => $module->getSlug()]));


                } elseif ($module->getType()->getConditional() === ModuleType::Presentiel) {
                    //return $this->redirect($this->generateUrl('admin_module_edit', [ 'slug'=>$module->getSlug() ] ));
                    return $this->redirect($this->generateUrl('admin_module_list', ['slugm' => $module->getSlug()]));
                } elseif ($module->getType()->getConditional() === ModuleType::Scorm) {
                    return $this->redirect($this->generateUrl('admin_module_edit', ['slug' => $module->getSlug()]));
                }

                return $this->redirect($this->generateUrl('admin_course_create', ['slugm' => $module->getSlug()]));
            }
        }

        return $this->render('FormationManagement/Module/create.html.twig', [
            'form' => $form->createView(),
            'validationModes' => $em->getRepository('App\Entity\LovManagement\ValidationMode')->findBy(['isValid' => 1])
        ]);
    }

    /**
     * Show form to dupliquer a module
     *
     * @Route("/dupliquer", name="admin_module_dupliquer", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_MODULES')")
     */
    public function dupliquer(Request $request, ScormManager $scormManager, TranslatorInterface $translator, AuditManager $auditManager, SessionInterface $sfSession, ModuleManager $moduleManager)
    {
        $em = $this->getDoctrine()->getManager();
        //$module = new Module();
        $defaultData = ['message' => 'dupliquer une module'];

        $form = $this->createFormBuilder($defaultData)
            ->add(
                'module',
                EntityType::class,
                [
                    'class' => 'App\Entity\FormationManagement\Module',
                    'choice_label' => function ($module) {
                        $label = ''.$module->getTitle().' V.'.$module->getVersion().' ('.$module->getRegulatoryRef().')';
                         return $label ;
                    },
                    'expanded' => false,
                    'multiple' => false,
                    'required' => true,
                    'placeholder' => "--",
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('m')
                            ->where('m.isValid = true')
                            ->andWhere('m.isScorm = false')
                            ->orderBy('m.title', 'ASC');
                    }
                ]
            )->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $moduleManager->dupliquer($form['module']->getData());

                $sfSession->getFlashBag()->add('success', $translator->trans('formation.module.action.copy'));

                return $this->redirect($this->generateUrl('admin_module_list'));
            } catch (\Doctrine\DBAL\DBALException $exception) {
                $sfSession->getFlashBag()->add('error', $exception->getMessage());
            } catch (\Exception $e) {
                $sfSession->getFlashBag()->add('error', $e->getMessage());
            }
        }

        return $this->render('FormationManagement/Module/dupliquer.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Show formationPath view
     *
     * @Route("/edit/{slug}", name="admin_module_edit", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_MODULES') or is_granted('ROLE_PUBLICATION_MODULE')")
     */
    public function edit(Request $request, $slug, ScormManager $scormManager, TranslatorInterface $translator, SessionInterface $sfSession, AuditManager $auditManager)
    {
        $em = $this->getDoctrine()->getManager();

        $module = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBy(['slug' => $slug]);

        if (!$module instanceof Module) {
            throw new NotFoundHttpException('Module Not Found Exception');
        }
        $old_entity = clone $module;

        $form = $this->createForm('App\Form\FormationManagement\ModuleType', $module);

        //PREPARE COURSES
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($module->checkModuleValid() === false) {
                $sfSession->getFlashBag()->add('error', $translator->trans('formation.module.action.validationModeFalse'));
            } else {
                if ($module->getScormZip()) {
                    $ds = DIRECTORY_SEPARATOR;
                    $hashName = Uuid::uuid4()->toString() . '.zip';
                    $scormData = $scormManager->parseScormArchive($module->getScormZip());
                    $scormManager->unzipScormArchive($module, $module->getScormZip(), $hashName);

                    $module->setScormPath($ds . 'scorm' . $ds . $module->getId() . $ds . $hashName . $ds . 'index.html');
                    $module->setIsScorm(1);
                    $em->persist($module);
                    $em->flush();

                    $serializedScorm = $scormManager->createScorm($module, [
                        'hashName' => $hashName,
                        'version' => $scormData['version'],
                        'scos' => $scormData['scos'],
                    ]);
                } else {
                    $em->persist($module);
                    $em->flush();
                }

                $auditManager->generateAudit($old_entity, $module, 'edit', $this->getUser());

                $sfSession->getFlashBag()->add('success', $translator->trans('formation.module.action.updated'));

                if (sizeof($module->getModuleCourses()) > 0) {
                    $courses = $em->getRepository('App\Entity\FormationManagement\ModuleCourse')->findByModuleId($module->getId());

                    return $this->redirect($this->generateUrl('admin_course_edit', [
                        'slug' => $courses[0]->getCourse()->getSlug(),
                        'slugm' => $module->getSlug()
                    ]));
                } elseif ($module->getIsScorm() || $module->getType()->getConditional() === ModuleType::Presentiel || $module->getType()->getConditional() === ModuleType::Scorm) {
                    return $this->redirect($this->generateUrl('admin_module_edit', [
                        'slug' => $module->getSlug()
                    ]));
                } else {
                    return $this->redirect($this->generateUrl('admin_course_create', [
                        'slugm' => $module->getSlug()
                    ]));
                }
            }
        }


        return $this->render('FormationManagement/Module/edit.html.twig', [
            'form' => $form->createView(),
            'module' => $module,
            'moduleTypes' => $em->getRepository('App\Entity\LovManagement\ModuleType')->findAll(),
            'moduleType' => $module->getType(),
            'validationModes' => $em->getRepository('App\Entity\LovManagement\ValidationMode')->findBy(['isValid' => 1]),
            'typeTests' => $em->getRepository('App\Entity\LovManagement\TypeTest')->findAll(),
            'nbFormationLinked' => $module->getNbFormationLinked(),
        ]);
    }

    /**
     * Desactivate module with ajax
     *
     * @Route("/desactivate/{slug}", name="admin_module_desactivate", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_MODULES') ")
     */
    public function desactivate(Request $request, $slug, AuditManager $auditManager, SessionInterface $sfSession, TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();
        $module = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBy(['slug' => $slug]);
        $old_entity = clone $module;
        $data = json_decode($request->getContent(), true);

        if ($data['comentary'] && !$module->checkActiveSessions() && $module->getIsValid() && $this->isGranted('ROLE_PUBLICATION_MODULE')) {
            $module->setIsValid(false);
            $action = 'disable';
            $newVersion = $module->getVersion() + 1;
            $module->setVersion($newVersion);
            $versioningModule = new VersioningModule();
            $versioningModule->setActor($this->getUser());
            $versioningModule->setJustification($data['comentary']);
            $versioningModule->setModule($module);
            $versioningModule->setModuleVersion($newVersion);
            $versioningModule->setAction('Mise en Conception');
            $versioningModule->setHasRequiredRole($this->isGranted('ROLE_GESTION_MODULES'));
            $em->persist($module);
            $em->persist($versioningModule);
            $em->flush();
            $auditManager->generateAudit($old_entity, $module, $action, $this->getUser());
            //$sfSession->getFlashBag()->add('eror', $translator->trans('formation.module.action.canPutoffline'));
            return new JsonResponse([
            'flash' => $sfSession->getFlashBag()->add('success', $translator->trans('versioning.goOffLine.success')),
                'success' => 1]);
        }
        //$sfSession->getFlashBag()->add('eror', $translator->trans('formation.module.action.cantPutoffline'));
        return new JsonResponse([
            'flash' => $sfSession->getFlashBag()->add('error', $translator->trans('versioning.goOffLine.error')),
            'success' => 0]);
    }

    /**
     * activate module with ajax
     *
     * @Route("/activate/{slug}", name="admin_module_activate", methods={"GET","POST"})
     *
     * @Security(" is_granted('ROLE_PUBLICATION_MODULE')")
     */
    public function activate(Request $request, $slug, AuditManager $auditManager, LoggerInterface $logger, UserPasswordEncoderInterface $encoder, SessionInterface $sfSession, TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();
        $module = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBy(['slug' => $slug]);
        $old_entity = clone $module;
        $data = json_decode($request->getContent(), true);

        $userToCheck = $em->getRepository('App\Entity\UserManagement\User')->findOneBy(['username' => $data['login']]);

        if ($userToCheck == $this->getUser()) {
            if (!$userToCheck->getLdapUser()) {
                $isPasswordValid = $encoder->isPasswordValid($userToCheck, $data['psw']);
            } else {
                $ldap_server = $em->getRepository("App\Entity\UserManagement\LdapServer")->findOneBy(['laboratory' => $userToCheck->getLaboratory()->getLdapServer()]);
                if ($ldap_server) {
                    try {
                        $ldap = Ldap::create('ext_ldap', ['connection_string' => $ldap_server->getUrl()]);
                        
                        $ldap->bind($userToCheck->getUserDn(), $data['psw']);
                        $isPasswordValid = true;
                    }catch(\Symfony\Component\Ldap\Exception\ConnectionException $exception){
                        return new JsonResponse([
                            'flash' => $sfSession->getFlashBag()->add('error', $translator->trans('versioning.goOnline.success')),
                            'success' => 0]); 
                    }
                }else{
                    $isPasswordValid = false; 
                }
            }
            
            
            if ($isPasswordValid && !$module->checkActiveSessions() && !$module->getIsValid() && $this->isGranted('ROLE_PUBLICATION_MODULE')) {
                $versioningModule = new VersioningModule();
                $versioningModule->setActor($this->getUser());
                $versioningModule->setModule($module);
                $module->setIsValid(true);
                $action = 'enable';
                $versioningModule->setModuleVersion($module->getVersion());
                $versioningModule->setAction('Mise en ligne');
                $versioningModule->setHasRequiredRole($this->isGranted('ROLE_PUBLICATION_MODULE'));
                $em->persist($module);
                $em->persist($versioningModule);
                $em->flush();
                $auditManager->generateAudit($old_entity, $module, $action, $this->getUser());

                return new JsonResponse([
                    'flash' => $sfSession->getFlashBag()->add('success', $translator->trans('versioning.goOnline.success')),
                    'success' => 1]);
            }
        }
        //$sfSession->getFlashBag()->add('eror', $translator->trans('formation.module.action.cantPutonline'));
        return new JsonResponse([
            'flash' => $sfSession->getFlashBag()->add('error', $translator->trans('versioning.goOnline.error')),
            'success' => 0]);
    }

    /**
     * archived module
     *
     * @Route("/archived/{slug}", name="admin_module_archived", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_MODULES')")
     */
    public function archived(Request $request, $slug, AuditManager $auditManager)
    {
        $em = $this->getDoctrine()->getManager();

        $module = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBy([
            'slug' => $slug
        ]);
        $old_entity = clone $module;

        if (!$module->checkActiveSessions()) {
            if ($module->getIsArchived() == false) {
                $module->setIsArchived(true);
                $module->setIsValid(false);
                $action = 'archived';
            } else {
                $module->setIsArchived(false);
                $action = 'unarchived';
            }

            $em->persist($module);
            $em->flush();

            $auditManager->generateAudit($old_entity, $module, $action, $this->getUser());
        }

        return $this->redirect($this->generateUrl('admin_module_edit', [
            'slug' => $module->getSlug(),
        ]));
    }

    /**
     * Show all modules link with the formationPath & allow to order them
     *
     * @Route("/order/{module}/{key}/{action}", name="admin_module_course_order", methods={"POST"})
     *
     * @Security("is_granted('ROLE_GESTION_MODULES')")
     */
    public function order(Request $request, Module $module, int $key, string $action, AuditManager $auditManager)
    {
        try {
            $em = $this->getDoctrine()->getManager();

            $module_courses = $em->getRepository('App\Entity\FormationManagement\ModuleCourse')->findBy(['module' => $module], ['sort' => 'ASC']);

            if ($action === 'down') {
                $course_key = $key + 1;
            } else {
                $course_key = $key - 1;
            }

            $i = 1;
            foreach ($module_courses as $mc) {
                if ($i == $course_key) {
                    $module_course_remplace = $mc;
                    $oldEntityA = clone $module_course_remplace;
                    $sort_remplace = $mc->getSort();
                }
                if ($i == $key) {
                    $module_course = $mc;
                    $oldEntityB = clone $module_course;
                    $sort = $mc->getSort();
                }
                $i++;
            }

            $module_course->setSort($sort_remplace);
            $module_course_remplace->setSort($sort);

            $em->persist($module_course);
            $em->persist($module_course_remplace);
            $em->flush();

            $auditManager->generateAudit($oldEntityA, $module_course_remplace, 'order', $this->getUser());
            $auditManager->generateAudit($oldEntityB, $module_course_remplace, 'order', $this->getUser());

            return new JsonResponse(['message' => 'Les ordres sont mise à jour', 'success' => 1], 200);
        } catch (\Doctrine\DBAL\DBALException $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
                'success' => 0], 200);
        } catch (\Exception $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
                'success' => 0], 200);
        }

        return new JsonResponse([
            'message' => 'admin_module_course_order',
            'success' => 1
        ]);
    }

    /**
     * SEARCH FORMATION PATHS
     *
     * @Route("/search/{keywords}", name="admin_module_search", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_MODULES')")
     */
    public function search(Request $request, string $keywords)
    {
        $keywords = trim(urldecode($keywords));

        $em = $this->getDoctrine()->getManager();

        $modules = $em->getRepository('App\Entity\FormationManagement\Module')->findBySearchQuery($keywords);

        return $this->render('FormationManagement/Module/search.html.twig', [
            'modules' => $modules,
            'keywords' => $keywords
        ]);
    }

    /**
     * EDIT FORMATIONPATH MODULE DATE
     *
     * @Route("/dates/{id}", name="admin_module_edit_date", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_MODULES')")
     */
    public function date(Request $request, string $id)
    {
        $em = $this->getDoctrine()->getManager();

        $formationPathModule = $em->getRepository('App\Entity\FormationManagement\FormationPathModule')->find($id);

        if ($formationPathModule) {
            $form = $this->createForm('App\Form\FormationManagement\FormationPathModuleDateType', $formationPathModule);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em->persist($formationPathModule);
                $em->flush();

                return $this->redirect($this->generateUrl('formation_path_view', [
                    'slug' => $formationPathModule->getFormationPath()->getSlug()
                ]));
            }

            return $this->render('FormationManagement/Module/dates.html.twig', [
                'formationPathModule' => $formationPathModule,
                'form' => $form->createView()
            ]);
        } else {
            return $this->redirect($this->generateUrl('formation_path_list'));
        }
    }

    /**
     * show scorm
     *
     * @Route("/scorm/{slugModule}", name="admin_module_scorm_view", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_MODULES') or is_granted('ROLE_PUBLICATION_MODULE')")
     */
    public function scorm($slugModule, Request $request, ModuleRepository $moduleRepository)
    {
        $module = $moduleRepository->findOneBy(['slug' => $slugModule]);

        $sco = $module->getScorm()->getScos();

        return $this->render('FormationManagement/Module/scorm.html.twig', [
            'currentModule' => $module,
            'scorm' => $module->getScorm(),
            'scoEntryUrl' => dirname($module->getScormPath()) . '/' . $sco[0]->getEntryUrl(),
            'sco' => $sco[0]
        ]);
    }
}
