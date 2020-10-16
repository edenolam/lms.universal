<?php

namespace App\Traits;

/**
 * IsValidTrait
 *
 * @ORM\HasLifecycleCallbacks()
 * @author Unkown <info@universalmedica.com>
 */
trait IsValidTrait
{
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_valid", type="boolean", nullable=false)
     */
    protected $isValid = true;

    /**
     * Set isValid
     *
     * @param bool $isValid
     * @return Self
     */
    public function setIsValid(int $isValid)
    {
        $this->isValid = $isValid;

        return $this;
    }

    /**
     * Get isValid
     *
     * @return boolean
     */
    public function getIsValid(): ?int
    {
        return $this->isValid;
    }
}
