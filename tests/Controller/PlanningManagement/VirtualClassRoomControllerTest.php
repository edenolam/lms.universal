<?php

namespace App\Tests\Controller\PlanningManagement;

use App\Entity\UserManagement\User;
use App\Tests\Controller\ControllerBaseTest;

class VirtualClassRoomControllerTest extends ControllerBaseTest
{

    /**
     * @group responsable
     */
    public function testIndex()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_RESPONSABLE_FORMATION);
        $this->request($client, '/admin/planningManagement/virtualClassRoom/');

        $response = $client->getResponse();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasAddClassVirtuelleButton($client);
    }    
}