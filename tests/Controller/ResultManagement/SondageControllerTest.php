<?php

namespace App\Tests\Controller\ResultManagement;

use App\Entity\UserManagement\User;
use App\Tests\Controller\ControllerBaseTest;

class SondageControllerTest extends ControllerBaseTest
{
	/**
	 * @group tuteur
	 */
    public function testSondageTuteur()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TUTEUR);
        $this->request($client, '/bilan/sondage');

        $response = $client->getResponse();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasSondageFilterForm($client);
    }

    /**
     * @group responsable
     */
    public function testSondageResponsable()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_RESPONSABLE_FORMATION);
        $this->request($client, '/bilan/sondage');

        $response = $client->getResponse();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasSondageFilterForm($client);
    }    
}