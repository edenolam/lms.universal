<?php

namespace App\Serializer\FormationManagement;

use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\Reference;

/**
 * ReferenceSerializer
 *
 * @author null
 */
class ReferenceSerializer
{
    /**
     * @param Reference $reference
     *
     * @return array
     */
    public function serialize(Reference $reference)
    {
        return [ // RevisionTrait, DateTrait, IsValidTrait;
                'id' => $reference->getId(),
                'slug' => $reference->getSlug(),
                'title' => $reference->getTitle(),
                'author' => $reference->getAuthor(),
                'supportIndication' => $reference->getSupportIndication(),
                'edition' => $reference->getEdition(),
                'location' => $reference->getLocation(),
                'editor' => $reference->getEditor(),
                'date' => $reference->getDate()->format('Y-m-d\TH:i:s'),
                'publicationTitle' => $reference->getPublicationTitle(),
                'numerotation' => $reference->getNumerotation(),
                'normaliseNumber' => $reference->getNormaliseNumber(),
                'disponibility' => $reference->getDisponibility(),
                'informations' => $reference->getInformations(),
                'url' => $reference->getUrl(),
                'revision' => $reference->getRevision(),
                'isValid' => $reference->getIsValid(), //IsValidTrait,
                'createDate' => $reference->getCreateDate()->format('Y-m-d\TH:i:s'),
                'updateDate' => $reference->getUpdateDate()->format('Y-m-d\TH:i:s')
            ];
    }

    public function deserialize(array $data, Module $module)
    {
        $reference = new Reference();
        $reference->setModule($module);
        if ($data['title']) {
            $reference->setTitle($data['title']);
        }
        if ($data['author']) {
            $reference->setAuthor($data['author']);
        }
        if ($data['supportIndication']) {
            $reference->setSupportIndication($data['supportIndication']);
        }
        if ($data['edition']) {
            $reference->setEdition($data['edition']);
        }
        if ($data['location']) {
            $reference->setLocation($data['location']);
        }
        if ($data['editor']) {
            $reference->setEditor($data['editor']);
        }
        if ($data['date']) {
            $reference->setDate(new \DateTime($data['date']));
        }
        if ($data['publicationTitle']) {
            $reference->setPublicationTitle($data['publicationTitle']);
        }
        if ($data['numerotation']) {
            $reference->setNumerotation($data['numerotation']);
        }
        if ($data['normaliseNumber']) {
            $reference->setNormaliseNumber($data['normaliseNumber']);
        }
        if ($data['disponibility']) {
            $reference->setDisponibility($data['disponibility']);
        }
        if ($data['informations']) {
            $reference->setInformations($data['informations']);
        }
        if ($data['url']) {
            $reference->setUrl($data['url']);
        }
        $reference->setIsValid($data['isValid']);

        return $reference;
    }
}
