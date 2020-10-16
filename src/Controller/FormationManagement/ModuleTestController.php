<?php

namespace App\Controller\FormationManagement;

use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\ModuleTest;
use App\Entity\LovManagement\TypeTest;
use App\Entity\TestManagement\Test;
use App\Manager\AuditManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/admin/TestManagement/module")
 */
class ModuleTestController extends AbstractController
{
    /**
     * ADD A TEST TO SLUGED MODULE
     * @Route("/addTest/{slug}/{testConditional}", name="admin_module_add_test", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_TEST')")
     */
    public function add(Request $request, string $slug, string $testConditional, TranslatorInterface $translator, AuditManager $auditManager, SessionInterface $sfSession)
    {
        $em = $this->getDoctrine()->getManager();

        $module = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBy([
            'slug' => $slug
        ]);
        if (!$module instanceof Module) {
            throw new NotFoundHttpException('Module Not Found Exception');
        }
        $old_module = clone $module;

        $typeTest = $em->getRepository('App\Entity\LovManagement\TypeTest')->findOneBy([
            'conditional' => $testConditional
        ]);
        if (!$typeTest instanceof TypeTest) {
            throw new NotFoundHttpException('TypeTest Not Found Exception');
        }
        $moduleTest = new ModuleTest();
        $moduleTest->setModule($module);
        $moduleTest->setTypeTest($typeTest);
        $moduleTest->setNumberTry(3);

        $formModuleTest = $this->createForm('App\Form\FormationManagement\ModuleTestType', $moduleTest);

        if ($request->isMethod('POST')) {
            //$form->handleRequest($request);
            $formModuleTest->handleRequest($request);
            $test = new Test();
            $test->setTitle($moduleTest->getTitle());
            $test->setIsTestCommune($moduleTest->getIsTestCommune());
            $test->setIsTestPresentiel($moduleTest->getIsTestPresentiel());
            $test->setTheoricalDuration($moduleTest->getTheoricalDuration());
            $test->setTypeTest($typeTest); //obligatoire, car le champ typeTest est disabled
            $test->setCreateUser($this->getUser());
            $test->setUpdateUser($this->getUser());
            $test->setModule($module);

            $em->persist($test);
            $em->flush();

            $auditManager->generateAudit(null, $test, 'add', $this->getUser());

            // Pré-test question commun
            if ($test->getTypeTest()->getConditional() == 'eval' && $test->getIsTestCommune()) {
                foreach ($module->getModuleTests() as $mt) {
                    if (strpos($mt->getTest()->getTypeTest()->getConditional(), 'pretest') !== false) {
                        $test->setTestCommune($mt->getTest());
                        break;
                    }
                }
            }

            $moduleTest->setTest($test);
            $moduleTest->setModule($module); //obligatoire, car le champ module est disabled
            $em->persist($moduleTest);
            $em->flush();

            $auditManager->generateAudit($old_module, $module, 'edit', $this->getUser());

            $sfSession->getFlashBag()->add('success', $translator->trans('created'));

            // if test presentiel
            if ($test->getIsTestPresentiel() or $test->getIsTestCommune() == true) {
                return $this->redirect($this->generateUrl('admin_testManagement_edit', [
                  'test' => $test->getId(),
              ]));
            } else {
                return $this->redirect($this->generateUrl('admin_pool_create', [
                  'test' => $test->getId(),
              ]));
            }
        }

        return $this->render('FormationManagement/ModuleTest/add.html.twig', [
            'module' => $module,
            'typeTests' => $em->getRepository('App\Entity\LovManagement\TypeTest')->findAll(),
            //'form' => $form->createView(),
            'formModuleTest' => $formModuleTest->createView(),
            'conditional' => $testConditional,
        ]);
    }
}
