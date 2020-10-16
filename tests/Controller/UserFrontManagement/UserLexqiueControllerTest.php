<?php

namespace App\Tests\Controller\UserFrontManagement;

use App\Entity\UserManagement\User;
use App\Tests\Controller\ControllerBaseTest;

class UserLexiqueControllerTest extends ControllerBaseTest
{
	/**
	 * @group apprenant
	 */
    public function testList()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_APPRENANT);
        $this->request($client, '/user/lexique');

        $response = $client->getResponse();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $response->getContent();
        $this->assertContains('id="lexique"', $content);
    }
}