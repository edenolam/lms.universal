<?php

namespace App\Command;

use App\Entity\TestManagement\Pool;
use App\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PoolGenerator extends Command
{
    protected $em;

    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('update:test_pool');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $begin = new \DateTime();
        $output->writeln('<comment>Begin : ' . $begin->format('d-m-Y G:i:s') . ' ---</comment>');

        $tests = $this->em->getRepository('App\Entity\TestManagement\Test')->findAll();

        foreach ($tests as $test) {
            $output->writeln('<comment>test : ' . $test->getTitle() . ' => ' . sizeof($test->getQuestions()) . ' ---</comment>');
            if (sizeof($test->getPools()) == 0) {
                $user = $this->em->getRepository('App\Entity\UserManagement\User')->findOneBy(['id' => 1]);
                $pool = new Pool();
                $pool->setTest($test);
                $pool->setRevision(0);
                $pool->setCreateDate(new \Datetime());
                $pool->setUpdateDate(new \Datetime());
                $pool->setCreateUser($user);
                $pool->setUpdateUser($user);
                $pool->setisValid(1);
                $pool->setTitle('pooldefault');
                $pool->setNbRequQuestions(1);
                $pool->setRef('ref');
                $this->em->persist($pool);
                $this->em->flush();

                foreach ($test->getQuestions() as $question) {
                    $question->setPool($pool);
                    $this->em->persist($question);
                    $this->em->flush();
                }

                $output->writeln('<comment>pool : ' . sizeof($pool->getQuestions()) . ' ---</comment>');
            }
        }

        $end = new \DateTime();
        $output->writeln('<comment>End : ' . $end->format('d-m-Y G:i:s') . ' ---</comment>');
    }
}
