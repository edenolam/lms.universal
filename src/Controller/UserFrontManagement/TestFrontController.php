<?php

namespace App\Controller\UserFrontManagement;

use App\Entity\FormationManagement\Module;
use App\Entity\PlanningManagement\Session;
use App\Entity\TestManagement\Test;
use App\Entity\UserFrontManagement\UserQuestion;
use App\Entity\UserFrontManagement\UserTest;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @Route("/user/test")
 */
class TestFrontController extends AbstractController
{
    /**
     * SHOW TEST INFO
     *
     * @Route("/show/{sessionSlug}/{moduleSlug}/{testSlug}", name="test_front_begin", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function show(Request $request, $sessionSlug, $moduleSlug, $testSlug)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();

        $test = $em->getRepository('App\Entity\TestManagement\Test')->findOneBy([
            'slug' => $testSlug
        ]);

        $module = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBy([
            'slug' => $moduleSlug
        ]);

        //$nbQuestionTotal = $em->getRepository('App\Entity\TestManagement\Test')->getQuestionTirer($test->getId());

        $currentSession = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy(['slug' => $sessionSlug]);

        if (!$test instanceof Test || !$module instanceof Module || !$currentSession instanceof Session) {
            throw new NotFoundHttpException('Test Not Found Exception');
        }
        $userLastTest = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findByTestLastUser($user->getId(), $test->getId(), $currentSession);
        $nbtentetiveDown = 0;

        $moduleTest = $em->getRepository('App\Entity\FormationManagement\ModuleTest')->findByModuleANDTestId($module->getId(), $test->getId());
        $moduleTest = $moduleTest[0];

        if ($userLastTest) {
            if ($userLastTest->getLastIndexQuestion() == -1 && $userLastTest->getDatePass() == null) {
                $nbtentetiveDown = $userLastTest->getTentative();
            } else {
                $nbtentetiveDown = $userLastTest->getTentative() - 1; // ???
            }
            $numberTry = $userLastTest->getNumberTry();
        } else {
            $numberTry = $moduleTest->getNumberTry();
        }

        $result = [];
        foreach ($module->getValidationModes() as $validationMode) {
            array_push($result, $validationMode->getConditional());
        }

        // if userLastPage is null
        $userLastPage = $em->getRepository('App\Entity\UserFrontManagement\UserPageFollow')->findLastUserPage($user);

        return $this->render('UserFrontManagement/TestFront/begin.html.twig', [
            'nbtentetiveDown' => $nbtentetiveDown,
            'numberTry' => $numberTry,
            'validationModes' => $result,
            'moduleTest' => $moduleTest,
            'formationPath' => $currentSession->getFormationPath(),
            'currentSession' => $currentSession,
            'module' => $module,
            'userLastPage' => $userLastPage,
            'nbQuestionTotal' => $test->getTotalRequiredQuestion()
        ]);
    }

    /**
     * PREPARE USER TEST / IF ALREADY USER TEST -> OR WE GO QUESTION / OR WE CREATE NEW ONE AND INCREMENT TENTATIVE
     *
     * @Route("/prepare/{sessionSlug}/{moduleSlug}/{testSlug}", name="test_front_prepare", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function prepare(Request $request, $sessionSlug, $moduleSlug, $testSlug, SessionInterface $sfSession)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();

        $test = $em->getRepository('App\Entity\TestManagement\Test')->findOneBy([
            'slug' => $testSlug
        ]);

        $module = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBy([
            'slug' => $moduleSlug
        ]);

        $currentSession = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy(['slug' => $sessionSlug]);

        if (!$test instanceof Test || !$module instanceof Module || !$currentSession instanceof Session) {
            throw new NotFoundHttpException('Test Not Found Exception');
        }

        $moduleTest = $em->getRepository('App\Entity\FormationManagement\ModuleTest')->findByModuleANDTestId($module->getId(), $test->getId());

        $moduleTest = $moduleTest[0];

        if ($test) {
            $userTest = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findByTestAndUserIdAndModuleRef($user->getId(), $test->getId(), $module->getReference(), $currentSession->getId());

            $userTest = end($userTest);

            // ALREADY USER TEST
            if ($userTest) {
                $numberTry = ($userTest->getNumberTry() > $moduleTest->getNumberTry()) ? $userTest->getNumberTry() : $moduleTest->getNumberTry();
                // IF LAST QUESTION INDEX IS -1 TEST IS OVER
                if (($userTest->getDatePass() == null && $userTest->getLastIndexQuestion() == -1) || $userTest->getTest()->getTypeTest()->getConditional() == 'training') {
                    // IF TENTATIVE AND NOT TRAINING TEST
                    if ($userTest->getTentative() >= $numberTry && $userTest->getTest()->getTypeTest()->getConditional() != 'training') {
                        return $this->redirect($this->generateUrl('user_formation_module_organisation', [
                            'slugSession' => $currentSession->getSlug(),
                            'formationPath' => $currentSession->getFormationPath()->getId(),
                            'slugModule' => $module->getSlug(),
                            'evalTry' => true
                        ]));
                    } else {
                        $newUserTest = new UserTest();
                        $newUserTest->setSession($currentSession);
                        $newUserTest->setUser($user);
                        $newUserTest->setRefModule($module->getReference());
                        $newUserTest->setRefFormation('FnÂ°' . $currentSession->getFormationPath()->getId());
                        $newUserTest->setTest($test);
                        $newUserTest->setDateDown(new \DateTime());
                        $newUserTest->setTentative($userTest->getTentative() + 1);
                        $newUserTest->setNumberTry($numberTry);

                        $details = [];
                        $details['slug'] = $test->getSlug();
                        $details['id'] = $test->getId();
                        $details['title'] = $test->getTitle() ?? '';
                        $details['ref'] = $test->getRef();
                        $details['typeTest'] = ['title' => $test->getTypeTest()->getTitle(), 'conditional' => $test->getTypeTest()->getConditional()];
                        //$totalQuestionRequired = $em->getRepository('App\Entity\TestManagement\Test')->getQuestionTirer($test->getId());
                        $details['minQuestion'] = $test->getTotalRequiredQuestion();
                        $details['theoricalDuration'] = $test->getTheoricalDuration();
                        $details['isTestCommune'] = $test->getIsTestCommune() ?? '';
                        $details['testCommune'] = $test->getTestCommune() != null ? $test->getTestCommune()->getId() : '';
                        $details['scorePercentage'] = $moduleTest->getScore();
                        $details['numberTry'] = $numberTry;
                        $details['chronoQuestion'] = $moduleTest->getChronoQuestion() ?? '';
                        $details['chronoQuestionTime'] = $moduleTest->getChronoQuestionTime() != null ? $moduleTest->getChronoQuestionTime()->format('H-i') : '';
                        $details['chronoTest'] = $moduleTest->getChronoTest() ?? '';
                        $details['chronoTestTime'] = $moduleTest->getChronoTestTime() != null ? $moduleTest->getChronoTestTime()->format('H-i') : '';

                        $newUserTest->setDetail($details);

                        if ($moduleTest->getChronoTest()) {
                            $newUserTest->setCurrentChrono($moduleTest->getChronoTestTime());
                        }
                        if ($this->shuffleTestQuestions($test, $em) == null) {
                            return $this->redirect($this->generateUrl('user_formation_module', [
                                'slugSession' => $currentSession->getSlug(),
                            ]));
                        }
                        $newUserTest->setShuffleQuestions($this->shuffleTestQuestions($test, $em));

                        try {
                            $em->persist($newUserTest);
                            $em->flush();
                        } catch (\Doctrine\DBAL\DBALException $exception) {
                            $sfSession->getFlashBag()->add('error', $exception->getMessage());
                        } catch (\Exception $exception) {
                            $sfSession->getFlashBag()->add('error', $exception->getMessage());
                        }

                        return $this->redirect($this->generateUrl('test_front_question', [
                            'sessionSlug' => $currentSession->getSlug(),
                            'moduleSlug' => $module->getSlug(),
                            'testSlug' => $test->getSlug()
                        ]));
                    }
                    //TEST NOT OVER
                } elseif ($userTest->getDatePass() == null && $userTest->getLastIndexQuestion() != -1) {
                    return $this->redirect($this->generateUrl('test_front_question', [
                        'sessionSlug' => $currentSession->getSlug(),
                        'moduleSlug' => $module->getSlug(),
                        'testSlug' => $test->getSlug()
                    ]));
                } else {
                    return $this->redirect($this->generateUrl('user_formation_module', [
                        'slugSession' => $currentSession->getSlug(),
                    ]));
                }
            } else {
                $userTest = new UserTest();
                $userTest->setUser($user);
                $userTest->setSession($currentSession);
                $userTest->setRefModule($module->getReference());
                $userTest->setRefFormation('FnÂ°' . $currentSession->getFormationPath()->getId());
                $userTest->setTest($test);
                $userTest->setNumberTry($moduleTest->getNumberTry());
                $details = [];
                $details['slug'] = $test->getSlug();
                $details['id'] = $test->getId();
                $details['title'] = $test->getTitle() ?? '';
                $details['ref'] = $test->getRef();
                $details['typeTest'] = ['title' => $test->getTypeTest()->getTitle(), 'conditional' => $test->getTypeTest()->getConditional()];
                //$totalQuestionRequired = $em->getRepository('App\Entity\TestManagement\Test')->getQuestionTirer($test->getId());
                $details['minQuestion'] = $test->getTotalRequiredQuestion();
                $details['theoricalDuration'] = $test->getTheoricalDuration();
                $details['isTestCommune'] = $test->getIsTestCommune() ?? '';
                $details['testCommune'] = $test->getTestCommune() != null ? $test->getTestCommune()->getId() : '';
                $details['scorePercentage'] = $moduleTest->getScore();
                $details['numberTry'] = $moduleTest->getNumberTry();
                $details['chronoQuestion'] = $moduleTest->getChronoQuestion() ?? '';
                $details['chronoQuestionTime'] = $moduleTest->getChronoQuestionTime() != null ? $moduleTest->getChronoQuestionTime()->format('H-i') : '';
                $details['chronoTest'] = $moduleTest->getChronoTest() ?? '';
                $details['chronoTestTime'] = $moduleTest->getChronoTestTime() != null ? $moduleTest->getChronoTestTime()->format('H-i') : '';

                $userTest->setDetail($details);
                //$userTest->setDatePass(null);
                $userTest->setDateDown(new \DateTime());
                if ($moduleTest->getChronoTest()) {
                    $userTest->setCurrentChrono($moduleTest->getChronoTestTime());
                }
                if ($this->shuffleTestQuestions($test, $em) == null) {
                    return $this->redirect($this->generateUrl('user_formation_module', [
                        'slugSession' => $currentSession->getSlug(),
                    ]));
                }
                $userTest->setShuffleQuestions($this->shuffleTestQuestions($test, $em));

                $em->persist($userTest);
                $em->flush();

                return $this->redirect($this->generateUrl('test_front_question', [
                    'sessionSlug' => $currentSession->getSlug(),
                    'moduleSlug' => $module->getSlug(),
                    'testSlug' => $test->getSlug()
                ]));
            }
        } else {
            return $this->redirect($this->generateUrl('user_formation_module', [
                'slugSession' => $currentSession->getSlug(), 'preVue' => false
            ]));
        }
    }

    private function shuffleTestQuestions(Test $test, $manager)
    {
        $em = $this->getDoctrine()->getManager();
        if ($test->getIsTestCommune() && $test->getTestCommune() instanceof Test) {
            // pretest question commun
            $testUsed = $test->getTestCommune();
        } else {
            $testUsed = $test;
        }
        $questionArray = [];
        foreach ($testUsed->getPools() as $pool) {
            if ($pool->getIsValid() == true) {
                $requiredQuestions = $manager->getRepository('App\Entity\TestManagement\Question')->findRequiredByPoolID($pool->getId());
                $unrequiredQuestions = $manager->getRepository('App\Entity\TestManagement\Question')->findUnRequiredPoolID($pool->getId());

                $poolLimit = $pool->getNbRequQuestions();
                if (count($requiredQuestions) >= $poolLimit) {
                    foreach ($requiredQuestions as $question) {
                        array_push($questionArray, $question->getId());
                    }
                } else {
                    foreach ($requiredQuestions as $question) {
                        array_push($questionArray, $question->getId());
                    }
                    $tmpQt = [];
                    foreach ($unrequiredQuestions as $question) {
                        array_push($tmpQt, $question->getId());
                    }
                    if ($test->getTypeTest()->getConditional() == 'sondage') {
                    } else {
                        shuffle($tmpQt);
                    }
                    $unrequiredCount = $poolLimit - count($requiredQuestions);
                    for ($i = 0; $i < $unrequiredCount; $i++) {
                        array_push($questionArray, $tmpQt[$i]);
                    }
                }
                if ($test->getTypeTest()->getConditional() == 'sondage') {
                } else {
                    shuffle($questionArray);
                }
            }
        }
        $totalRequired = $test->getTotalRequiredQuestion(); //$em->getRepository('App\Entity\TestManagement\Test')->getQuestionTirer($test->getId());
        if (sizeof($questionArray) >= $totalRequired) {
            return $questionArray;
        } else {
            return null;
        }
    }

    /**
     * SHOW QUESTION
     *
     * @Route("/question/{sessionSlug}/{moduleSlug}/{testSlug}", name="test_front_question", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function doQuestion(Request $request, string $sessionSlug, string $moduleSlug, string $testSlug)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();

        $test = $em->getRepository('App\Entity\TestManagement\Test')->findOneBy([
            'slug' => $testSlug
        ]);

        $currentSession = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy([
            'slug' => $sessionSlug
        ]);
        $module = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBy([
            'slug' => $moduleSlug
        ]);

        if (!$test instanceof Test || !$module instanceof Module || !$currentSession instanceof Session) {
            throw new NotFoundHttpException('Test Not Found Exception');
        }
        
        $userTest = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findByTestAndUserIdAndModuleRef($user->getId(), $test->getId(), $module->getReference(), $currentSession->getId());

        $userTest = end($userTest);

        if ($userTest->getLastIndexQuestion() == -1) {
            return $this->redirect($this->generateUrl('test_front_result', [
                'sessionSlug' => $currentSession->getSlug(),
                'moduleSlug' => $module->getSlug(),
                'testSlug' => $test->getSlug(),
                'userTestID' => $userTest->getId()
            ]));
        }

        // GET THE QUESTION WITH USER TEST INDEX
        $question = $em->getRepository('App\Entity\TestManagement\Question')->find($userTest->getShuffleQuestions()[$userTest->getLastIndexQuestion()]);

        $moduleTest = $em->getRepository('App\Entity\FormationManagement\ModuleTest')->findByModuleANDTestId($module->getId(), $test->getId());

        $moduleTest = $moduleTest[0];

        $shuffleAnswer = [];

        foreach ($question->getAnswers() as $answer) {
            if ($answer->getIsValid() == true) {
                array_push($shuffleAnswer, $answer->getId());
            }
        }
        if ($test->getTypeTest()->getConditional() == 'sondage') {
        } else {
            shuffle($shuffleAnswer);
        }

        $answers = [];

        foreach ($shuffleAnswer as $answer) {
            array_push($answers, $em->getRepository('App\Entity\TestManagement\Answer')->find($answer));
        }

        $result = [];

        foreach ($module->getValidationModes() as $validationMode) {
            array_push($result, $validationMode->getConditional());
        }

        $userLastTest = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findByTestLastUser($user->getId(), $test->getId(), $currentSession);

        $nbtentetiveDown = 0;

        if ($userLastTest) {
            if ($userLastTest->getLastIndexQuestion() == -1 && $userLastTest->getScore() == -1) {
                $nbtentetiveDown = $userLastTest->getTentative();
            } else {
                $nbtentetiveDown = $userLastTest->getTentative() - 1;
            }
        }

        $userLastPage = $em->getRepository('App\Entity\UserFrontManagement\UserPageFollow')->findLastUserPage($user);
        //$nbQuestionTotal = $em->getRepository('App\Entity\TestManagement\Test')->getQuestionTirer($test->getId());
        return $this->render('UserFrontManagement/TestFront/question.html.twig', [
            'userTest' => $userTest,
            'validationModes' => $result,
            'moduleTest' => $moduleTest,
            'answers' => $answers,
            'question' => $question,
            'module' => $module,
            'formationPath' => $currentSession->getFormationPath(),
            'currentSession' => $currentSession,
            'nbtentetiveDown' => $nbtentetiveDown,
            'userLastPage' => $userLastPage,
            'nbQuestionTotal' => $test->getTotalRequiredQuestion(),
        ]);
    }

    /**
     * FROM AJAX CALL / CHECK ANSWER & STORE IT
     *
     * @Route("/answer/unique", name="test_front_answer_unique", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function checkAnswerUnique(Request $request, LoggerInterface $logger)
    {
        $em = $this->getDoctrine()->getManager();

        $data = json_decode($request->getContent(), true);

        $formationPath = $em->getRepository('App\Entity\FormationManagement\FormationPath')->findOneBy([
            'slug' => $data['formationPathSlug']
        ]);

        $module = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBy([
            'slug' => $data['moduleSlug']
        ]);

        $test = $em->getRepository('App\Entity\TestManagement\Test')->findOneBy([
            'slug' => $data['testSlug']
        ]);

        $question = $em->getRepository('App\Entity\TestManagement\Question')->find($data['question']);

        $currentSession = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy([
            'slug' => $data['sessionSlug']
        ]);
        //$logger->addError(json_encode($data));
        $user = $em->getRepository('App\Entity\UserManagement\User')->find($data['user']);

        $userTest = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findByTestAndUserIdAndModuleRef($user->getId(), $test->getId(), $module->getReference(), $currentSession->getId());

        $userTest = end($userTest);

        $verbalAnswers = [];
        foreach ($question->getAnswers() as $answer) {
            if ($answer->getIsValid() == true) {
                $answerDetails = ['id' => $answer->getId(), 'ref' => $answer->getRef(), 'content' => $answer->getContent(), 'status' => $answer->getStatus(), 'slug' => $answer->getSlug()];
                array_push($verbalAnswers, $answerDetails);
            }
        }

        $userQuestion = new UserQuestion();
        $userQuestion->setUserTest($userTest);
        $userQuestion->setUser($user);
        $userQuestion->setTest($test);
        $userQuestion->setQuestion($question);
        $userQuestion->setVerbalQuestion($question->getQuestion());
        $userQuestion->setVerbalAnswers($verbalAnswers);
        $userQuestion->setRefFormation('FnÂ°' . $formationPath->getId());
        $userQuestion->setRefModule($module->getReference());
        $userQuestion->setTestTentative($userTest->getTentative());
        $userQuestion->setUserAnswers($data['answer']);

        $questionDetails = [];
        $questionDetails['title'] = $question->getTitle() ?? '';
        $questionDetails['slug'] = $question->getSlug();
        $questionDetails['weight'] = $question->getWeight();
        $questionDetails['content'] = $question->getContent() ?? '';
        $questionDetails['question'] = $question->getQuestion();
        $questionDetails['comment'] = $question->getComment() ?? '';
        $questionDetails['required'] = $question->getRequired() ?? '';
        $questionDetails['answerType'] = $question->getAnswerType();
        $userQuestion->setQuestionDetails($questionDetails);

        if ($test->getTypeTest()->getConditional() == 'sondage') {
            $userQuestion->setScored(true);
        } else {
            if ($data['answer'] == 0) {
                $userQuestion->setScored(false);
            } else {
                $answer = $em->getRepository('App\Entity\TestManagement\Answer')->find($data['answer']);
                $userQuestion->setScored($answer->getStatus());
            }
        }

        $em->persist($userQuestion);
        $em->flush();

        // UserTest
        if ($userTest->getLastIndexQuestion() != count($userTest->getShuffleQuestions()) - 1) {
            $userTest->setLastIndexQuestion($userTest->getLastIndexQuestion() + 1);
            $em->persist($userTest);
            $em->flush();
            if ($userQuestion->getScored()) {
                return new Response('success');
            } else {
                return new Response('fail');
            }
        } else {
            //TEST OVER => SET INDEX TOO -1
            $userTest->setLastIndexQuestion(-1);
            $em->persist($userTest);
            $em->flush();
            if ($userQuestion->getScored()) {
                return new Response('success');
            } else {
                return new Response('fail');
            }
        }
    }

    /**
     * FROM AJAX CALL / CHECK ANSWER & STORE IT
     *
     * @Route("/answer/multiple", name="test_front_answer_multiple", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function checkAnswerMultiple(Request $request, LoggerInterface $logger)
    {
        $em = $this->getDoctrine()->getManager();

        $data = json_decode($request->getContent(), true);
        //$logger->addError(json_encode($data));

        $formationPath = $em->getRepository('App\Entity\FormationManagement\FormationPath')->findOneBy([
            'slug' => $data['formationPathSlug']
        ]);

        $module = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBy([
            'slug' => $data['moduleSlug']
        ]);

        $test = $em->getRepository('App\Entity\TestManagement\Test')->findOneBy([
            'slug' => $data['testSlug']
        ]);
        $currentSession = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy([
            'slug' => $data['sessionSlug']
        ]);

        $question = $em->getRepository('App\Entity\TestManagement\Question')->find($data['question']);

        $user = $em->getRepository('App\Entity\UserManagement\User')->find($data['user']);

        $userTest = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findByTestAndUserIdAndModuleRef($user->getId(), $test->getId(), $module->getReference(), $currentSession->getId());

        $userTest = end($userTest);

        $scored = true;
        $countAnswers = 0;
        $verbalAnswers = [];
        //CHECK GOOD ANSWER IN USER ANSWERS

        foreach ($question->getAnswers() as $answer) {
            if ($answer->getIsValid() == true) {
                $answerDetails = ['id' => $answer->getId(), 'ref' => $answer->getRef(), 'content' => $answer->getContent(), 'status' => $answer->getStatus(), 'slug' => $answer->getSlug()];
                array_push($verbalAnswers, $answerDetails);
                if ($answer->getStatus()) {
                    $countAnswers++;
                    if (!in_array($answer->getId(), $data['answers'])) {
                        $scored = false;
                    }
                }
            }
        }
        //CHECK IF SAME NUMBERS OF ANSWERS
        if ($test->getTypeTest()->getConditional() == 'sondage') {
            $scored = true;
        } else {
            if ($countAnswers != count($data['answers'])) {
                $scored = false;
            }
        }

        $userQuestion = new UserQuestion();
        $userQuestion->setUserTest($userTest);
        $userQuestion->setVerbalQuestion($question->getQuestion());
        $userQuestion->setVerbalAnswers($verbalAnswers);
        $userQuestion->setUser($user);
        $userQuestion->setTest($test);
        $userQuestion->setQuestion($question);
        $userQuestion->setRefFormation('FnÂ°' . $formationPath->getId());
        $userQuestion->setRefModule($module->getReference());
        $userQuestion->setTestTentative($userTest->getTentative());
        $userQuestion->setScored($scored);
        $userQuestion->setUserAnswers($data['answers']);

        $questionDetails = [];
        $questionDetails['title'] = $question->getTitle() ?? '';
        $questionDetails['slug'] = $question->getSlug();
        $questionDetails['weight'] = $question->getWeight();
        $questionDetails['content'] = $question->getContent() ?? '';
        $questionDetails['question'] = $question->getQuestion();
        $questionDetails['comment'] = $question->getComment() ?? '';
        $questionDetails['required'] = $question->getRequired() ?? '';
        $questionDetails['answerType'] = $question->getAnswerType();
        $userQuestion->setQuestionDetails($questionDetails);

        $em->persist($userQuestion);
        $em->flush();

        if ($userTest->getLastIndexQuestion() != count($userTest->getShuffleQuestions()) - 1) {
            $userTest->setLastIndexQuestion($userTest->getLastIndexQuestion() + 1);
            $em->persist($userTest);
            $em->flush();
            if ($userQuestion->getScored()) {
                return new Response('success');
            } else {
                return new Response('fail');
            }
        } else {
            $userTest->setLastIndexQuestion(-1);
            $em->persist($userTest);
            $em->flush();
            if ($userQuestion->getScored()) {
                return new Response('success');
            } else {
                return new Response('fail');
            }
            // return new Response($this->generateUrl('test_front_result', array(
            //     'formationPathSlug' => $formationPath->getSlug(),
            //     'moduleSlug' => $module->getSlug(),
            //     'testSlug' => $test->getSlug(),
            //     'userTestID' => $userTest->getId()
            // ),
            //     UrlGeneratorInterface::ABSOLUTE_URL
            // ));
        }
    }

    /**
     * FROM AJAX CALL / CHECK ANSWER & STORE IT
     *
     * @Route("/answer/text", name="test_front_answer_text_gradutation", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function checkAnswerTextGraduation(Request $request, LoggerInterface $logger)
    {
        $em = $this->getDoctrine()->getManager();
        $data = json_decode($request->getContent(), true);

        $formationPath = $em->getRepository('App\Entity\FormationManagement\FormationPath')->findOneBy(['slug' => $data['formationPathSlug']]);
        $module = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBy(['slug' => $data['moduleSlug']]);
        $test = $em->getRepository('App\Entity\TestManagement\Test')->findOneBy(['slug' => $data['testSlug']]);
        $question = $em->getRepository('App\Entity\TestManagement\Question')->find($data['question']);
        $currentSession = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy(['slug' => $data['sessionSlug']]);
        $user = $em->getRepository('App\Entity\UserManagement\User')->find($data['user']);
        $userTest = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findByTestAndUserIdAndModuleRef($user->getId(), $test->getId(), $module->getReference(), $currentSession->getId());

        $userTest = end($userTest);

        $verbalAnswers = [];
        if ($test->getTypeTest()->getConditional() == 'sondage') {
            $isGoodAnswer = true;
        } else {
            $isGoodAnswer = false;
            foreach ($question->getAnswers() as $answer) {
                if ($answer->getIsValid() == true) {
                    $answerDetails = ['id' => $answer->getId(), 'ref' => $answer->getRef(), 'content' => $answer->getContent(), 'status' => $answer->getStatus(), 'slug' => $answer->getSlug()];
                    array_push($verbalAnswers, $answerDetails);
                    if ($answer->getStatus() == true && $answer->getContent() == $data['answers']) {
                        $isGoodAnswer = true;
                    }
                }
            }
        }

        $userQuestion = new UserQuestion();
        $userQuestion->setUserTest($userTest);
        $userQuestion->setUser($user);
        $userQuestion->setTest($test);
        $userQuestion->setQuestion($question);
        $userQuestion->setVerbalQuestion($question->getQuestion());
        $userQuestion->setVerbalAnswers($verbalAnswers);
        $userQuestion->setRefFormation('FnÂ°' . $formationPath->getId());
        $userQuestion->setRefModule($module->getReference());
        $userQuestion->setTestTentative($userTest->getTentative());
        $userQuestion->setUserAnswers($data['answers']);

        $questionDetails = [];
        $questionDetails['title'] = $question->getTitle() ?? '';
        $questionDetails['slug'] = $question->getSlug();
        $questionDetails['weight'] = $question->getWeight();
        $questionDetails['content'] = $question->getContent() ?? '';
        $questionDetails['question'] = $question->getQuestion();
        $questionDetails['comment'] = $question->getComment() ?? '';
        $questionDetails['required'] = $question->getRequired() ?? '';
        $questionDetails['answerType'] = $question->getAnswerType();
        $userQuestion->setQuestionDetails($questionDetails);
        $userQuestion->setScored($isGoodAnswer);

        $em->persist($userQuestion);
        $em->flush();

        // UserTest
        if ($userTest->getLastIndexQuestion() != count($userTest->getShuffleQuestions()) - 1) {
            $userTest->setLastIndexQuestion($userTest->getLastIndexQuestion() + 1);
            $em->persist($userTest);
            $em->flush();
            if ($userQuestion->getScored()) {
                return new Response('success');
            } else {
                return new Response('fail');
            }
        } else {
            //TEST OVER => SET INDEX TOO -1
            $userTest->setLastIndexQuestion(-1);
            $em->persist($userTest);
            $em->flush();
            if ($userQuestion->getScored()) {
                return new Response('success');
            } else {
                return new Response('fail');
            }
        }
    }

    /**
     * FROM AJAX CALL / UPDATE EVERY SECOND
     *
     * @Route("/duration", name="test_front_duration", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function updateDurationTest(Request $request, LoggerInterface $logger)
    {
        $em = $this->getDoctrine()->getManager();

        $data = json_decode($request->getContent(), true);
        //$logger->addError(json_encode($data));

        //$user = $em->getRepository('App\Entity\UserManagement\User')->find($data['user']);

        $test = $em->getRepository('App\Entity\TestManagement\Test')->findBy([
            'slug' => $data['testSlug']
        ]);
        
        if ($test) {
            $userTest = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findOneBy([
                'user' => $this->getUser(),
                'test' => $test
            ]);
            if ($userTest) {
                $userTest->setDuration($userTest->getDuration() + 10);

                $em->persist($userTest);
                $em->flush();
                return new Response('OK');
            } else {
                return new Response('Not Found User Test');
            }
        } else {
            return new Response('Not Found Test');
        }
        return new Response('Null');        
    }

    /*
     * GET REQUIRED AND UNREQUIRED QUESTION FROM TEST
     * IF MORE REQUIRED QUESTION THAN TEST LIMIT => ALL REQUIRED QUESTION IN TEST
     * ELSE => ALL REQUIRED QUESTION IN TEST AND COMPLETE WITH UNREQUIRED
     * SHUFFLE ARRAY
     */

    /**
     * FROM AJAX CALL / UPDATE EVERY SECOND
     *
     * @Route("/chrono", name="test_front_chrono", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function updateChronoTest(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $data = json_decode($request->getContent(), true);

        $userTest = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->find($data['userTestID']);

        $userTest->setCurrentChronoSeconds($data['seconds']);

        $userTest->setDuration($userTest->getDuration() + 10);

        if ($data['seconds'] == 0) {
            $userTest->setLastIndexQuestion(-1);

            $em->persist($userTest);
            $em->flush();

            return new Response($this->generateUrl('test_front_result',
                [
                    'sessionSlug' => $data['sessionSlug'],
                    'moduleSlug' => $data['moduleSlug'],
                    'testSlug' => $userTest->getTest()->getSlug(),
                    'userTestID' => $userTest->getId()
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            ));
        }

        $em->persist($userTest);
        $em->flush();

        return new Response('OK');
    }

    /**
     * RESTULT OF TEST / IF EVAL => DONT SHOW CORRECTION
     *
     * @Route("/quit/{sessionSlug}/{moduleSlug}/{testSlug}", name="test_front_quit", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function quit(Request $request, $sessionSlug, $moduleSlug, $testSlug)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();

        $test = $em->getRepository('App\Entity\TestManagement\Test')->findOneBy([
            'slug' => $testSlug
        ]);

        $currentSession = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy(['slug' => $sessionSlug]);

        $module = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBy([
            'slug' => $moduleSlug
        ]);

        $moduleTest = $em->getRepository('App\Entity\FormationManagement\ModuleTest')->findByModuleANDTestId($module->getId(), $test->getId());

        $moduleTest = $moduleTest[0];

        $userTest = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findByTestAndUserIdAndModuleRef($user->getId(), $test->getId(), $module->getReference(), $currentSession->getId());

        $userTest = end($userTest);

        $userTest->setLastIndexQuestion(-1);

        $em->persist($userTest);
        $em->flush();

        return $this->redirect($this->generateUrl('test_front_result', [
            'sessionSlug' => $currentSession->getSlug(),
            'moduleSlug' => $module->getSlug(),
            'testSlug' => $test->getSlug(),
            'userTestID' => $userTest->getId()
        ]));
    }
}
