<?php

namespace App\Tests\Controller\TestManagement;

use App\Entity\UserManagement\User;
use App\Tests\Controller\ControllerBaseTest;

class TestControllerTest extends ControllerBaseTest
{
	/**
	 * @group concepteur
	 */
    public function testList()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_CONCEPTEUR);
        $this->request($client, '/admin/TestManagement/test/list');

        $response = $client->getResponse();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $response->getContent();
        $this->assertContains('<table class="table card-table table-vcenter text-nowrap">', $content);
    }
}