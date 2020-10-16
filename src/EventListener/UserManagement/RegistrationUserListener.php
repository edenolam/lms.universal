<?php

namespace App\EventListener\UserManagement;

use App\Event\UserManagement\RegistrationUserEvent;
use App\Manager\AuditManager;
use App\Service\MailService;

/**
 * RegistrationUserListener
 *
 * @author info@universalmedica.com
 */
class RegistrationUserListener
{
    /**
     * @var MailService
     */
    private $mailService;
    private $auditManager;

    /**
     * RegistrationUserListener constructor.
     * @param MailService $mailService
     */
    public function __construct(MailService $mailService, AuditManager $auditManager)
    {
        $this->mailService = $mailService;
        $this->auditManager = $auditManager;
    }

    /**
     * @param RegistrationUserEvent $event
     */
    public function onRegistrationUserEvent(RegistrationUserEvent $event): void
    {
        $this->mailService->sendMailRegistrationUser($event->getUser(), $event->getPassword());
        $this->auditManager->generateAudit(null, $event->getUser(), 'add', $event->getCreator());
    }
}
