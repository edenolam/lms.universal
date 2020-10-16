<?php

namespace App\Tests\Controller\ResultManagement;

use App\Entity\UserManagement\User;
use App\Tests\Controller\ControllerBaseTest;

class BilanControllerTest extends ControllerBaseTest
{
	/**
	 * @group tuteur
	 */
    public function testList()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TUTEUR);
        $this->request($client, '/bilan/list');

        $response = $client->getResponse();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $response->getContent();
        $this->assertContains('action="/fr/bilan/list"', $content);
    }
}