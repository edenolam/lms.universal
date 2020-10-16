<?php

namespace App\Tests\Controller\UserFrontManagement;

use App\Entity\UserManagement\User;
use App\Tests\Controller\ControllerBaseTest;

class UserFormationControllerTest extends ControllerBaseTest
{
    /**
     * @group apprenant
     */
    public function testViewMonSuivi()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_APPRENANT);
        $this->request($client, '/apprenant/formation');

        $response = $client->getResponse();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $response->getContent();
        $this->assertContains('en cours', $content);
        $this->assertContains('terminée(s)', $content);
    }
}