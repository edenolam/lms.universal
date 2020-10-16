<?php

namespace App\Traits;

/**
 * IsArchivedTrait
 *
 * @ORM\HasLifecycleCallbacks()
 * @author null
 */
trait IsArchivedTrait
{
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_archived", type="boolean", nullable=false)
     */
    protected $isArchived = false;

    /**
     * Set isArchived
     *
     * @param bool $isArchived
     * @return Self
     */
    public function setIsArchived(int $isArchived)
    {
        $this->isArchived = $isArchived;

        return $this;
    }

    /**
     * Get isArchived
     *
     * @return boolean
     */
    public function getIsArchived(): ?int
    {
        return $this->isArchived;
    }
}
