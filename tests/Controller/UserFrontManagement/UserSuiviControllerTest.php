<?php

namespace App\Tests\Controller\UserFrontManagement;

use App\Entity\UserManagement\User;
use App\Tests\Controller\ControllerBaseTest;

class UserSuiviControllerTest extends ControllerBaseTest
{
    /**
     * @group apprenant
     */
    public function testViewMonSuivi()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_APPRENANT);
        $this->request($client, '/apprenant/');

        $response = $client->getResponse();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $response->getContent();
        $this->assertContains('suivi-formation active', $content);
        $this->assertContains('suivi-formation past', $content);
    }
}