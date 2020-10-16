<?php

namespace App\Traits;

use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * FileUpload Trait
 * @Vich\Uploadable
 */
trait FileTrait
{
    /**
     * @ORM\Column(name="uri", type="string", nullable=true)
     */
    protected $uri;

    /**
     * @Vich\UploadableField(mapping="uploads", fileNameProperty="uri")
     * @var File
     */
    protected $file;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="upload_at", type="datetime", nullable=true)
     */
    protected $uploadAt;

    /**
     * @return string uri
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param File $file
     *                   update $updatedAt
     */
    public function setFile(File $file = null)
    {
        $this->file = $file;

        if ($file) {
            $this->uploadAt = new \DateTime('now');
        }
    }

    /**
     * Set uploadAt
     */
    public function setUploadAt()
    {
        $this->uploadAt = new \DateTime();

        return $this;
    }

    /**
     * Get uploadAt
     *
     * @return \DateTime
     */
    public function getUploadAt(): ? \Datetime
    {
        return $this->uploadAt;
    }
}
