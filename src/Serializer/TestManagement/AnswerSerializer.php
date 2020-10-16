<?php

namespace App\Serializer\TestManagement;

use App\Entity\TestManagement\Answer;
use App\Repository\UserManagement\UserRepository;

/**
 * AnswerSerializer
 *
 * @author null
 */
class AnswerSerializer
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @param Answer $answer
     *
     * @return array
     */
    public function serialize(Answer $answer)
    {
        // use ActorTrait, RevisionTrait, DateTrait, IsValidTrait, IsDeletedTrait;
        return [
                'id' => $answer->getId(),
                'ref' => $answer->getRef(),
                'slug' => $answer->getSlug(),
                'content' => $answer->getContent(),
                'status' => $answer->getStatus(),
                'revision' => $answer->getRevision(), // RevisionTrait
                'isValid' => $answer->getIsValid(), // IsValidTrait
                'isDeleted' => $answer->getIsDeleted(), //IsDeletedTrait;
                'createDate' => $answer->getCreateDate()->format('d/m/Y H:i'), // DateTrait
                'updateDate' => $answer->getUpdateDate()->format('d/m/Y H:i'),
                'createUser' => [ // ManyToOne
                    'id' => $answer->getCreateUser()->getId(), // ActorTrait
                    'username' => $answer->getCreateUser()->getUsername()],
                'updateUser' => [
                    'id' => $answer->getUpdateUser()->getId(), // ActorTrait
                    'username' => $answer->getUpdateUser()->getUsername()]
            ];
        // ManyToOne user, question
    }

    public function deserialize(array $data)
    {
        $answer = new Answer();
        $answer->setRef($data['ref']);
        $answer->setContent($data['content']);
        $answer->setStatus($data['status']);
        $answer->setIsValid($data['isValid']);
        $answer->setIsDeleted($data['isDeleted']);
        // ManyToOne User
        if (isset($data['createUser']) && isset($data['createUser']['id'])) {
            $createUser = $this->userRepository->findOneBy([
                'id' => $data['createUser']['id']
            ]);

            if (!empty($createUser)) {
                $answer->setCreateUser($createUser);
            }
        }
        // ManyToOne User
        if (isset($data['updateUser']) && isset($data['updateUser']['id'])) {
            $updateUser = $this->userRepository->findOneBy([
                'id' => $data['updateUser']['id']
            ]);

            if (!empty($updateUser)) {
                $answer->setUpdateUser($updateUser);
            }
        }

        return $answer;
    }
}
