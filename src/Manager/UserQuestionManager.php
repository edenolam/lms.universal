<?php

namespace App\Manager;

use App\Entity\FormationManagement\FormationPath;
use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\ModuleTest;
use App\Entity\PlanningManagement\Session;
use App\Entity\TestManagement\Question;
use App\Entity\TestManagement\Test;
use App\Entity\UserFrontManagement\UserQuestion;
use App\Entity\UserFrontManagement\UserTest;
use App\Entity\UserManagement\User;
use App\Persistence\ObjectManager;
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * User Question Manager
 *
 * @author null
 */
class UserQuestionManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    protected $logger;
    protected $sfSession;
    protected $translator;

    /**
     * UserModuleFollowManager constructor.
     * @param EntityManager $em
     * @param Logger $logger
     */
    public function __construct(ObjectManager $em, LoggerInterface $logger, SessionInterface $sfSession, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->sfSession = $sfSession;
        $this->translator = $translator;
    }

    public function createUserQuestion(Session $session, FormationPath $formationPath, Module $module, ModuleTest $moduleTest, Test $test, User $user, UserTest $userTest, Question $question, string $verbalAnswers, bool $scored, array $data): void
    {
        try {
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

            $this->em->persist($userQuestion);
            $this->em->flush();

        } catch (DBALException $e) {
            $this->logger->err($e->getMessage());
            $this->sfSession->getFlashBag()->add('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage());
            $this->sfSession->getFlashBag()->add('error', $e->getMessage());
        }

        return;
    }
}
 