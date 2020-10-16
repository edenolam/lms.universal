<?php

namespace App\Command;

use App\Entity\UserManagement\LoggedMessage;
use App\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use App\Service\MailService;

/**
 * SendMailRelance
 *
 * @author null
 */
class SendMailRelance extends Command
{
    protected static $defaultName = 'lms:mail_formation_relance';
    protected $em;
    protected $twig;
    protected $mailer;
    protected $logger;
    protected $translator;
    protected $doctrine;

    public function __construct(\Twig_Environment $twig, \Swift_Mailer $mailer, ObjectManager $em, LoggerInterface $logger, TranslatorInterface $translator, ManagerRegistry $doctrine)
    {
        $this->em = $em;
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->doctrine  = $doctrine;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            // ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            // ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
            ->setHelp('This command allows you to alert the user...')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $users = $this->em->getRepository('App\Entity\UserManagement\User')->findBy([
            'enabled' => 1,
            'isValid' => 1,
            ]);

        $output->writeln('User number = ' . count($users));

        foreach ($users as $user) {
            foreach ($user->getSessions() as $session) {
                $today = new \DateTime();
                // les sessions encours
                if ($session->getOpeningDate()->format('Y-m-d') == $today->modify('+5 day')->format('Y-m-d')) {
                    // for nb formation(s) en cours
                    $userformationSessionfollow = $this->em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findOneBy(['user' => $user, 'session' => $session]);
                    $modules_todo = [];
                    $evaluations_todo = [];
                    // les modules à faire
                    foreach ($session->getFormationPath()->getFormationPathModules() as $formationPathModule) {
                        $userModule = $this->em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')->findOneBy(['user' => $user, 'module' => $formationPathModule->getModule(), 'session' => $session]);

                        if (!$userModule || !$userModule->getSuccess()) {
                            array_push($modules_todo, ['session' => $session, 'module' => $formationPathModule->getModule()]);
                            // les evaluation à faire
                            foreach ($formationPathModule->getModule()->getModuleTests() as $moduleTest) {
                                if ($moduleTest->getTest()->getTypeTest()->getConditional() == 'eval') {
                                    array_push($evaluations_todo, ['session' => $session, 'test' => $moduleTest->getTest(), 'module' => $formationPathModule->getModule()]);
                                }
                            }
                        }
                    }
                    if (sizeof($modules_todo) > 0 || count($evaluations_todo) > 0) {
                        /*
                         * $body = $this->renderTemplate($user, $userformationSessionfollow, $modules_todo, $evaluations_todo);
                            $message = (new \Swift_Message('RAPPEL [évaluation / formation] à réaliser sur la plateforme euro-academy'))
                            ->setFrom(['info@universalmedica.com' => 'universalmedica'])
                            ->setTo($user->getEmail())
                            ->setBody($body, 'text/html')
                            ->setCharset('utf-8');
                        */
                        $mailer = new MailService($this->twig,$this->mailer,$this->logger,$this->doctrine);
                        $from = ['info@universalmedica.com' => 'universalmedica'];
                        $to = $user->getEmail();
                        $subject = 'RAPPEL [évaluation / formation] à réaliser sur la plateforme euro-academy';
                        $body = $this->renderTemplate($user, $userformationSessionfollow, $modules_todo, $evaluations_todo);

                        //$output->writeln('User number = ' . $to);
                        $mailer->sendAMail($from, $to, $subject, $body);
                        /*try {
                            $reponse = $this->mailer->send($message);
                            $this->log($message);
                        } catch (\Swift_TransportException $e) {
                            $this->logger->addError($e->getMessage());
                        } catch (\Exception $e) {
                            $this->logger->addError($e->getMessage());
                        } */
                    }
                }
            }
        }
        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');
    }

    /**
     * @param $name
     * @param $email
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    protected function renderTemplate($user, $formation_todo, $modules_todo, $evaluations): string
    {
        return $this->twig->render(
            'UserFrontManagement/emails/sendMailRelance.html.twig',
            [
                'user' => $user,
                'modules' => $modules_todo,
                'formationSessionF' => $formation_todo,
                'evaluations' => $evaluations
            ]
        );
    }

    /*
     * @param $message
     * @param int $result
     * @param array $failures
     */
    /*public function log($message, $result = 1, $failures = [])
    {
        $loggedMessage = new LoggedMessage();
        $loggedMessage->setMessage($message);
        $loggedMessage->setResult($result);
        $loggedMessage->setFailedRecipients($failures);

        // application should not crash when logging fails
        try {
            $this->em->persist($loggedMessage);
            $this->em->flush($loggedMessage);
        } catch (\Exception $e) {
            $error = 'Logging sent message with \SwiftmailerLoggerBundle\Logger\EntityLogger failed: ' .
                $e->getMessage();
            $this->logger->addError($error);
        }
    }*/
}
