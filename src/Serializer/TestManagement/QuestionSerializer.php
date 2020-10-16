<?php

namespace App\Serializer\TestManagement;

use App\Entity\FormationManagement\Knowledge;
use App\Entity\FormationManagement\Module;
use App\Entity\LovManagement\AnswerType;
use App\Entity\TestManagement\Pool;
use App\Entity\TestManagement\Question;
use App\Entity\TestManagement\Test;
use App\Persistence\ObjectManager;
use App\Repository\FormationManagement\KnowledgeRepository;
use App\Repository\LovManagement\AnswerTypeRepository;
use App\Repository\UserManagement\UserRepository;

/**
 * QuestionSerializer
 *
 * @author null
 */
class QuestionSerializer
{
    private $userRepository;
    private $knowledgeRepository;
    private $answerTypeRepository;
    private $om;

    public function __construct(UserRepository $userRepository, KnowledgeRepository $knowledgeRepository, AnswerTypeRepository $answerTypeRepository, ObjectManager $om)
    {
        $this->userRepository = $userRepository;
        $this->knowledgeRepository = $knowledgeRepository;
        $this->answerTypeRepository = $answerTypeRepository;
        $this->om = $om;
    }

    /**
     * @param Question $question
     *
     * @return array
     */
    public function serialize(Question $question)
    {
        // use ActorTrait, RevisionTrait, DateTrait, IsValidTrait, IsDeletedTrait;
        return [
                'id' => $question->getId(),
                'ref' => $question->getRef(),
                'slug' => $question->getSlug(),
                'title' => $question->getTitle(),
                'weight' => $question->getWeight(),
                'content' => $question->getContent(),
                'comment' => $question->getComment(),
                'required' => $question->getRequired(),
                'theoricalDuration' => $question->getTheoricalDuration(),
                'question' => $question->getQuestion(),
                'answerType' => [ // ManyToOne
                        'id' => $question->getAnswerType()->getId(),
                        'title' => $question->getAnswerType()->getTitle(),
                    ],
                'knowledges' => $this->knowledgesSerialize($question), // ManyToMany knowledges
                'revision' => $question->getRevision(), // RevisionTrait
                'isValid' => $question->getIsValid(), // IsValidTrait
                'isDeleted' => $question->getIsDeleted(), //IsDeletedTrait;
                'createDate' => $question->getCreateDate()->format('d/m/Y H:i'), // DateTrait
                'updateDate' => $question->getUpdateDate()->format('d/m/Y H:i'),
                'createUser' => [ // ManyToOne
                    'id' => $question->getCreateUser()->getId(), // ActorTrait
                    'username' => $question->getCreateUser()->getUsername()],
                'updateUser' => [
                    'id' => $question->getUpdateUser()->getId(), // ActorTrait
                    'username' => $question->getUpdateUser()->getUsername()]
            ];
        // ManyToMany knowledges
        // ManyToOne answerType
        // OneToMany answers
        // ManyToOne test
        // ManyToOne pool
        // ManyToOne User
    }

    public function deserialize(array $data, Test $test, Pool $pool, Module $module)
    {
        $question = new Question();
        $question->setTest($test);
        $question->setPool($pool);
        $question->setRef($data['ref']);
        if ($data['title']) {
            $question->setTitle($data['title']);
        }
        $question->setWeight($data['weight']);
        if ($data['content']) {
            $question->setContent($data['content']);
        }
        if ($data['comment']) {
            $question->setComment($data['comment']);
        }
        $question->setRequired($data['required']);
        if ($data['theoricalDuration']) {
            $question->setTheoricalDuration(new \DateTime($data['theoricalDuration']));
        }
        $question->setQuestion($data['question']);
        $question->setIsValid($data['isValid']);
        $question->setIsDeleted($data['isDeleted']);
        // ManyToOne answerType
        if (isset($data['answerType']) && isset($data['answerType']['id'])) {
            $answerType = $this->answerTypeRepository->findOneBy([
                'id' => $data['answerType']['id']
            ]);

            if (!empty($answerType)) {
                $question->setAnswerType($answerType);
            }
        }
        // ManyToMany knowledges
        if (isset($data['knowledges'])) {
            foreach ($data['knowledges'] as $_knowledge) {
                $knowledge = $this->knowledgeRepository->findOneBy([
                    'parent' => $_knowledge['id']
                ]);

                if (!empty($knowledge)) {
                    $question->addKnowledge($knowledge);
                } else {
                    $parent = $this->knowledgeRepository->findOneBy([
                        'id' => $_knowledge['id']
                    ]);
                    $knowledge = new Knowledge();
                    $knowledge->setParent($parent);
                    $knowledge->setTitle($_knowledge['title']);
                    $knowledge->setIsValid($_knowledge['isValid']);
                    $knowledge->setModule($module);
                    $this->om->startFlushSuite();
                    $this->om->persist($knowledge);
                    $this->om->endFlushSuite();
                    $question->addKnowledge($knowledge);
                }
            }
        }
        // ManyToOne User
        if (isset($data['createUser']) && isset($data['createUser']['id'])) {
            $createUser = $this->userRepository->findOneBy([
                'id' => $data['createUser']['id']
            ]);

            if (!empty($createUser)) {
                $question->setCreateUser($createUser);
            }
        }
        // ManyToOne User
        if (isset($data['updateUser']) && isset($data['updateUser']['id'])) {
            $updateUser = $this->userRepository->findOneBy([
                'id' => $data['updateUser']['id']
            ]);

            if (!empty($updateUser)) {
                $question->setUpdateUser($updateUser);
            }
        }

        return $question;
    }

    private function knowledgesSerialize(Question $question)
    {
        $knowledges = [];
        foreach ($question->getKnowledges() as $knowledge) {
            array_push($knowledges, [
            'id' => $knowledge->getId(),
            'slug' => $knowledge->getSlug(),
            'revision' => $knowledge->getRevision(),  // RevisionTrait
            'title' => $knowledge->getTitle(),
            'isValid' => $knowledge->getIsValid(), // IsValidTrait
        ]);
        }

        return $knowledges;
    }
}
