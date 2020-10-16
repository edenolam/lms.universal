<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument,InputInterface,InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;
use App\Persistence\ObjectManager;
use App\Entity\UserFrontManagement\{UserFormationSessionFollow, UserModuleFollow};
use App\Entity\FormationManagement\{FormationPath, FormationPathModule, Module, ScoTracking};
use App\Entity\PlanningManagement\Session;
use App\Entity\UserManagement\{User, LoggedMessage};
use Doctrine\Common\Persistence\ManagerRegistry;
use App\Service\MailService;

/**
 * MailTauxReussite
 *
 * @author null
 */
class MailTauxReussite extends Command
{
    //SendMailFormationOpen
    protected static $defaultName = 'lms:stat_question';
    protected $em;
    protected $twig;
    protected $mailer;
    protected $logger;
    protected $translator;
    protected $doctrine;

    public function __construct(\Twig_Environment $twig, \Swift_Mailer $mailer,ObjectManager $em, LoggerInterface $logger, TranslatorInterface $translator, ManagerRegistry $doctrine)
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
            ->setHelp('This command allows you to alert the user...')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $begin = new \DateTime();
        $output->writeln('<comment>Begin : ' . $begin->format('d-m-Y G:i:s') . ' --- </comment>');

        $pools = $this->em->getRepository('App\Entity\TestManagement\Pool')->findAll();
        $tabQuestion = array();

        $respos = $this->em->getRepository('App\Entity\UserManagement\User')->findAllByRoles('ROLE_RESPONSABLE_FORMATION');
        $responsable = [];
        foreach ($respos as $user){
            $responsable[] = $user['email'];
        }
        foreach($pools as $pool){
            foreach($pool->getQuestions() as $question){
                if($question->getIsDeleted() == false){
                    $statRight = $this->em->getRepository('App\Entity\TestManagement\Question')->checkQuestionStat($question, "oui");
                    $statAll = $this->em->getRepository('App\Entity\TestManagement\Question')->checkQuestionStat($question, null);
                    if($statRight != 0){
                        $moyenSucces = $statRight / $statAll * 100;
                        if($statAll >= 10 and $moyenSucces <= 60){
                            $tabQuestion[$question->getId()]['question'] = $question;
                            $tabQuestion[$question->getId()]['moyenSucces'] = $moyenSucces;
                            //$output->writeln($responsable);
                            //$output->writeln($statAll);
                        }
                    }
                }
            }
        }
        if(sizeof($tabQuestion) > 0){
            $mailer = new MailService($this->twig,$this->mailer,$this->logger,$this->doctrine);
            $from = ['info@universalmedica.com' => 'universalmedica'];
            $to = $responsable;
            $subject = 'INFO - Taux de réussite question';
            $body = $this->renderTemplate($tabQuestion);
            $mailer->sendAmail($from, $to, $subject, $body);
        }

        /*
         *
        $users = $this->em->getRepository('App\Entity\UserManagement\User')->findAll();
        foreach($users as $user){
            foreach($user->getGroups() as $group){
                if (in_array('ROLE_RESPONSABLE_FORMATION', $group->getRoles())) {

                }
            }
        }
        *
        */

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
    protected function renderTemplate($tabQuestion)
    {
        return $this->twig->render(
            'TestManagement/Question/mail_taux_reussite.html.twig',
            [
                'questions' => $tabQuestion,
            ]
        );
    }

}
?>