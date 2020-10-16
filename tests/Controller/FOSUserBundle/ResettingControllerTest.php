<?php

namespace App\Tests\Controller\FOSUserBundle;

use App\Entity\UserManagement\User;
use App\DataFixtures\AppFixtures;
use App\Tests\Controller\ControllerBaseTest;
use Symfony\Component\Translation\TranslatorInterface;

class ResttingControllerTest extends ControllerBaseTest
{
    /**
     * @group resetting
     */
    public function testRequestAction()
    {
        $client = static::createClient();
        /** @var EntityManager $em */
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');

        // enables the profiler for the next request (it does nothing if the profiler is not available)
        $client->followRedirects();

        $crawler = $client->request('GET', '/fr/resetting/request');
        
        $form = $crawler->filter('form[action="/fr/resetting/send-email"]')->form();
        
        $this->assertTrue($form->has('username'));

        $email = "info@universalmedica.com";
        
        $user = $em->getRepository('App\Entity\UserManagement\User')->findOneBy(['email' => $email]); 
        if ($user) { 
            $user->setConfirmationToken(null);
            $user->setPasswordRequestedAt(null);
            $em->persist($user);
            $em->flush();

            $client->submit($form, [
                'username' => $email
            ]);
            
            $this->assertFalse($client->getResponse()->isRedirect());
            $this->assertTrue($client->getResponse()->isSuccessful());

            $this->assertContains(
                'Un e-mail a été envoyé. Il contient un lien sur lequel il vous faudra cliquer pour réinitialiser votre mot de passe. Ce lien est actif 24h. Si vous ne recevez pas un email, vérifiez votre dossier spam ou essayez à nouveau.',
                $client->getResponse()->getContent()
            );

            $mailCollector = $client->getProfile()->getCollector('swiftmailer');
            // checks that an email was sent
            $this->assertSame(1, $mailCollector->getMessageCount());

            $collectedMessages = $mailCollector->getMessages();
            $message = $collectedMessages[0];

            // Asserting email data
            $this->assertInstanceOf('Swift_Message', $message);        
            $this->assertSame('Réinitialisation de votre mot de passe', $message->getSubject()); // resetting.email.subject
            $this->assertSame("info@universalmedica.com", key($message->getFrom()));
            $this->assertSame($email, key($message->getTo()));
            $this->assertContains(
                'Pour réinitialiser votre mot de passe, merci de vous rendre sur',
                $message->getBody()
            );
        }
    }
     
}