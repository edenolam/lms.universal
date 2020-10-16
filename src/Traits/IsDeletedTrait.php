<?php

namespace App\Traits;

/**
 * IsDeletedTrait
 *
 * @ORM\HasLifecycleCallbacks()
 * @author null
 */
trait IsDeletedTrait
{
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_deleted", type="boolean", nullable=false)
     */
    protected $isDeleted = false;

    /**
     * Set isDeleted
     *
     * @param bool $isDeleted
     * @return Self
     */
    public function setIsDeleted(int $isDeleted)
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    /**
     * Get isDeleted
     *
     * @return boolean
     */
    public function getIsDeleted(): ?int
    {
        return $this->isDeleted;
    }
}
