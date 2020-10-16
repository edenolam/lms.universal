<?php

namespace App\Controller\TestManagement;

use App\Entity\TestManagement\Pool;
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
class PoolController extends AbstractController
{
    /**
     * Affiche le formulaire d'ajout d'un pool à un Test
     *
     * @Route("/addpool/{test}", name="admin_pool_create", methods={"GET","POST"})
     *
     * @Security("has_role('ROLE_GESTION_TEST')")
     */
    public function create(Request $request, Test $test, TranslatorInterface $translator, AuditManager $auditManager)
    {
        $em = $this->getDoctrine()->getManager();

        $pool = new Pool();
        $pool->setTest($test);

        //foreach ($test->getModuleTests() as $moduleTest) {
        $module = $test->getModuleTest()->getModule();
        $score = $test->getModuleTest()->getScore();
        //    break;
        //}

        $test_pool = [];
        if ($test->getIsTestCommune() && $test->getIsTestCommune() != null) {
            $pools = $test->getTestCommune()->getPools();
        } else {
            $pools = $test->getPools();
        }
        $totalQuestion = 0;
        $totalQuestionRequired = 0;
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

        $form = $this->createForm("App\Form\TestManagement\PoolType", $pool);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $pool->setTest($test);
                $pool->setCreateUser($this->getUser());
                $pool->setUpdateUser($this->getUser());

                $em->persist($pool);
                $em->flush();

                $auditManager->generateAudit(null, $pool, 'add', $this->getUser());
                $this->get('session')->getFlashBag()->add('success', $translator->trans('created'));

                return $this->redirect($this->generateUrl('admin_testManagement_question_create', [
                    'test' => $test->getId(),
                    'pool' => $pool->getId()
                ]));
            }
        }

        return $this->render('TestManagement/Pool/create.html.twig', [
            'pool' => $pool,
            'form' => $form->createView(),
            'test' => $test,
            'module' => $module,
            'test_pool' => $test_pool,
            'scoreMaxMin' => $scoreMaxMin,
            'score' => $score,
            'totalQuestion' => $totalQuestion,
            'typeTests' => $em->getRepository('App\Entity\LovManagement\TypeTest')->findAll(),
            'totalQuestionRequired' => $totalQuestionRequired,
            'conditional' => $test->getTypeTest()->getConditional(),
        ]);
    }

    /**
     * Affiche le formulaire modification d'un pool (List Of Values) du projet.
     *
     * @Route("/pool/edit/{pool}", name="admin_pool_edit", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_TEST') or is_granted('ROLE_RESPONSABLE_FORMATION')")
     */
    public function edit(Request $request, Pool $pool, TranslatorInterface $translator, AuditManager $auditManager, SessionInterface $sfSession)
    {
        $em = $this->getDoctrine()->getManager();

        $oldEntity = clone $pool;

        $test = $pool->getTest();

        $pool->setTest($test);
        //// pretest question commun
        $module = $test->getModuleTest()->getModule();
        $score = $test->getModuleTest()->getScore();

        $typeTest = $test->getTypeTest();

        $form = $this->createForm("App\Form\TestManagement\PoolType", $pool);

        $test_pool = [];
        if ($test->getIsTestCommune() && $test->getIsTestCommune() != null) {
            $pools = $test->getTestCommune()->getPools();
        } else {
            $pools = $test->getPools();
        }
        $totalQuestion = 0;
        $totalQuestionRequired = 0;
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
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $pool->setTest($test);
                $pool->setUpdateUser($this->getUser());

                $em->persist($pool);
                $em->flush();

                $auditManager->generateAudit($oldEntity, $pool, 'edit', $this->getUser());
                $sfSession->getFlashBag()->add('success', $translator->trans('updated'));

                return $this->redirect($this->generateUrl('admin_pool_edit', [
                    'pool' => $pool->getId()
                ]));
            }
        }

        return $this->render('TestManagement/Pool/edit.html.twig', [
            'pool' => $pool,
            'form' => $form->createView(),
            'test' => $test,
            'module' => $module,
            'test_pool' => $test_pool,
            'scoreMaxMin' => $scoreMaxMin,
            'score' => $score,
            'totalQuestion' => $totalQuestion,
            'typeTests' => $em->getRepository('App\Entity\LovManagement\TypeTest')->findAll(),
            'totalQuestionRequired' => $totalQuestionRequired,
            'conditional' => $test->getTypeTest()->getConditional(),
        ]);
    }

    /**
     * Desactivate page with ajax
     *
     * @Route("/pool/desactivate/{id}", name="pool_desactivate", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_TEST')")
     */
    public function desactivate(Request $request, $id, AuditManager $auditManager, SessionInterface $session)
    {
        $em = $this->getDoctrine()->getManager();

        $pool = $em->getRepository('App\Entity\TestManagement\Pool')->findOneBy([
            'id' => $id
        ]);

        $old_pool = clone $pool;

        if ($pool->getIsValid()) {
            $pool->setIsValid(false);
            $action = 'disable';
            foreach ($pool->getQuestions() as $question) {
                $old_question = clone $question;
                $question->setIsValid(false);
                $em->persist($question);
                $em->flush();
                $auditManager->generateAudit($old_question, $question, $action, $this->getUser());
            }
        } else {
            $pool->setIsValid(true);
            $action = 'enable';
        }

        $em->persist($pool);
        $em->flush();

        $auditManager->generateAudit($old_pool, $pool, $action, $this->getUser());

        return $this->redirect($this->generateUrl('admin_testManagement_edit', [
            'test' => $pool->getTest()->getId(),
        ]));
    }
}
