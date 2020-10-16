<?php

namespace App\Traits;

/**
 * RevisionTrait
 *
 * @ORM\HasLifecycleCallbacks()
 * @author Unkown <info@universalmedica.com>
 */
trait RevisionTrait
{
    /**
     * @var integer
     *
     * @ORM\Column(name="revision", type="integer", nullable=false)
     */
    protected $revision;

    /**
     * Set revision
     *
     * @param int $revision
     * @return Self
     */
    public function setRevision(int $revision)
    {
        $this->revision = $revision;

        return $this;
    }

    /**
     * Get revision
     *
     * @return integer
     */
    public function getRevision(): ?int
    {
        return $this->revision;
    }
}
