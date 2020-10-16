<?php

namespace App\Controller\TestManagement;

use App\Entity\TestManagement\Answer;
use App\Entity\TestManagement\Question;
use App\Entity\TestManagement\Test;
use App\Manager\AuditManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/admin/TestManagement/answer")
 */
class AnswerController extends AbstractController
{
    /**
     * Affiche le formulaire d'ajout d'une réponse à une question
     *
     * @Route("/create/{question}", name="admin_testManagement_answer_create", methods={"GET","POST"})
     *
     * @Security("has_role('ROLE_GESTION_TEST')")
     */
    public function create(Request $request, Question $question, TranslatorInterface $translator, AuditManager $auditManager, SessionInterface $sfSession)
    {
        $em = $this->getDoctrine()->getManager();

        $nbQuestionUsed = $em->getRepository('App\Entity\TestManagement\Question')->checkQuestionStat($question, null, true);

        $test = $question->getTest();

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

        $answer = new Answer();

        $form = $this->createForm("App\Form\TestManagement\AnswerType", $answer);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                $answer->setCreateUser($this->getUser());
                $answer->setUpdateUser($this->getUser());
                $answer->setQuestion($question);
                $em->persist($answer);

                $question->addAnswer($answer);
                $em->persist($question);

                $em->flush();

                $auditManager->generateAudit(null, $answer, 'add', $this->getUser());

                $sfSession->getFlashBag()->add('success', $translator->trans('created'));

                return $this->redirect($this->generateUrl('admin_testManagement_question_edit', [
                        'question' => $question->getId(),
                    ]));
            }
        }

        return $this->render('TestManagement/Answer/create.html.twig', [
                'question' => $question,
                'answer' => $answer,
                'form' => $form->createView(),
                'test' => $test,
                'module' => $module,
                'typeTests' => $em->getRepository('App\Entity\LovManagement\TypeTest')->findAll(),
                'conditional' => $test->getTypeTest()->getConditional(),
                'test_pool' => $test_pool,
                'score' => $score,
                'scoreMaxMin' => $scoreMaxMin,
                'nbQuestionUsed' => $nbQuestionUsed,
                'totalQuestion' => $totalQuestion,
                'totalQuestionRequired' => $totalQuestionRequired,
        ]);
    }

    /**
     * Supprimer une réponse
     *
     * @Route("/delete/{slug}", name="admin_testManagement_answer_delete", methods={"GET","POST"})
     *
     * @Security("has_role('ROLE_GESTION_TEST')")
     */
    public function delete(string $slug, TranslatorInterface $translator, AuditManager $auditManager, SessionInterface $sfSession)
    {
        $em = $this->getDoctrine()->getManager();

        $answer = $em->getRepository("App\Entity\TestManagement\Answer")->findOneBy(['slug' => $slug]);

        $old_answwer = clone $answer;

        $question = $answer->getQuestion();

        if ($answer) {
            $answer->setIsValid(false);
            $answer->setIsDeleted(true);

            $em->persist($answer);
            $em->flush();

            $auditManager->generateAudit($old_answwer, $answer, 'delete', $this->getUser());

            $sfSession->getFlashBag()->add('success', $translator->trans('deleted'));
        }

        return $this->redirect($this->generateUrl('admin_testManagement_question_edit', [
                        'question' => $question->getId(),
                    ]));
    }

    /**
     * Show form to edit saved formationPath
     *
     * @Route("/edit_flash/{id}", name="admin_testManagement_answer_edit_flash", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_TEST')")
     */
    public function editFlash(Request $request, $id, AuditManager $auditManager, SessionInterface $sfSession)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('App\Entity\TestManagement\Answer')->findOneBy([
             'id' => $id,
        ]);

        $oldEntity = clone $entity;

        $output = [];

        if ($entity) {
            $output['id'] = $request->get('pk');
            $output['name'] = $request->get('name');
            $output['value'] = $request->get('value');

            switch ($request->get('name')) {
            case 'content':
                $entity->setContent($request->get('value'));
                break;
            case 'status':
                $entity->setStatus($request->get('value'));
                break;
            default:
                break;
        }
            $em->persist($entity);
            $em->flush($entity);

            $auditManager->generateAudit($oldEntity, $entity, 'edit', $this->getUser());
        }

        return new JsonResponse($output, 200);
    }


    /**
     * Desactivate answer with ajax
     *
     * @Route("/answer/desactivate/{id}", name="reponse_desactivate", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_TEST')")
     */
    public function desactivate(Request $request, $id, AuditManager $auditManager, SessionInterface $session)
    {
        $em = $this->getDoctrine()->getManager();

        $answer = $em->getRepository('App\Entity\TestManagement\Answer')->findOneBy([
            'id' => $id
        ]);

        $old_answer = clone $answer;

        if ($answer->getIsValid()) {
            $answer->setIsValid(false);
            $action = 'disable';
        } else {
            $answer->setIsValid(true);
            $action = 'enable';
        }

        $em->persist($answer);
        $em->flush();

        $auditManager->generateAudit($old_answer, $answer, $action, $this->getUser());

        return $this->redirect($this->generateUrl('admin_testManagement_question_edit', [
            'question' => $answer->getQuestion()->getId(),
        ]));
    }
}
