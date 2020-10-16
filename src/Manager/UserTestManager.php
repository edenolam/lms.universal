<?php

namespace App\Manager;

use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\ModuleTest;
use App\Entity\PlanningManagement\Session;
use App\Entity\TestManagement\Test;
use App\Entity\UserFrontManagement\UserTest;
use App\Entity\UserManagement\User;
use App\Persistence\ObjectManager;
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * User Test Manager
 *
 * @author null
 */
class UserTestManager
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

    public function createUserTest(Session $session, Module $module, ModuleTest $moduleTest, Test $test, User $user, UserTest $userTest = null, int $numberTry): void
    {
        try {
            $newUserTest = new UserTest();
            $newUserTest->setSession($session);
            $newUserTest->setUser($user);
            $newUserTest->setRefModule($module->getReference());
            $newUserTest->setRefFormation('FnÂ°' . $session->getFormationPath()->getId());
            $newUserTest->setTest($test);
            if ($userTest) {
                $newUserTest->setTentative($userTest->getTentative() + 1);
            }
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
            $details['numberTry'] = $moduleTest->getNumberTry();
            $details['chronoQuestion'] = $moduleTest->getChronoQuestion() ?? '';
            $details['chronoQuestionTime'] = $moduleTest->getChronoQuestionTime() != null ? $moduleTest->getChronoQuestionTime()->format('H-i') : '';
            $details['chronoTest'] = $moduleTest->getChronoTest() ?? '';
            $details['chronoTestTime'] = $moduleTest->getChronoTestTime() != null ? $moduleTest->getChronoTestTime()->format('H-i') : '';

            $newUserTest->setDetail($details);
            //$userTest->setDatePass(null);
            $newUserTest->setDateDown(new \DateTime());
            if ($moduleTest->getChronoTest()) {
                $newUserTest->setCurrentChrono($moduleTest->getChronoTestTime());
            }

            $newUserTest->setShuffleQuestions($this->shuffleTestQuestions($test, $this->em));

            $this->em->persist($newUserTest);
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
