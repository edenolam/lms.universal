<?php

namespace App\Entity\FormationManagement;

use App\Traits\DateTrait;
use App\Traits\FileTrait;
use App\Traits\IsValidTrait;
use App\Traits\RevisionTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FormationManagement\EditorFileRepository")
 * @Vich\Uploadable
 */
class EditorFile
{
    use RevisionTrait;
    use DateTrait;
    use FileTrait;
    use IsValidTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Gedmo\Slug(fields={"uri"})
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string $slug
     */
    public function getSlug()
    {
        return $this->slug;
    }

    public function __contruct()
    {
    }
}
