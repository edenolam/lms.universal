<?php

namespace App\Tests\Controller\FormationManagement;

use App\Entity\UserManagement\User;
use App\Tests\Controller\ControllerBaseTest;

class ModuleControllerTest extends ControllerBaseTest
{
	/**
	 * @group concepteur
	 */
    public function testListConcepteur()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_CONCEPTEUR);
        $this->request($client, '/admin/formationManagement/module/list');

        $response = $client->getResponse();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $response->getContent();
        $this->assertContains('Créer un module', $content);
    }

    /**
     * @group responsable
     */
    public function testListResponsable()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_RESPONSABLE_FORMATION);
        $this->request($client, '/admin/formationManagement/module/list');

        $response = $client->getResponse();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasDupliquerModuleButton($client);
    }     
}