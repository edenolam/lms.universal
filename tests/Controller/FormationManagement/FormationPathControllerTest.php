<?php

namespace App\Tests\Controller\FormationManagement;

use App\Entity\UserManagement\User;
use App\Tests\Controller\ControllerBaseTest;

class FormationPathControllerTest extends ControllerBaseTest
{
	/**
	 * @group concepteur
	 */
    public function testList()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_CONCEPTEUR);
        $this->request($client, '/admin/formationManagement/formationPath/list');

        $response = $client->getResponse();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $response->getContent();
        $this->assertContains('Créer une formation', $content);
    }
}