<?php

namespace App\Controller\TestManagement;

use App\Entity\TestManagement\Pool;
use App\Entity\TestManagement\Question;
use App\Entity\TestManagement\Test;
use App\Manager\AuditManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/admin/TestManagement/question")
 */
class QuestionController extends AbstractController
{
    /**
     * Affiche le formulaire d'ajout d'une question à un Test
     *
     * @Route("/addquestion/{test}/{pool}", name="admin_testManagement_question_create", methods={"GET","POST"})
     *
     * @Security("has_role('ROLE_GESTION_TEST')")
     */
    public function create(Request $request, Test $test, Pool $pool, TranslatorInterface $translator, AuditManager $auditManager, SessionInterface $sfSession)
    {
        $em = $this->getDoctrine()->getManager();

        $question = new Question();
        $question->setPool($pool);

        $module = $test->getModuleTests()[0]->getModule();
        $score = $test->getModuleTests()[0]->getScore();

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

        $options = ['test' => $test, 'moduletest' => $test->getModuleTests()[0], 'question' => null, 'pool' => $pool];

        $form = $this->createForm("App\Form\TestManagement\QuestionType", $question, $options);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $question->setCreateUser($this->getUser());
                $question->setUpdateUser($this->getUser());
                $question->setTest($test);
                $em->persist($question);

                // foreach($question->getKnowledges() as $knowledge){
                //     $knowledge = $em->getRepository('App\Entity\FormationManagement\Knowledge')->find($knowledge->getId());
                //     $knowledge->addQuestion($question);
                //     $em->persist($knowledge);
                // }
                $test->addQuestion($question);
                $em->persist($test);
                $em->flush();

                try {
                    $em->flush();

                    $auditManager->generateAudit(null, $question, 'add', $this->getUser());
                    $sfSession->getFlashBag()->add('success', $translator->trans('created'));
                } catch (\Doctrine\DBAL\DBALException $exception) {
                    $sfSession->getFlashBag()->add('error', $exception->getMessage());
                } catch (\Exception $exception) {
                    $sfSession->getFlashBag()->add('error', $exception->getMessage());
                }

                return $this->redirect($this->generateUrl('admin_testManagement_question_edit', [
                    'question' => $question->getId(),
                ]));
            }
        }

        return $this->render('TestManagement/Question/create.html.twig', [
            'question' => $question,
            'form' => $form->createView(),
            'test' => $test,
            'module' => $module,
            'typeTests' => $em->getRepository('App\Entity\LovManagement\TypeTest')->findAll(),
            'conditional' => $test->getTypeTest()->getConditional(),
            'test_pool' => $test_pool,
            'scoreMaxMin' => $scoreMaxMin,
            'score' => $score,
            'totalQuestion' => $totalQuestion,
            'totalQuestionRequired' => $totalQuestionRequired,
            'pool' => $pool,
        ]);
    }

    /**
     * Affiche le formulaire modification d'une Test (List Of Values) du projet.
     *
     * @Route("/edit/{question}", name="admin_testManagement_question_edit", methods={"GET","POST"})
     *
     * @Security("has_role('ROLE_GESTION_TEST') or has_role('ROLE_RESPONSABLE_FORMATION')")
     */
    public function edit(Request $request, Question $question, TranslatorInterface $translator, AuditManager $auditManager, SessionInterface $sfSession)
    {
        $em = $this->getDoctrine()->getManager();

        $nbQuestionUsed = $em->getRepository('App\Entity\TestManagement\Question')->checkQuestionStat($question, null, true);

        $oldEntity = clone $question;
        $test = $question->getTest();
        $pool = $question->getPool();

        $module = $test->getModuleTests()[0]->getModule();
        $score = $test->getModuleTests()[0]->getScore();

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

        $options = ['test' => $test, 'moduletest' => $test->getModuleTests()[0], 'question' => $question, 'pool' => $pool];

        $form = $this->createForm("App\Form\TestManagement\QuestionType", $question, $options);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                // // DELETE KNOWLEDGES FROM QUESTION
                // $oldKnowledges = $em->getRepository('App\Entity\FormationManagement\Knowledge')->findByQuestionId($question->getId());

                // foreach ($oldKnowledges as $knowledge) {
                //     if (!$knowledge->getQuestions()->contains($question)) {
                //         $knowledge->removeQuestion($question);
                //         $em->persist($knowledge);
                //     }
                // }

                // $knowledges = $question->getKnowledges();
                // //ADD KNOWLEDGES
                // foreach ($knowledges as $knowledge) {
                //     $knowledge = $em->getRepository('App\Entity\FormationManagement\Knowledge')->find($knowledge->getId());
                //     $knowledge->removeQuestion($question);
                //     $knowledge->addQuestion($question);
                //     $em->persist($knowledge);
                // }

                $question->setUpdateUser($this->getUser());
                $em->persist($question);
                $em->flush();

                $auditManager->generateAudit($oldEntity, $question, 'edit', $this->getUser());
                $sfSession->getFlashBag()->add('success', $translator->trans('updated'));

                return $this->redirect($this->generateUrl('admin_testManagement_question_edit', [
                    'question' => $question->getId(),
                ]));
            }
        }

        $statRight = $em->getRepository('App\Entity\TestManagement\Question')->checkQuestionStat($question, 'oui');
        $statAll = $em->getRepository('App\Entity\TestManagement\Question')->checkQuestionStat($question, null);

        return $this->render('TestManagement/Question/edit.html.twig', [
            'question' => $question,
            'form' => $form->createView(),
            'test' => $test,
            'module' => $module,
            'typeTests' => $em->getRepository('App\Entity\LovManagement\TypeTest')->findAll(),
            'conditional' => $test->getTypeTest()->getConditional(),
            'test_pool' => $test_pool,
            'statRight' => $statRight,
            'statAll' => $statAll,
            'scoreMaxMin' => $scoreMaxMin,
            'score' => $score,
            'nbQuestionUsed' => $nbQuestionUsed,
            'totalQuestion' => $totalQuestion,
            'totalQuestionRequired' => $totalQuestionRequired,
        ]);
    }

    /**
     * Supprimer une réponse
     *
     * @Route("/delete/{slug}", name="admin_testManagement_question_delete", methods={"GET","POST"})
     *
     * @Security("has_role('ROLE_GESTION_TEST')")
     */
    public function delete(string $slug, TranslatorInterface $translator, AuditManager $auditManager, SessionInterface $sfSession)
    {
        $em = $this->getDoctrine()->getManager();

        $question = $em->getRepository("App\Entity\TestManagement\Question")->findOneBy(['slug' => $slug]);

        $old_question = clone $question;

        $test = $question->getTest();

        if ($question) {
            $question->setIsValid(false);
            $question->setIsDeleted(true);
            foreach ($question->getAnswers() as $answer) {
                $old_answer = clone $answer;
                $answer->setIsValid(false);
                $answer->setIsDeleted(true);
                $em->persist($answer);
                $em->flush();

                $auditManager->generateAudit($old_answer, $answer, 'deleted', $this->getUser());
            }
            $em->persist($question);
            $em->flush();

            $auditManager->generateAudit($old_question, $question, 'deleted', $this->getUser());

            $sfSession->getFlashBag()->add('success', $translator->trans('deleted'));
        }

        return $this->redirect($this->generateUrl('admin_testManagement_edit', [
            'test' => $test->getId(),
        ]));
    }

    /**
     * Desactivate question with ajax
     *
     * @Route("/question/desactivate/{id}", name="question_desactivate", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_TEST')")
     */
    public function desactivate(Request $request, $id, AuditManager $auditManager, SessionInterface $session)
    {
        $em = $this->getDoctrine()->getManager();

        $question = $em->getRepository('App\Entity\TestManagement\Question')->findOneBy([
            'id' => $id
        ]);

        $old_question = clone $question;

        if ($question->getIsValid()) {
            $question->setIsValid(false);
            $action = 'disable';
        } else {
            $question->setIsValid(true);
            $action = 'enable';
        }

        $em->persist($question);
        $em->flush();

        $auditManager->generateAudit($old_question, $question, $action, $this->getUser());

        return $this->redirect($this->generateUrl('admin_pool_edit', [
            'pool' => $question->getPool()->getId(),
        ]));
    }
}
