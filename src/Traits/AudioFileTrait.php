<?php

namespace App\Traits;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
/**
 * FileUpload Trait
 * @Vich\Uploadable
 */
trait AudioFileTrait
{
    /**
     * @ORM\Column(name="uri_audio", type="string", nullable=true)
     */
    protected $uriAudio;

    /**
     * @Vich\UploadableField(mapping="uploads", fileNameProperty="uriAudio")
     * @Assert\File(
     *  mimeTypes = {"audio/mpeg"},
     *  mimeTypesMessage = "Format(mp3)"
     * )
     * @var File
     */
    protected $fileAudio;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="upload_at_audio", type="datetime", nullable=true)
     */
    protected $uploadAtAudio;

    /**
     * @return string uri
     */
    public function getUriAudio()
    {
        return $this->uriAudio;
    }

    /**
     * @param string $uriAudio
     */
    public function setUriAudio($uriAudio)
    {
        $this->uriAudio = $uriAudio;
    }

    /**
     * @return File
     */
    public function getFileAudio()
    {
        return $this->fileAudio;
    }

    /**
     * @param File $file
     *                   update $updatedAt
     */
    public function setFileAudio(File $fileAudio = null)
    {
        $this->fileAudio = $fileAudio;

        if ($fileAudio) {
            $this->uploadAtAudio = new \DateTime('now');
        }
    }

    /**
     * Set uploadAt
     */
    public function setUploadAtAudio()
    {
        $this->uploadAtAudio = new \DateTime();

        return $this;
    }

    /**
     * Get uploadAt
     *
     * @return \DateTime
     */
    public function getUploadAtAudio(): ? \Datetime
    {
        return $this->uploadAtAudio;
    }
}
