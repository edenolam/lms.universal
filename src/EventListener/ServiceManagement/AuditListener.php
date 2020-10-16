<?php

namespace App\EventListener\ServiceManagement;

use App\Controller\ServiceManagement\AuditController;
use App\Event\ServiceManagement\AuditEvent;

class AuditListener
{
    private $auditController;
    //protected $container;

    public function __construct(AuditController $auditController)
    {
        $this->auditController = $auditController;
        //$this->container = $container;
    }

    public function onAuditEvent(AuditEvent $AuditEvent)
    {
        $this->auditController->generateAudit($AuditEvent->getOldEntity(), $AuditEvent->getNewEntity(), $AuditEvent->getAction(), $AuditEvent->getEm(), $AuditEvent->getUser());
    }
}
