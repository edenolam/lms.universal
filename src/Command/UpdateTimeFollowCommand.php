<?php

namespace App\Command;


use App\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class UpdateTimeFollowCommand extends Command
{
    protected $em;

    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('update:module_session_time')
            ->setDescription('update follow module / session time duration')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $begin = new \DateTime();
        $output->writeln('<comment>Begin : ' . $begin->format('d-m-Y G:i:s') . ' ---</comment>');

        $userModuleFollows = $this->em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')->findAll();
        $userFormationSessionFollowq = $this->em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findAll();
        $countModule = 0;
        $countSession = 0;

        foreach ($userModuleFollows as $userModuleFollow) {
            if($userModuleFollow->getDurationTotal()!=null){
                $Hs=((int)$userModuleFollow->getDurationTotal()->format('H'))*3600;
                $Ms=((int)$userModuleFollow->getDurationTotal()->format('i'))*60;
                $Ss=(int)$userModuleFollow->getDurationTotal()->format('s');
                $output->writeln('<comment>ori : ' . $userModuleFollow->getDurationTotal()->format('H:i:s') .'--- Hs:'.$Hs.'--- Ms:'.$Ms .'--- Ss:'.$Ss.' ---</comment>');
                $userModuleFollow->setDurationTotalSec($Hs+$Ms+$Ss);
            }else{
                $userModuleFollow->setDurationTotalSec(0);
            }
            $userModuleFollow->setDurationLastSessionSec(0);
            $this->em->persist($userModuleFollow);
            $this->em->flush();
            $countModule ++;
        }

        foreach ($userFormationSessionFollowq as $userFormationSessionFollow) {
            if($userFormationSessionFollow->getDurationTotal()!=null){
                $Hs=((int)$userFormationSessionFollow->getDurationTotal()->format('H'))*3600;
                $Ms=((int)$userFormationSessionFollow->getDurationTotal()->format('i'))*60;
                $Ss=(int)$userFormationSessionFollow->getDurationTotal()->format('s');
                
                $userFormationSessionFollow->setDurationTotalSec($Hs+$Ms+$Ss);
            }else{
                $userFormationSessionFollow->setDurationTotalSec(0);
            }
            $userFormationSessionFollow->setDurationLastSessionSec(0);
            $this->em->persist($userFormationSessionFollow);
            $this->em->flush();
            $countSession ++;
        }

        $end = new \DateTime();
        $output->writeln('<comment>End : ' . $end->format('d-m-Y G:i:s') .'--- modules:'.$countModule.'--- session:'.$countSession. ' ---</comment>');
    }
}
