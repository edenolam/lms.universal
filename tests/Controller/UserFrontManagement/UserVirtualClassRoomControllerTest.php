<?php

namespace App\Tests\Controller\UserFrontManagement;

use App\Entity\UserManagement\User;
use App\Tests\Controller\ControllerBaseTest;

class UserVirtualClassRoomControllerTest extends ControllerBaseTest
{
    /**
     * @group apprenant
     * @group tuteur
     */
    public function testVirtualClassRoom()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_APPRENANT);
        $this->request($client, '/user/virtual_class_room');

        $response = $client->getResponse();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $response->getContent();
        $this->assertContains('En cours', $content);
        $this->assertContains('A venir', $content);
        $this->assertContains('Terminée', $content);
    }
}