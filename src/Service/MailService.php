<?php

namespace App\Service;

use App\Entity\UserManagement\LoggedMessage;
use App\Entity\UserManagement\User;
use Doctrine\Common\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * Mail Service
 *
 * @author info@universalmedica.com
 */
class MailService
{
    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;



    /**
     * MailService constructor.
     * @param \Twig_Environment $twig
     * @param \Swift_Mailer $mailer
     * @param LoggerInterface $logger
     */
    public function __construct(\Twig_Environment $twig, \Swift_Mailer $mailer, LoggerInterface $logger, ManagerRegistry $doctrine)
    {
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->doctrine = $doctrine;
    }

     /**
     * @param User $user
     */
    public function sendAMail($from, $to, $purpose, $body)
    {
        $message = (new \Swift_Message($purpose))
            ->setFrom($from)
            ->setTo($to)
            //->setCc($cc)
            ->setBody($body, 'text/html')
            ->setCharset('utf-8');

        try {
            $reponse = $this->mailer->send($message);
            $this->log($message);
        } catch (\Swift_TransportException $e) {
            $this->logger->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->addError($e->getMessage());
        }
    }

    /**
     * @param User $user
     */
    public function sendMailRegistrationUser(User $user, $password)
    {
        $email = $user->getEmail();
        $name = $user->getFirstname() . ' ' . $user->getLastname();
        $pseudo = $user->getUsername();
        $password = $password;

        $body = $this->renderTemplate($name, $email);
        $bodyData = $this->renderTemplate2($name, $pseudo, $password);

        $message = (new \Swift_Message('Création de votre compte sur Euro-Academy'))
            ->setFrom(['info@universalmedica.com' => 'universalmedica'])
            ->setTo($email)
            ->setBody($body, 'text/html')
            ->setCharset('utf-8')
        ;

        $message2 = (new \Swift_Message('Vos codes d’accès  sur Euro-Academy'))
            ->setFrom(['info@universalmedica.com' => 'universalmedica'])
            ->setTo($email)
            ->setBody($bodyData, 'text/html')
            ->setCharset('utf-8')
        ;

        try {
            $reponse = $this->mailer->send($message);
            $this->log($message);
            //envoi du deuxieme message
            $response = $this->mailer->send($message2);
            $this->log($message2);
        } catch (\Swift_TransportException $e) {
            $this->logger->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->addError($e->getMessage());
        }
    }

    /**
     * @param $name
     * @param $email
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    protected function renderTemplate($name, $email): string
    {
        return $this->twig->render(
            'UserManagement/User/emails/registration.html.twig',
            [
                'name' => $name,
                'email' => $email
            ]
        );
    }

    /**
     * @param $name
     * @param $email
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    protected function renderTemplate2($name, $pseudo, $password): string
    {
        return $this->twig->render(
            'UserManagement/User/emails/registration2.html.twig',
            [
                'name' => $name,
                'pseudo' => $pseudo,
                'password' => $password
            ]
        );
    }

    /**
     * @param $message
     * @param int $result
     * @param array $failures
     */
    public function log($message, $result = 1, $failures = [])
    {
        $loggedMessage = new LoggedMessage();
        $loggedMessage->setMessage($message);
        $loggedMessage->setResult($result);
        $loggedMessage->setFailedRecipients($failures);

        $em = $this->doctrine->getManagerForClass(LoggedMessage::class);

        // application should not crash when logging fails
        try {
            $em->persist($loggedMessage);
            $em->flush($loggedMessage);
        } catch (\Exception $e) {
            $error = 'Logging sent message with \SwiftmailerLoggerBundle\Logger\EntityLogger failed: ' .
                $e->getMessage();
            $this->logger->addError($error);
        }
    }
}
