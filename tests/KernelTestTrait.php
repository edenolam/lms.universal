<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\Entity\UserManagement\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\EntityManager;

/**
 * A trait to be used in all tests that extend the KernelTestCase.
 */
trait KernelTestTrait
{
    /**
     * @param EntityManager $em
     * @param Fixture $fixture
     */
    protected function importFixture(EntityManager $em, Fixture $fixture)
    {
        $loader = new Loader();
        $loader->addFixture($fixture);

        $executor = new ORMExecutor($em, null);
        $executor->execute($loader->getFixtures(), true);
    }

    /**
     * @param EntityManager $em
     * @param string $username
     * @return User|null
     */
    protected function getUserByName(EntityManager $em, string $username)
    {
        return $em->getRepository(User::class)->findOneBy(['username' => $username]);
    }

    /**
     * @param EntityManager $em
     * @param string $role
     * @return User|null
     */
    protected function getUserByRole(EntityManager $em, string $role = User::ROLE_USER)
    {
        $name = null;

        switch ($role) {
            case User::ROLE_SUPER_ADMIN:
                $name = AppFixtures::SUPER_ADMIN_NOM;
                break;

            case User::ROLE_ADMIN:
                $name = AppFixtures::ADMIN_NOM;
                break;

            case User::ROLE_TUTEUR:
                $name = AppFixtures::TUTEUR_NOM;
                break;

            case User::ROLE_CONCEPTEUR:
                $name = AppFixtures::CONCEPTEUR_NOM;
                break;

            case User::ROLE_RESPONSABLE_FORMATION:
                $name = AppFixtures::RESPONSABLE_NOM;
                break;            

            case User::ROLE_APPRENANT:
                $name = AppFixtures::APPRENANT_NOM;
                break;

            default:
                return null;
        }

        return $this->getUserByName($em, $name);
    }
}
