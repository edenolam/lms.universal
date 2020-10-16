<?php

namespace App\Controller\UserFrontManagement;

use App\Entity\TestManagement\Test;
use App\Entity\UserFrontManagement\UserTest;
use App\Service\MailService;
use App\Event\UserFrontManagement\UserTestEvent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Html2Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/user/test")
 */
class UserTestResultController extends Controller
{
    /**
     * RESTULT OF TEST / IF EVAL => DONT SHOW CORRECTION
     *
     * @Route("/result/{sessionSlug}/{moduleSlug}/{testSlug}/{userTestID}/{userFrom}", name="test_front_result", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function result(Request $request, $sessionSlug, $moduleSlug, $testSlug, $userTestID, $userFrom = null,MailService $mailer)
    {
        $em = $this->getDoctrine()->getManager();

        $userTest = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findOneBy(['id' => $userTestID]);

        if ($userFrom == null) {
            $user = $this->getUser();
        } else {
            $user = $userTest->getUser();
        }

        $test = $em->getRepository('App\Entity\TestManagement\Test')->findOneBy(['slug' => $testSlug]);
        $currentSession = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy(['slug' => $sessionSlug]);
        $module = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBy(['slug' => $moduleSlug]);
        $moduleTest = $em->getRepository('App\Entity\FormationManagement\ModuleTest')->findByModuleANDTestId($module->getId(), $test->getId());
        $moduleTest = $moduleTest[0];

        $oldUserTest = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findBy(['user' => $user, 'session' => $currentSession, 'refModule' => $module->getReference(), 'test' => $test]);
        $userModule = $em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')->findOneBy(['user' => $user, 'module' => $module]);
        $answeredQuestions = $em->getRepository('App\Entity\UserFrontManagement\UserQuestion')->findBy(['userTest' => $userTest]);

        $correctionArray = [];
        $knowledgeArray = [];
        $score = 0;

        foreach ($answeredQuestions as $answeredQuestion) {
            //UPDATE TEST SCORE
            $question = [];
            //PREPARE ARRAY
            $question['questionTitle'] = $answeredQuestion->getQuestionDetails()['title'];
            $question['question'] = $answeredQuestion->getVerbalQuestion();
            $question['comment'] = $answeredQuestion->getQuestionDetails()['comment'];
            $question['weight'] = $answeredQuestion->getQuestionDetails()['weight'];
            $question['scored'] = $answeredQuestion->getScored();

            $allPagesModule = $em->getRepository('App\Entity\FormationManagement\Page')->findPageModule($module);

            if (!$answeredQuestion->getScored()) {
                foreach ($answeredQuestion->getQuestion()->getKnowledges() as $knowledge) {
                    if (!array_key_exists($knowledge->getTitle(), $knowledgeArray)) {
                        $pages = [];
                        foreach ($knowledge->getPages() as $page) {
                            if (in_array($page, $allPagesModule)) {
                                $pages[$page->getSlug()] = ['page' => $page->getTitle(), 'course' => $page->getCourse()->getSlug()];
                            }
                        }
                        $knowledgeArray[$knowledge->getTitle()] = $pages;
                    }
                }
            }
            $question['answers'] = [];
            foreach ($answeredQuestion->getVerbalAnswers() as $answer) {
                // IF ONE ANSWER -> TYPE IS INTERGER
                if (!is_array($answeredQuestion->getUserAnswers())) {
                    if ($answer['id'] == $answeredQuestion->getUserAnswers()) {
                        $answerArray = [
                            'title' => $answer['content'],
                            'status' => $answer['status'],
                            'voted' => true
                        ];
                    } else {
                        $answerArray = [
                            'title' => $answer['content'],
                            'status' => $answer['status'],
                            'voted' => false
                        ];
                    }
                    //IF MORE THAN ONE ANSWER -> TYPE IS ARRAY
                } elseif (is_array($answeredQuestion->getUserAnswers())) {
                    if (in_array($answer['id'], $answeredQuestion->getUserAnswers())) {
                        $answerArray = [
                            'title' => $answer['content'],
                            'status' => $answer['status'],
                            'voted' => true
                        ];
                    } else {
                        $answerArray = [
                            'title' => $answer['content'],
                            'status' => $answer['status'],
                            'voted' => false
                        ];
                    }
                }
                array_push($question['answers'], $answerArray);
            }
            array_push($correctionArray, $question);
        }

        $arrayValisationModes = [];
        foreach ($module->getValidationModes() as $validationMode) {
            array_push($arrayValisationModes, $validationMode->getConditional());
        }

        $userLastTest = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findByTestLastUser($user->getId(), $test->getId(), $currentSession);

        $nbtentetiveDown = 0;
        if ($userLastTest) {
            if ($userLastTest->getLastIndexQuestion() == -1) {
                $nbtentetiveDown = $userLastTest->getTentative();
            } else {
                $nbtentetiveDoshuffleQuestionswn = $userLastTest->getTentative() - 1;
            }
        }
        $userLastPage = $em->getRepository('App\Entity\UserFrontManagement\UserPageFollow')->findLastUserPage($user);

        if ($userFrom == null ) {
            // dispatcher UserTestEvent
            $event = new UserTestEvent($test, $userTest, $module, $currentSession, $user);
            $dispatcher = $this->get('event_dispatcher');
            $dispatcher->dispatch(UserTestEvent::NAME, $event);
            if($test->getTypeTest()->getConditional() == "eval"){
                $this->sendMailEchecAction($test, $currentSession, $userTest, $user, $module, $mailer);
            }
        }

        //echo "<pre>"; var_dump($userTest->getDetail()); exit();
        //$nbQuestionTotal = $em->getRepository('App\Entity\TestManagement\Test')->getQuestionTirer($test->getId());

        return $this->render('UserFrontManagement/TestFront/result.html.twig', [
            'userTest' => $userTest,
            'nbtentetiveDown' => $nbtentetiveDown,
            'validationModes' => $arrayValisationModes,
            'userLastPage' => $userLastPage,
            'userModule' => $userModule,
            'moduleTest' => $moduleTest,
            'formationPath' => $currentSession->getFormationPath(),
            'currentSession' => $currentSession,
            'module' => $module,
            'knowledgeArray' => $knowledgeArray,
            'correctionArray' => $correctionArray,
            'userFrom' => $userFrom,
            'oldUserTest' => $oldUserTest,
            'nbQuestionTotal' => $test->getTotalRequiredQuestion(),
        ]);
    }

    /**
     * Send echec to respo formation and student's tutors
     */
    public function sendMailEchecAction($test, $currentSession, $userTest, $user, $module,MailService $mailer)
    {
        $em = $this->getDoctrine()->getManager();
        if (($userTest->getNumberTry() - $userTest->getTentative()) == 0) {
            $seuil = true;
            $allUserTest = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findBy(['session' => $currentSession, 'test' => $test, 'user' => $user]);
            foreach ($allUserTest as $utest) {
                if ($utest->getScore() >= $utest->getDetail()['scorePercentage']) {
                    $seuil = false;
                }
            }
            if ($seuil) {
                $mails = [];
                $tutors = $user->getSupervisors();
                foreach ($tutors as $user){
                    if(!in_array($user->getEmail(), $mails)){
                        $mails[] = $user->getEmail();
                    }
                }

                $respos = $em->getRepository('App\Entity\UserManagement\User')->findAllByRoles('ROLE_RESPONSABLE_FORMATION');
                foreach ($respos as $user){
                    if(!in_array($user['email'], $mails)){
                        $mails[] = $user['email'];
                    }
                }
                $subject = 'INFO - Echec sur une évaluation';
                $from = ['info@universalmedica.com' => 'universalmedica'];
                //$cc = '';
                $body = $this->renderView('UserFrontManagement/emails/alerte_echec.html.twig', [
                        'userTest' => $userTest,
                        'module' => $module,
                        'formationPath' => $currentSession->getFormationPath(),
                    ]);

                $mailer->sendAMail($from, $mails, $subject, $body);

                /*$messages = (new \Swift_Message('INFO  - Echec sur une évaluation'))
                    ->setFrom(['info@universalmedica.com' => 'universalmedica'])
                    ->setTo($mails)
                    ->setBody(
                        $this->renderView('UserFrontManagement/emails/alerte_echec.html.twig', [
                            'userTest' => $userTest,
                            'module' => $module,
                            'formationPath' => $currentSession->getFormationPath(),
                        ]),
                        'text/html'
                    );
                $mailer->send($messages);
                */
            }
        }
        return ;
    }

    /**
     * Download  resultat student
     * @Route("/home/resultat/{moduleSlug}/{testSlug}/{userTestID}", name="download_resultat_copie", methods="GET")
     *
     * $Security("is_granted('ROLE_USER')")
     */
    public function generatePdfResultAction(Request $request, $moduleSlug, $testSlug, $userTestID)
    {
        $em = $this->getDoctrine()->getManager();

        $test = $em->getRepository('App\Entity\TestManagement\Test')->findOneBy(['slug' => $testSlug]);
        $module = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBy(['slug' => $moduleSlug]);

        $userTest = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findOneBy(['id' => $userTestID]);

        $moduleTest = $em->getRepository('App\Entity\FormationManagement\ModuleTest')->findByModuleANDTestId($module->getId(), $test->getId());
        $moduleTest = $moduleTest[0];

        $answeredQuestions = $em->getRepository('App\Entity\UserFrontManagement\UserQuestion')->findBy(['userTest' => $userTest]);

        $correctionArray = [];
        $knowledgeArray = [];
        $score = 0;

        foreach ($answeredQuestions as $answeredQuestion) {
            //UPDATE TEST SCORE
            if ($answeredQuestion->getScored()) {
                $score = $score + $answeredQuestion->getQuestionDetails()['weight'];
            }

            $question = [];
            //PREPARE ARRAY
            $question['questionTitle'] = $answeredQuestion->getQuestionDetails()['title'];
            $question['question'] = $answeredQuestion->getVerbalQuestion();
            $question['comment'] = $answeredQuestion->getQuestionDetails()['comment'];
            $question['weight'] = $answeredQuestion->getQuestionDetails()['weight'];
            $question['scored'] = $answeredQuestion->getScored();
            $question['knowledge'] = [];
            $allPagesModule = $em->getRepository('App\Entity\FormationManagement\Page')->findPageModule($module);

            if (!$answeredQuestion->getScored()) {
                foreach ($answeredQuestion->getQuestion()->getKnowledges() as $knowledge) {
                    if (!array_key_exists($knowledge->getTitle(), $knowledgeArray)) {
                        $pages = [];
                        foreach ($knowledge->getPages() as $page) {
                            if (in_array($page, $allPagesModule)) {
                                $pages[$page->getSlug()] = ['page' => $page->getTitle(), 'course' => $page->getCourse()->getSlug()];
                            }
                        }
                        $knowledgeArray[$knowledge->getTitle()] = $pages;
                    }
                }
            }

            $question['answers'] = [];
            foreach ($answeredQuestion->getVerbalAnswers() as $answer) {
                // IF ONE ANSWER -> TYPE IS INTERGER
                if (!is_array($answeredQuestion->getUserAnswers())) {
                    if ($answer['id'] == $answeredQuestion->getUserAnswers()) {
                        $answerArray = [
                            'title' => $answer['content'],
                            'status' => $answer['status'],
                            'voted' => true
                        ];
                    } else {
                        $answerArray = [
                            'title' => $answer['content'],
                            'status' => $answer['status'],
                            'voted' => false
                        ];
                    }
                    //IF MORE THAN ONE ANSWER -> TYPE IS ARRAY
                } elseif (is_array($answeredQuestion->getUserAnswers())) {
                    if (in_array($answer['id'], $answeredQuestion->getUserAnswers())) {
                        $answerArray = [
                            'title' => $answer['content'],
                            'status' => $answer['status'],
                            'voted' => true
                        ];
                    } else {
                        $answerArray = [
                            'title' => $answer['content'],
                            'status' => $answer['status'],
                            'voted' => false
                        ];
                    }
                }
                array_push($question['answers'], $answerArray);
            }
            array_push($correctionArray, $question);
        }

        if ($userTest) {
            try {
                $html2pdf = new HTML2PDF('P', 'A4', 'fr');
                $html2pdf->pdf->SetDisplayMode('fullpage');
                $html2pdf->pdf->SetProtection(['print', 'copy', 'annot-forms']);

                $chemin = $this->get('kernel')->getRootDir() . '/..';
                $content = $this->container->get('templating')->render('UserFrontManagement/TestFront/resultat_student.html.twig', [
                    'chemin' => $chemin,
                    'userTest' => $userTest,
                    'moduleTest' => $moduleTest,
                    'correctionArray' => $correctionArray,
                    'knowledgeArray' => $knowledgeArray,
                ]);

                $html2pdf->writeHTML($content);
                $html2pdf->output('Resultat_' . $module->getSlug() . '_' . date('Y_m_d_H_i_s') . '.pdf');
            } catch (Html2PdfException $e) {
                $html2pdf->clean();
                $formatter = new ExceptionFormatter($e);
                echo $formatter->getHtmlMessage();
            }
        }
    }
}
