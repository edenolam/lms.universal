<?php

namespace App\Tests\Controller\PlanningManagement;

use App\Entity\UserManagement\User;
use App\Tests\Controller\ControllerBaseTest;

class SeesionControllerTest extends ControllerBaseTest
{

    /**
     * @group responsable
     */
    public function testList()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_RESPONSABLE_FORMATION);
        $this->request($client, '/admin/planningManagement/session/list');

        $response = $client->getResponse();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasAddSessionButton($client);
    }    
}