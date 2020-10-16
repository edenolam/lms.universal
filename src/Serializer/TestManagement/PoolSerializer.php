<?php

namespace App\Serializer\TestManagement;

use App\Entity\TestManagement\Pool;
use App\Repository\UserManagement\UserRepository;

/**
 * PoolSerializer
 *
 * @author null
 */
class PoolSerializer
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @param Pool $pool
     *
     * @return array
     */
    public function serialize(Pool $pool)
    {
        // use ActorTrait, RevisionTrait, DateTrait, IsValidTrait;
        return [
                'id' => $pool->getId(),
                'ref' => $pool->getRef(),
                'title' => $pool->getTitle(),
                'nbRequQuestions' => $pool->getNbRequQuestions(),
                'revision' => $pool->getRevision(), // RevisionTrait
                'isValid' => $pool->getIsValid(), // IsValidTrait
                'createDate' => $pool->getCreateDate()->format('d/m/Y H:i'), // DateTrait
                'updateDate' => $pool->getUpdateDate()->format('d/m/Y H:i'),
                'createUser' => [ // ManyToOne
                    'id' => $pool->getCreateUser()->getId(), // ActorTrait
                    'username' => $pool->getCreateUser()->getUsername()],
                'updateUser' => [
                    'id' => $pool->getUpdateUser()->getId(), // ActorTrait
                    'username' => $pool->getUpdateUser()->getUsername()]
            ];
        // ManyToOne test
        // OneToMany questions
        // ManyToOne User
    }

    public function deserialize(array $data)
    {
        $pool = new Pool();
        $pool->setRef($data['ref']);
        $pool->setTitle($data['title']);
        $pool->setNbRequQuestions($data['nbRequQuestions']);
        $pool->setIsValid($data['isValid']);
        // ManyToOne User
        if (isset($data['createUser']) && isset($data['createUser']['id'])) {
            $createUser = $this->userRepository->findOneBy([
                'id' => $data['createUser']['id']
            ]);

            if (!empty($createUser)) {
                $pool->setCreateUser($createUser);
            }
        }
        // ManyToOne User
        if (isset($data['updateUser']) && isset($data['updateUser']['id'])) {
            $updateUser = $this->userRepository->findOneBy([
                'id' => $data['updateUser']['id']
            ]);

            if (!empty($updateUser)) {
                $pool->setUpdateUser($updateUser);
            }
        }

        return $pool;
    }
}
