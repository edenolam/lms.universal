<?php

namespace App\Event\ServiceManagement;

use App\Entity\UserManagement\User;
use Symfony\Component\EventDispatcher\Event;

class AuditTrailEvent extends Event
{
    public const NAME = 'audit.event';

    protected $oldEntity;

    protected $newEntity;

    /**
     * @var String
     */
    protected $action;

    /**
     * @var User
     */
    protected $user;

    /**
     * RegistrationUserEvent constructor.
     * @param  $oldEntity
     * @param  $newEntity
     * @param Text $action
     * @param Entity $newEntity
     */
    public function __construct($oldEntity = null, $newEntity, String $action, $user)
    {
        $this->oldEntity = $oldEntity;
        $this->newEntity = $newEntity;
        $this->action = $action;
        $this->user = $user;
    }

    /**
     * @return Entity
     */
    public function getOldEntity()
    {
        return $this->oldEntity;
    }

    /**
     * @return Entity
     */
    public function getNewEntity()
    {
        return $this->newEntity;
    }

    /**
     * @return String
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
