<?php

namespace App\Event\ServiceManagement;

use App\Entity\UserManagement\User;
use Symfony\Component\EventDispatcher\Event;

class AuditEvent extends Event
{
    public const NAME = 'add.audit.event';

    protected $oldEntity;

    protected $newEntity;

    /**
     * @var String
     */
    protected $action;

    protected $em;

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
     * @param User $user
     */
    public function __construct($oldEntity = null, $newEntity, String $action, $em, $user)
    {
        $this->oldEntity = $oldEntity;
        $this->newEntity = $newEntity;
        $this->action = $action;
        $this->em = $em;
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

    public function getEm()
    {
        return $this->em;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
