<?php

namespace App\Tests\Controller\UserManagement;

use App\Tests\Controller\ControllerBaseTest;

/**
 * This test makes sure the login and registration work as expected.
 * The logic is located in the FOSUserBundle and already tested, but we use a different layout.
 *
 */
class SecurityControllerTest extends ControllerBaseTest
{
    /**
     * @group login
     */
    public function testLoginPageIsRendered()
    {
        $client = self::createClient();
        $this->request($client, '/login');

        $response = $client->getResponse();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $content = $response->getContent();
        $this->assertContains('action="/fr/login"', $content);
        $this->assertContains('id="username" name="_username"', $content);
        $this->assertContains('id="password" name="_password"', $content);
        $this->assertContains('id="remember_me" name="_remember_me"', $content);
        $this->assertContains('">Connexion</button>', $content);
        $this->assertContains('<input type="hidden" name="_csrf_token" value="', $content);      
    }

}