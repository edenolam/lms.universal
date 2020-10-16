<?php

namespace App\Serializer\TestManagement;

use App\Entity\TestManagement\Test;
use App\Repository\LovManagement\TypeTestRepository;
use App\Repository\TestManagement\TestRepository;
use App\Repository\UserManagement\UserRepository;

/**
 * TestSerializer
 *
 * @author null
 */
class TestSerializer
{
    private $typeTestRepository;
    private $testRepository;
    private $userRepository;

    public function __construct(TypeTestRepository $typeTestRepository, TestRepository $testRepository, UserRepository $userRepository)
    {
        $this->typeTestRepository = $typeTestRepository;
        $this->testRepository = $testRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * @param Test $test
     *
     * @return array
     */
    public function serialize(Test $test)
    {
        // use ActorTrait, RevisionTrait, DateTrait;
        return [
                'id' => $test->getId(),
                'isTestCommune' => $test->getIsTestCommune(),
                'isTestPresentiel' => $test->getIsTestPresentiel(),
                'ref' => $test->getRef(),
                'slug' => $test->getSlug(),
                'title' => $test->getTitle(),
                'typeTest' => [  // ManyToOne
                    'id' => $test->getTypeTest()->getId(),
                    'title' => $test->getTypeTest()->getTitle(),
                    'conditional' => $test->getTypeTest()->getConditional()],
                'testCommune' => ($test->getTestCommune()) ? [ // ManyToOne
                    'id' => $test->getTestCommune()->getId(),
                    'title' => $test->getTestCommune()->getTitle(),
                    'conditional' => $test->getTypeTest()->getConditional()] : null,
                'theoricalDuration' => $test->getTheoricalDuration(),
                'totalRequiredQuestion' => $test->getTotalRequiredQuestion(),
                'revision' => $test->getRevision(), // RevisionTrait
                'createDate' => $test->getCreateDate()->format('d/m/Y H:i'), // DateTrait
                'updateDate' => $test->getUpdateDate()->format('d/m/Y H:i'),
                'createUser' => [ // ManyToOne
                    'id' => $test->getCreateUser()->getId(), // ActorTrait
                    'username' => $test->getCreateUser()->getUsername()],
                'updateUser' => [
                    'id' => $test->getUpdateUser()->getId(), // ActorTrait
                    'username' => $test->getUpdateUser()->getUsername()]
            ];
        // OneToMany moduleTests
        // ManyToOne typeTest
        // ManyToOne testCommune
        // OneToMany questions
        // OneToMany pools
        // ManyToOne User
    }

    public function deserialize(array $data)
    {
        $test = new Test();
        $test->setIsTestCommune($data['isTestCommune']);
        $test->setIsTestPresentiel($data['isTestPresentiel']);
        $test->setRef($data['ref']);
        $test->setTitle($data['title']);
        $test->setTheoricalDuration($data['theoricalDuration']);
       // $test->setTotalRequiredQuestion($data['totalRequiredQuestion']);
        // ManyToOne typeTest = ModuleType
        if (isset($data['typeTest']) && isset($data['typeTest']['id'])) {
            $type = $this->typeTestRepository->findOneBy([
                'id' => $data['typeTest']['id']
            ]);

            if (!empty($type)) {
                $test->setTypeTest($type);
            }
        }
        // ManyToOne testCommune
        if (isset($data['testCommune']) && isset($data['testCommune']['id'])) {
            $test_commune = $this->testRepository->findOneBy([
                'parent' => $data['testCommune']['id']
            ]);

            if (!empty($test_commune)) {
                $test->setTestCommune($test_commune);
            }
        }
        // ManyToOne User
        if (isset($data['createUser']) && isset($data['createUser']['id'])) {
            $createUser = $this->userRepository->findOneBy([
                'id' => $data['createUser']['id']
            ]);

            if (!empty($createUser)) {
                $test->setCreateUser($createUser);
            }
        }
        // ManyToOne User
        if (isset($data['updateUser']) && isset($data['updateUser']['id'])) {
            $updateUser = $this->userRepository->findOneBy([
                'id' => $data['updateUser']['id']
            ]);

            if (!empty($updateUser)) {
                $test->setUpdateUser($updateUser);
            }
        }

        return $test;
    }
}
