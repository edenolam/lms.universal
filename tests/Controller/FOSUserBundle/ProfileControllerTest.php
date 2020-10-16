<?php

namespace App\Tests\Controller\FOSUserBundle;

use App\Entity\UserManagement\User;
use App\DataFixtures\AppFixtures;
use App\Tests\Controller\ControllerBaseTest;

class ProfileControllerTest extends ControllerBaseTest
{
    public function getProfileData()
    {
        return [
            [User::ROLE_APPRENANT, AppFixtures::APPRENANT_EMAIL],
            [User::ROLE_TUTEUR, AppFixtures::TUTEUR_EMAIL],
            [User::ROLE_CONCEPTEUR, AppFixtures::CONCEPTEUR_EMAIL],
            [User::ROLE_RESPONSABLE_FORMATION, AppFixtures::RESPONSABLE_EMAIL],
            [User::ROLE_ADMIN, AppFixtures::ADMIN_EMAIL],
            [User::ROLE_SUPER_ADMIN, AppFixtures::SUPER_ADMIN_EMAIL],

        ];
    }

    /**
     * @dataProvider getProfileData
     * @group profile
     */
    public function testShowAction($role, $email)
    {
        $client = $this->getClientForAuthenticatedUser($role);
        $this->request($client, '/profile/');

        $response = $client->getResponse();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $response->getContent();
        $this->assertContains($email, $content);
    }
     
}