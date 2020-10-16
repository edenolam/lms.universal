<?php

namespace App\Manager;

use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\ModuleCourse;
use App\Entity\FormationManagement\ModuleTest;
use App\Entity\FormationManagement\PageReference;
use App\Persistence\ObjectManager;
use App\Serializer\FormationManagement\CourseSerializer;
use App\Serializer\FormationManagement\ModuleFileSerializer;
use App\Serializer\FormationManagement\ModuleSerializer;
use App\Serializer\FormationManagement\PageSerializer;
use App\Serializer\FormationManagement\ReferenceSerializer;
use App\Serializer\TestManagement\AnswerSerializer;
use App\Serializer\TestManagement\PoolSerializer;
use App\Serializer\TestManagement\QuestionSerializer;
use App\Serializer\TestManagement\TestSerializer;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * ModuleManager
 *
 * @author null
 */
class ModuleManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    protected $logger;
    protected $sfSession;
    protected $translator;
    protected $moduleSerializer;
    protected $courseSerializer;
    protected $pageSerializer;
    protected $referenceSerializer;
    protected $moduleFileSerializer;

    protected $testSerializer;
    protected $poolSerializer;
    protected $questionSerializer;
    protected $answerSerializer;

    /**
     * ModuleManager constructor.
     * @param EntityManager $em
     * @param Logger $logger
     */
    public function __construct(ModuleSerializer $moduleSerializer, CourseSerializer $courseSerializer, PageSerializer $pageSerializer, ReferenceSerializer $referenceSerializer, ModuleFileSerializer $moduleFileSerializer, TestSerializer $testSerializer, PoolSerializer $poolSerializer, QuestionSerializer $questionSerializer, AnswerSerializer $answerSerializer, ObjectManager $em, LoggerInterface $logger, SessionInterface $sfSession, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->sfSession = $sfSession;
        $this->translator = $translator;
        $this->moduleSerializer = $moduleSerializer;
        $this->courseSerializer = $courseSerializer;
        $this->pageSerializer = $pageSerializer;
        $this->referenceSerializer = $referenceSerializer;
        $this->moduleFileSerializer = $moduleFileSerializer;
        $this->testSerializer = $testSerializer;
        $this->poolSerializer = $poolSerializer;
        $this->questionSerializer = $questionSerializer;
        $this->answerSerializer = $answerSerializer;
    }

    /**
     * Dupliquer un module
     */
    public function dupliquer(Module $module)
    {
        $module_data = $this->moduleSerializer->serialize($module);
        $new_module = $this->moduleSerializer->deserialize($module_data);
        try {
            // create module
            // $this->em->persist($new_module);
            // $this->em->flush();            
            $this->em->startFlushSuite();
            $this->em->persist($new_module);
            $this->em->endFlushSuite();

            // OneToMany moduleTests
            foreach ($module->getModuleTests() as $moduleTest) {
                // create test
                $test_data = $this->testSerializer->serialize($moduleTest->getTest());
                $new_test = $this->testSerializer->deserialize($test_data);
                $new_test->setModule($new_module);
                $new_test->setParent($moduleTest->getTest());
                // $this->em->persist($new_test);
                // $this->em->flush();
                $this->em->startFlushSuite();
                $this->em->persist($new_test);
                $this->em->endFlushSuite();

                // then create moduleTest
                $new_moduleTest = new ModuleTest();
                $new_moduleTest->setScore($moduleTest->getScore());
                $new_moduleTest->setNumberTry($moduleTest->getNumberTry());
                //$new_moduleTest->setOpeningDate($ModuleTest->getOpeningDate());
                //$new_moduleTest->setClosingDate($ModuleTest->getClosingDate());
                $new_moduleTest->setChronoQuestion($moduleTest->getChronoQuestion());
                if ($moduleTest->getChronoQuestionTime()) {
                    $new_moduleTest->setChronoQuestionTime($moduleTest->getChronoQuestionTime());
                }
                $new_moduleTest->setChronoTest($moduleTest->getChronoTest());
                if ($moduleTest->getChronoTestTime()) {
                    $new_moduleTest->setChronoTestTime($moduleTest->getChronoTestTime());
                }

                $new_moduleTest->setModule($new_module);
                $new_moduleTest->setTest($new_test);

                // $this->em->persist($new_moduleTest);
                // $this->em->flush();
                $this->em->startFlushSuite();
                $this->em->persist($new_moduleTest);
                $this->em->endFlushSuite();

                // pools
                foreach ($moduleTest->getTest()->getPools() as $pool) {
                    // create pool
                    $pool_data = $this->poolSerializer->serialize($pool);
                    $new_pool = $this->poolSerializer->deserialize($pool_data);
                    $new_pool->setTest($new_test); // ManyToOne
                    // $this->em->persist($new_pool);
                    // $this->em->flush();
                    $this->em->startFlushSuite();
                    $this->em->persist($new_pool);
                    $this->em->endFlushSuite();

                    // questions
                    foreach ($pool->getQuestions() as $question) {
                        // create question
                        $question_data = $this->questionSerializer->serialize($question);
                        $new_question = $this->questionSerializer->deserialize($question_data, $new_test, $new_pool, $new_module);
                        $this->em->startFlushSuite();
                        $this->em->persist($new_question);
                        $this->em->endFlushSuite();

                        // $this->em->persist($new_question);
                        // $this->em->flush();

                        // answers
                        foreach ($question->getAnswers() as $answer) {
                            // create answer
                            $answer_data = $this->answerSerializer->serialize($answer);
                            $new_answer = $this->answerSerializer->deserialize($answer_data);
                            $new_answer->setQuestion($new_question); // ManyToOne $question
                            $this->em->startFlushSuite();
                            $this->em->persist($new_answer);
                            $this->em->endFlushSuite();
                            // $this->em->persist($new_answer);
                            // $this->em->flush();
                        }
                    }
                }
            }

            // OneToMany files = ModuleFile
            foreach ($module->getFiles() as $moduleFile) {
                $module_file_data = $this->moduleFileSerializer->serialize($moduleFile);
                $new_module_file = $this->moduleFileSerializer->deserialize($module_file_data, $new_module);
                $this->em->persist($new_module_file);
                $this->em->flush();
            }

            // OneToMany moduleCourse
            foreach ($module->getModuleCourses() as $moduleCourse) {
                // create course
                $course_data = $this->courseSerializer->serialize($moduleCourse->getCourse());
                $new_course = $this->courseSerializer->deserialize($course_data);
                $this->em->persist($new_course);
                $this->em->flush();

                // then create moduleCourse
                $new_moduleCourse = new ModuleCourse();
                $new_moduleCourse->setSort($moduleCourse->getSort());
                $new_moduleCourse->setModule($new_module);
                $new_moduleCourse->setCourse($new_course);
                $this->em->persist($new_moduleCourse);
                $this->em->flush();

                // OneToMany pages
                foreach ($moduleCourse->getCourse()->getPages() as $page) {
                    // create page
                    $page_data = $this->pageSerializer->serialize($page);
                    $new_page = $this->pageSerializer->deserialize($page_data, $new_course, $new_module);
                    $this->em->persist($new_page);
                    $this->em->flush();

                    // OneToMany pageReferences
                    foreach ($page->getPageReferences() as $pageReference) {
                        // create reference
                        $reference_data = $this->referenceSerializer->serialize($pageReference->getReference());
                        $new_reference = $this->referenceSerializer->deserialize($reference_data, $new_module);
                        $this->em->persist($new_reference);
                        $this->em->flush();

                        // then create pageReferences
                        $new_pageReference = new PageReference();
                        $new_pageReference->setSort($pageReference->getSort());
                        $new_pageReference->setPage($new_page);
                        $new_pageReference->setReference($new_reference);
                        $this->em->persist($new_pageReference);
                        $this->em->flush();
                    }
                }
            }
        } catch (\Doctrine\DBAL\DBALException $exception) {
            $this->sfSession->getFlashBag()->add('error', $exception->getMessage());
        }

        return;
    }
}
