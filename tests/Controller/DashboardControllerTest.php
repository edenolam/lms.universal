<?php

namespace App\Tests\Controller;

use App\Entity\UserManagement\User;

class DashboardControllerTest extends ControllerBaseTest
{
    /**
     * @group apprenant
     */
    public function testApprenantDashboard()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_APPRENANT);
        $this->request($client, '/user/dashboard');

        $response = $client->getResponse();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasMonTableauBordMenu($client);
    }

    /**
     * @group tuteur
     */
    public function testTuteurDashboard()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TUTEUR);
        $this->request($client, '/user/dashboard');

        $response = $client->getResponse();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasMonTableauBordMenu($client);
    }    

    /**
     * @group concepteur
     */
    public function testConcepteurDashboard()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_CONCEPTEUR);
        $this->request($client, '/user/dashboard');

        $response = $client->getResponse();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasMonTableauBordMenu($client);
    }      

    /**
     * @group responsable
     */
    public function testResponsableFormationDashboard()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_RESPONSABLE_FORMATION);
        $this->request($client, '/user/dashboard');

        $response = $client->getResponse();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasMonTableauBordMenu($client);
        $this->assertHasClasseVirtuelleMenu($client);
        $this->assertHasBilanFormationMenu($client);
        $this->assertHasAnalyseSondagesMenu($client);
        $this->assertHasGestionClassesVirtuellesMenu($client);
        $this->assertHasGestionSessionMenu($client);
        $this->assertHasGestionModuleMenu($client);
    }      
}