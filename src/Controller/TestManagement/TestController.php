<?php

namespace App\Controller\TestManagement;

use App\Entity\FormationManagement\ModuleTest;
use App\Entity\LovManagement\TypeTest;
use App\Entity\TestManagement\Test;
use App\Manager\AuditManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\{ChoiceType};
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/admin/TestManagement/test")
 */
class TestController extends AbstractController
{
    /**
     * Affiche la liste dune Test (List Of Values) du projet.
     *
     * @Route("/list", name="admin_testManagement_list", defaults={"page": "1"}, methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_TEST') or is_granted('ROLE_RESPONSABLE_FORMATION')")
     */
    public function list(Request $request, int $page)
    {
        $em = $this->getDoctrine()->getManager();

        $tests = $em->getRepository("App\Entity\TestManagement\Test")->findAll();
        $tests_list = [];
        foreach ($tests as $test) {
            if ($test->getIsTestCommune() && $test->getIsTestCommune() != null) {
                $pools = $test->getTestCommune()->getPools();
            } else {
                $pools = $test->getPools();
            }

            $alert = false;
            $totalQuestion = 0;
            $totalQuestionRequired = 0;
            $activModule = false;
            foreach ($pools as $p) {
                if ($p->getIsValid() == true) {
                    $totalQuestionRequired += $p->getNbRequQuestions();
                }
                foreach ($p->getQuestions() as $q) {
                    if ($q->getIsValid() == true) {
                        $totalQuestion++;
                    }
                    $statRight = $em->getRepository('App\Entity\TestManagement\Question')->checkQuestionStat($q, 'oui');
                    $statAll = $em->getRepository('App\Entity\TestManagement\Question')->checkQuestionStat($q, null);
                    if ($statRight != 0) {
                        $moyenneSuccess = $statRight / $statAll * 100;
                    } else {
                        $moyenneSuccess = 0;
                    }
                    if ($statAll >= 10 && round($moyenneSuccess) <= 50) {
                        $alert = true;
                    }
                }
                foreach ($test->getModuleTests() as $moduleTest) {
                    if ($moduleTest->getModule()->getIsValid() == true) {
                        $activModule = true;
                    }
                }
            }
            $tests_list[$test->getId()]['test'] = $test;
            $tests_list[$test->getId()]['alert_success'] = $alert;
            $tests_list[$test->getId()]['activModule'] = $activModule;
            $tests_list[$test->getId()]['totalQuestion'] = $totalQuestion;
            $tests_list[$test->getId()]['totalQuestionRequired'] = $totalQuestionRequired;
        }

        return $this->render('TestManagement/Test/list.html.twig', [
            'tests_list' => $tests_list,
            'total' => count($tests)
        ]);
    }

    /**
     * Affiche le formulaire modification d'une Test (List Of Values) du projet.
     *
     * @Route("/edit/{test}", name="admin_testManagement_edit", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_TEST') or is_granted('ROLE_RESPONSABLE_FORMATION')")
     */
    public function edit(Request $request, Test $test, TranslatorInterface $translator, AuditManager $auditManager, SessionInterface $sfSession)
    {
        $em = $this->getDoctrine()->getManager();

        if (!$test instanceof Test) {
            throw new NotFoundHttpException('Test Not Found Exception');
        }
        $old_test = clone $test;
        // get module
        foreach ($test->getModuleTests() as $moduleTest) {
            $module = $moduleTest->getModule();
            break;
        }

        $typeTest = $test->getTypeTest();

        $moduleTest = $em->getRepository("App\Entity\FormationManagement\ModuleTest")->findOneBy(['test' => $test]);
        $moduleTest->setTest($test);

        $old_module_test = clone $moduleTest;

        //$form = $this->createForm("App\Form\TestManagement\TestType", $test);

        $formModuleTest = $this->createForm('App\Form\FormationManagement\ModuleTestType', $moduleTest);

        $test_pool = [];
        if ($test->getIsTestCommune() && $test->getIsTestCommune() != null) {
            $pools = $test->getTestCommune()->getPools();
        } else {
            $pools = $test->getPools();
        }
        $totalQuestion = $totalQuestionRequired = 0;
        foreach ($pools as $p) {
            if ($p->getIsValid() == true) {
                $totalQuestionRequired += $p->getNbRequQuestions();
            }
            $questionPoolNb = 0;
            $test_pool[$p->getId()]['pool'] = $p;
            $arrayQuestions = [];
            foreach ($p->getQuestions() as $q) {
                if ($q->getIsDeleted() == false) {
                    if ($q->getIsValid() == true) {
                        $totalQuestion++;
                        $questionPoolNb++;
                    }
                    $statRight = $em->getRepository('App\Entity\TestManagement\Question')->checkQuestionStat($q, 'oui');
                    $statAll = $em->getRepository('App\Entity\TestManagement\Question')->checkQuestionStat($q, null);
                    $arrayQuestions[$q->getId()]['question'] = $q;
                    $arrayQuestions[$q->getId()]['statRight'] = $statRight;
                    $arrayQuestions[$q->getId()]['statAll'] = $statAll;
                }
            }
            $test_pool[$p->getId()]['questions'] = $arrayQuestions;
            $test_pool[$p->getId()]['nbQuestion'] = $questionPoolNb;
        }

        $scoreMaxMin = $em->getRepository('App\Entity\TestManagement\Question')->sumScore($test, $totalQuestionRequired);
        if ($request->isMethod('POST')) {
            //$form->handleRequest($request);
            $formModuleTest->handleRequest($request);

            if ($formModuleTest->isSubmitted() && $formModuleTest->isValid()) {
                $test->setTitle($moduleTest->getTitle());
                $test->setIsTestCommune($moduleTest->getIsTestCommune());
                $test->setIsTestPresentiel($moduleTest->getIsTestPresentiel());
                $test->setTheoricalDuration($moduleTest->getTheoricalDuration());
                $test->setTypeTest($typeTest); //obligatoire, car le champ typeTest est disabled
                $test->setUpdateUser($this->getUser());

                // Pré-test question commun
                if ($test->getTypeTest()->getConditional() == 'eval' && $test->getIsTestCommune()) {
                    foreach ($module->getModuleTests() as $mt) {
                        if (strpos($mt->getTest()->getTypeTest()->getConditional(), 'pretest') !== false) {
                            $test->setTestCommune($mt->getTest());
                            break;
                        }
                    }
                }

                $em->persist($test);
                $moduleTest->setModule($module); //obligatoire, car le champ module est disabled
                $em->persist($moduleTest);

                $em->flush();

                $auditManager->generateAudit($old_test, $test, 'edit', $this->getUser());
                $auditManager->generateAudit($old_module_test, $moduleTest, 'edit', $this->getUser());

                $sfSession->getFlashBag()->add('success', $translator->trans('Le test est mise à jour'));

                // if test presentiel
                if ($test->getIsTestPresentiel() or $test->getIsTestCommune() == true) {
                    return $this->redirect($this->generateUrl('admin_testManagement_edit', [
                        'test' => $test->getId(),
                    ]));
                } else {
                    $pool = $em->getRepository("App\Entity\TestManagement\Pool")->findFirstPoolByTest($test->getId());
                    if ($pool) {
                        return $this->redirect($this->generateUrl('admin_pool_edit', [
                            'pool' => $pool->getId(),
                        ]));
                    } else {
                        return $this->redirect($this->generateUrl('admin_pool_create', [
                            'test' => $test->getId(),
                        ]));
                    }
                }
            }
        }

        return $this->render('TestManagement/Test/edit.html.twig', [
            'test' => $test,
            'module' => $module,
            'typeTests' => $em->getRepository('App\Entity\LovManagement\TypeTest')->findAll(),
            'moduleTest' => $moduleTest,
            //'form' => $form->createView(),
            'formModuleTest' => $formModuleTest->createView(),
            'conditional' => $test->getTypeTest()->getConditional(),
            'test_pool' => $test_pool,
            'scoreMaxMin' => $scoreMaxMin,
            'score' => $moduleTest->getScore(),
            'totalQuestion' => $totalQuestion,
            'totalQuestionRequired' => $totalQuestionRequired,
        ]);
    }

    /**
     * Supprimer une réponse
     *
     * @Route("/delete/{id}", name="admin_testManagement_delete", methods={"GET","POST"})
     *
     * @Security("has_role('ROLE_GESTION_TEST')")
     */
    public function delete(int $id, TranslatorInterface $translator, AuditManager $auditManager)
    {
        $em = $this->getDoctrine()->getManager();

        $test = $em->getRepository("App\Entity\TestManagement\Test")->findOneBy(['id' => $id]);
        $oldEntity = clone $test;
        $moduleTest = $em->getRepository("App\Entity\FormationManagement\ModuleTest")->findOneBy(['test' => $test]);

        if ($test) {
            //$moduleTest->removeTest($test);
            $em->remove($test);
            $em->flush();
            $auditManager->generateAudit($oldEntity, $question, 'deleted', $this->getUser());

            $this->get('session')->getFlashBag()->add('success', $translator->trans('deleted'));
        }

        return $this->redirect($this->generateUrl('admin_testManagement_list', []));
    }
}
