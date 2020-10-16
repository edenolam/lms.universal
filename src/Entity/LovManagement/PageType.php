<?php

namespace App\Entity\LovManagement;

use App\Model\LovManagement\Lov as baseLov;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LovManagement\PageTypeRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class PageType extends baseLov
{
    public const Expert = 'expert';
    public const Video = 'vdieo';
    public const Pedagogique = 'pedago';
    public const Pdf = 'pdf';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    public function getId(): ?int
    {
        return $this->id;
    }
}
