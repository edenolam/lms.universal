<?php

namespace App\Command;

use App\Entity\PlanningManagement\Session;
use App\Entity\UserFrontManagement\UserFormationSessionFollow;
use App\Entity\UserManagement\LoggedMessage;
use App\Entity\UserManagement\User;
use App\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use App\Service\MailService;


/**
 * SendMailFormationOpen
 *
 * @author null
 */
class SendMailFormationOpen extends Command
{
    // SendMailFormationOpen
    protected static $defaultName = 'lms:mail_formation_open';
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
        $this->doctrine = $doctrine;

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
        $begin = new \DateTime();
        $output->writeln('<comment>Begin : ' . $begin->format('d-m-Y G:i:s') . ' ---</comment>');

        $users = $this->em->getRepository('App\Entity\UserManagement\User')->findBy([
            'enabled' => 1,
            'isValid' => 1,
            ]);

        foreach ($users as $user) {
            $formations_todo = [];
            $modules_todo = [];

            foreach ($user->getSessions() as $session) {
                $today = new \DateTime();

                // les sessions encours et la formation à 5 jours avant la clôture
                //if ($session->getOpeningDate() < $today && $session->getClosingDate() > $today && $session->getClosingDate()->format('Y-m-d') == $today->modify('+6 day')->format('Y-m-d') ) {
                if ($session->getOpeningDate()->format('Y-m-d') == $today->modify('+0 day')->format('Y-m-d')) {
                    $output->writeln('User = ' . $user->getUsername());

                    // for nb formation(s) en cours
                    $userformationSessionfollow = $this->em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findOneBy(['user' => $user, 'session' => $session]);
                    if ($userformationSessionfollow instanceof UserFormationSessionFollow) { // user session déjà commencer
                        if ($userformationSessionfollow->getSuccess() == false) { // formation not success
                            array_push($formations_todo, [
                                'session' => $session,
                                'userformationSessionfollow' => $userformationSessionfollow
                            ]);
                            $output->writeln('session = ' . $session->getTitle());
                        }
                    } else { // user session jamais commencer
                        array_push($formations_todo, [
                                'session' => $session,
                                'userformationSessionfollow' => null
                            ]);
                        $output->writeln('session = ' . $session->getTitle());
                    }

                    //les modules à faire
                    foreach ($session->getFormationPath()->getFormationPathModules() as $formationPathModule) {
                        $userModule = $this->em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')->findOneBy([
                            'user' => $user,
                            'module' => $formationPathModule->getModule(),
                            'session' => $session
                        ]);
                        if (!$userModule || !$userModule->getSuccess()) {
                            array_push($modules_todo, [
                                'session' => $session,
                                'module' => $formationPathModule->getModule()
                            ]);
                        }
                    }

                    $mailer = new MailService($this->twig,$this->mailer,$this->logger,$this->doctrine);
                    $from = ['info@universalmedica.com' => 'universalmedica'];
                    $to = $user->getEmail();
                    $subject = 'Votre prochaine formation à réaliser sur la plateforme euro-academy';
                    $body = $this->renderTemplate($user, $formations_todo, $modules_todo);
                    $mailer->sendAmail($from, $to, $subject, $body);
                }
            }

            /*
            $message = (new \Swift_Message('Votre prochaine formation à réaliser sur la plateforme euro-academy'))
                ->setFrom(['info@universalmedica.com' => 'universalmedica'])
                ->setTo($user->getEmail())
                ->setBody($body, 'text/html')
                ->setCharset('utf-8')
            ;
            if (count($formations_todo) > 0) {
                try {
                    $reponse = $this->mailer->send($message);
                    $this->log($message);
                } catch (\Swift_TransportException $e) {
                    $this->logger->addError($e->getMessage());
                } catch (\Exception $e) {
                    $this->logger->addError($e->getMessage());
                }
            }
            */
        }
        $end = new \DateTime();
        $output->writeln('<comment>End : ' . $end->format('d-m-Y G:i:s') . ' ---</comment>');
    }

    /**
     * @param $name
     * @param $email
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    protected function renderTemplate($user, $formations_todo, $modules_todo): string
    {
        return $this->twig->render(
            'UserFrontManagement/emails/SendMailFormationOpen.html.twig',
            [
                'user' => $user,
                'formations_todo' => $formations_todo,
                'modules_todo' => $modules_todo
            ]
        );
    }

    /*
     * @param $message
     * @param int $result
     * @param array $failures
     *//*
    public function log($message, $result = 1, $failures = [])
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
