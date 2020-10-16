<?php

namespace App\EventListener\ServiceManagement;

use App\Event\ServiceManagement\AuditTrailEvent;
use App\Manager\AuditManager;

class AuditTrailListener
{
    private $auditManager;

    public function __construct(AuditManager $auditManager)
    {
        $this->auditManager = $auditManager;
    }

    public function onAuditTrailEvent(AuditTrailEvent $auditTrailEvent)
    {
        $this->auditManager->generateAudit($auditTrailEvent->getOldEntity(), $auditTrailEvent->getNewEntity(), $auditTrailEvent->getAction(), $auditTrailEvent->getUser());
    }
}
