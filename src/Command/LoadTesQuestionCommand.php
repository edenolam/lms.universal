<?php

namespace App\Command;

use App\Entity\TestManagement\Answer;
use App\Entity\LovManagement\AnswerType;
use App\Entity\TestManagement\Question;
use App\Entity\TestManagement\Pool;
use App\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Manager\AuditManager;
use Symfony\Component\Finder\Finder;

class LoadTesQuestionCommand extends Command
{
    protected $em;
    protected $auditManager;

    public function __construct(ObjectManager $em, AuditManager $auditManager)
    {
        $this->em = $em;
        $this->auditManager = $auditManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('load:test_question')
            ->setDescription('load question of test by CSV')
            ->addArgument('csv', InputArgument::REQUIRED, 'What is the CSV name located in directory sql directory?')
            ->addArgument('id_test', InputArgument::REQUIRED, 'What is the test id ?')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $begin = new \DateTime();
        $output->writeln('<comment>Begin : ' . $begin->format('d-m-Y G:i:s') . ' ---</comment>');

        $csv = $input->getArgument('csv');
        $id_test = $input->getArgument('id_test');
        $finder = new Finder();

        $finder->files()->in('sql')->name($csv);
        if ($finder->hasResults()) {
            foreach ($finder as $f) {
                $file = $f;
            }
            $test = $this->em->getRepository('App\Entity\TestManagement\Test')->findOneBy(['id' => $id_test]);
            if ($test != null) {
                $info = $this->load($file, $test);
            //var_dump($info);
            } else {
                $output->writeln('Aucun test avec un id = ' . $id_test);
            }
        } else {
            $output->writeln('Pas de fichier csv ' . $csv . 'dans le dossier SQl');
        }

        $end = new \DateTime();
        $output->writeln('<comment>End : ' . $end->format('d-m-Y G:i:s') . ' ---</comment>');
    }

    protected function load($file, $test)
    {
        $handle = fopen($file->getRealPath(), 'r');
        $user = $this->em->getRepository('App\Entity\UserManagement\User')->findOneBy(['id' => 1]);
        $i = 1;
        $info = [];
        $questionNb = 0;
        $answerNb = 0;
        $pool = new Pool();
        $pool->setTitle("Pool auto generated");
        $pool->setRef("Tn°".$test->getId()."Pn°1");
        $pool->setNbRequQuestions(1);
        $pool->setTest($test);
        $this->em->persist($pool);
        $this->em->flush();
        $this->auditManager->generateAudit(null, $pool, "add", $user);
        while (($data = fgetcsv($handle, null, ';')) !== false) {
            if ($i != 1) {
                $typeAnswer = $this->em->getRepository('App\Entity\LovManagement\AnswerType')->findOneBy(['id' => $data[2]]);
                if ($typeAnswer != null) {
                    $question = $this->em->getRepository('App\Entity\TestManagement\Question')->findOneBy(['question' => utf8_encode($data[1]), 'test' => $test, 'answerType' => $typeAnswer]);
                   

                    if ($question == null) {
                        $question = new Question();
                        $question->setWeight(1);
                        $question->setTitle(addslashes(preg_replace("#\n|\t|\r#", '', utf8_encode($data[0]))));
                        $question->setRequired(0);
                        $question->setQuestion(addslashes(preg_replace("#\n|\t|\r#", '', utf8_encode($data[1]))));
                        $question->setAnswerType($typeAnswer);
                        $question->setCreateUser($user);
                        $question->setUpdateUser($user);
                        $question->setRevision(0);
                        $question->setCreateDate(new \Datetime());
                        $question->setUpdateDate(new \Datetime());
                        $question->setTest($test);
                        $question->setPool($pool);
                        $pool->addQuestion($question);
                        $test->addQuestion($question);
                        $question->setIsValid(1);
                        $question->setRef('T_' . $test->getId() . 'Q');
                        $this->em->persist($question);
                        $this->em->persist($test);
                        $this->em->flush();
                        $this->auditManager->generateAudit(null, $question, "add", $user);
                        $questionNb++;
                    }

                    $answer = new Answer();
                    $answer->setContent(addslashes(preg_replace("#\n|\t|\r#", '', utf8_encode($data[3]))));
                    $answer->setStatus($data[4]);
                    $answer->setCreateUser($user);
                    $answer->setUpdateUser($user);
                    $answer->setRevision(0);
                    $answer->setCreateDate(new \Datetime());
                    $answer->setUpdateDate(new \Datetime());
                    $answer->setIsValid(1);
                    $answer->setQuestion($question);
                    $answer->setRef('');
                    $this->em->persist($answer);
                    $this->em->flush();
                    
                    // création de la référence
                    $ref = 'Q_' . $question->getId() . '_An°' . $answer->getId();
                    $answer->setRef($ref);
                    $question->addAnswer($answer);
                    $this->em->persist($answer);
                    $this->em->persist($question);
                    $this->em->flush();
                    $this->auditManager->generateAudit(null, $answer, "add", $user);
                    $answerNb++;
                } else {
                    $info['answerType'][$i] = 'ligne' . $i . 'pas de type de réponse' . $data[1];
                }
            }
            $i++;
        }
        $info['answerNb'] = $answerNb;
        $info['questionNb'] = $questionNb;
        fclose($handle);

        return $info;
    }
}
