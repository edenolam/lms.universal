<?php

namespace App\Model\LovManagement;

use App\Traits\ActorTrait;
use App\Traits\DateTrait;
use App\Traits\IsValidTrait;
use App\Traits\RevisionTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * Lov
 */
abstract class Lov
{
    use ActorTrait;
    use RevisionTrait;
    use DateTrait;
    use IsValidTrait;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="keywords", type="string", length=255, nullable=true)
     */
    protected $keywords;

    /**
     * @var int
     *
     * @ORM\Column(name="sort", type="integer", nullable=true)
     */
    protected $sort;

    /**
     * @var string
     *
     * @ORM\Column(name="conditional", type="string", nullable=true)
     */
    protected $conditional;

    public function __construct()
    {
        $this->sort = 0;
        $this->isValid = 1;
        $this->revision = -1;
    }

    public function __toString()
    {
        return $this->title;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return AnswerTypes
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return AnswerTypes
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set sort
     *
     * @param int $sort
     * @return LovType
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort
     *
     * @return int
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set keywords
     *
     * @param int $keywords
     * @return LovType
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;

        return $this;
    }

    /**
     * Get keywords
     *
     * @return int
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * Get conditional
     * @return string
     */
    public function getConditional()
    {
        return $this->conditional;
    }

    /**
     * Set conditional
     *
     * @param string $conditional
     */
    public function setConditional($conditional)
    {
        $this->conditional = $conditional;
    }
}
