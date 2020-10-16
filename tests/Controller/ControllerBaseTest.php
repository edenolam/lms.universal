<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\UserManagement\User;
use App\Tests\KernelTestTrait;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * ControllerBaseTest adds some useful functions for writing integration tests.
 */
abstract class ControllerBaseTest extends WebTestCase
{
    use KernelTestTrait;

    public const DEFAULT_LANGUAGE = 'fr';

    /**
     * @param string $role
     * @return Client
     */
    protected function getClientForAuthenticatedUser(string $role = User::ROLE_APPRENANT)
    {
        switch ($role) {
            case User::ROLE_SUPER_ADMIN:
                $client = self::createClient([], [
                    'PHP_AUTH_USER' => AppFixtures::SUPER_ADMIN_USERNAME,
                    'PHP_AUTH_PW' => AppFixtures::SUPER_ADMIN_PASSWORD,
                ]);
                break;

            case User::ROLE_ADMIN:
                $client = self::createClient([], [
                    'PHP_AUTH_USER' => AppFixtures::ADMIN_USERNAME,
                    'PHP_AUTH_PW' => AppFixtures::ADMIN_PASSWORD,
                ]);
                break;

            case User::ROLE_TUTEUR:
                $client = self::createClient([], [
                    'PHP_AUTH_USER' => AppFixtures::TUTEUR_USERNAME,
                    'PHP_AUTH_PW' => AppFixtures::TUTEUR_PASSWORD,
                ]);
                break;

            case User::ROLE_CONCEPTEUR:
                $client = self::createClient([], [
                    'PHP_AUTH_USER' => AppFixtures::CONCEPTEUR_USERNAME,
                    'PHP_AUTH_PW' => AppFixtures::CONCEPTEUR_PASSWORD,
                ]);
                break;

            case User::ROLE_RESPONSABLE_FORMATION:
                $client = self::createClient([], [
                    'PHP_AUTH_USER' => AppFixtures::RESPONSABLE_USERNAME,
                    'PHP_AUTH_PW' => AppFixtures::RESPONSABLE_PASSWORD,
                ]);
                break;
                                
            case User::ROLE_APPRENANT:
                $client = self::createClient([], [
                    'PHP_AUTH_USER' => AppFixtures::APPRENANT_USERNAME,
                    'PHP_AUTH_PW' => AppFixtures::APPRENANT_PASSWORD,
                ]);
                break;

            default:
                $client = null;
                break;
        }

        return $client;
    }

    /**
     * @param string $url
     * @return string
     */
    protected function createUrl($url)
    {
        return '/' . self::DEFAULT_LANGUAGE . '/' . ltrim($url, '/');
    }

    /**
     * @param Client $client
     * @param string $url
     * @param string $method
     * @param array $parameters
     * @param string $content
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function request(Client $client, string $url, $method = 'GET', array $parameters = [], string $content = null)
    {
        return $client->request($method, $this->createUrl($url), $parameters, [], [], $content);
    }

    /**
     * Add ClassVirtuelle Button
     * @param Client $client
     */
    protected function assertHasAddClassVirtuelleButton(Client $client)
    {
        self::assertContains('<a href="/fr/admin/planningManagement/virtualClassRoom/new"', $client->getResponse()->getContent());
    }

    /**
     * add session button
     * @param Client $client
     */
    protected function assertHasAddSessionButton(Client $client)
    {
        self::assertContains('<a href="/fr/admin/planningManagement/session/create"', $client->getResponse()->getContent());
    } 

    /**
     * Dupliquer Module button
     * @param Client $client
     */
    protected function assertHasDupliquerModuleButton(Client $client)
    {
        self::assertContains('<a href="/fr/admin/formationManagement/module/dupliquer"', $client->getResponse()->getContent());
    } 

    /**
     * Sondage filtre form 
     * @param Client $client
     */
    protected function assertHasSondageFilterForm(Client $client)
    {
        self::assertContains('<form action="/fr/bilan/sondage"', $client->getResponse()->getContent());
    }  

    /**
     * Mon Tableau Bord Menu
     * @param Client $client
     */
    protected function assertHasMonTableauBordMenu(Client $client)
    {
        self::assertContains('<a href="/fr/user/dashboard" class="lms-nav-link">', $client->getResponse()->getContent());
    }

    /**
     * ClasseVirtuelle Menu
     * @param Client $client
     */
    protected function assertHasClasseVirtuelleMenu(Client $client)
    {
        self::assertContains('<a href="/fr/user/virtual_class_room" class="lms-nav-link"', $client->getResponse()->getContent());
    }

    /**
     * Bilan Formation Menu
     * @param Client $client
     */
    protected function assertHasBilanFormationMenu(Client $client)
    {
        self::assertContains('<a href="/fr/bilan/list" class="lms-nav-link">', $client->getResponse()->getContent());
    }

    /**
     * Analyse Sondages Menu
     * @param Client $client
     */
    protected function assertHasAnalyseSondagesMenu(Client $client)
    {
        self::assertContains('<a href="/fr/bilan/sondage" class="lms-nav-link">', $client->getResponse()->getContent());
    }

    /**
     * Gestion ClassesVirtuelles Menu
     * @param Client $client
     */
    protected function assertHasGestionClassesVirtuellesMenu(Client $client)
    {
        self::assertContains('<a class="lms-nav-link" href="/fr/admin/planningManagement/virtualClassRoom/"', $client->getResponse()->getContent());
    }

    /**
     * Gestion Session Menu
     * @param Client $client
     */
    protected function assertHasGestionSessionMenu(Client $client)
    {
        self::assertContains('<a class="lms-nav-link" href="/fr/admin/planningManagement/session/list"', $client->getResponse()->getContent());
    }

    /**
     * Gestion Module Menu
     * @param Client $client
     */
    protected function assertHasGestionModuleMenu(Client $client)
    {
        self::assertContains('<a class="lms-nav-link" href="/fr/admin/formationManagement/module/list"', $client->getResponse()->getContent());
    } 

}
