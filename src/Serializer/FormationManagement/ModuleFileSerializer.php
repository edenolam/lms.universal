<?php

namespace App\Serializer\FormationManagement;

use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\ModuleFile;
use Symfony\Component\Filesystem\Filesystem;

/**
 * ModuleFileSerializer
 *
 * @author null
 */
class ModuleFileSerializer
{
    /**
     * @param ModuleFile $moduleFile
     *
     * @return array
     */
    public function serialize(ModuleFile $moduleFile)
    {
        // use RevisionTrait, DateTrait, FileTrait, IsValidTrait, IsDeletedTrait;
        return [
            'id' => $moduleFile->getId(),
            'slug' => $moduleFile->getSlug(),
            'name' => $moduleFile->getName(),
            'isDownload' => $moduleFile->getIsDownload(),
            'revision' => $moduleFile->getRevision(), // RevisionTrait
            'isValid' => $moduleFile->getIsValid(), // IsValidTrait
            'uri' => $moduleFile->getUri(), // FileTrait
            'uploadAt' => $moduleFile->getUploadAt(), // FileTrait
            'createDate' => $moduleFile->getCreateDate()->format('Y-m-d\TH:i:s'), // DateTrait
            'updateDate' => $moduleFile->getUpdateDate()->format('Y-m-d\TH:i:s') // DateTrait
        ];
    }

    public function deserialize(array $data, Module $module)
    {
        $moduleFile = new ModuleFile();
        $moduleFile->setModule($module);
        $moduleFile->setName($data['name']);
        $moduleFile->setIsDownload($data['isDownload']);

        if ($data['uri']) {
            $filesystem = new Filesystem();
            if ($filesystem->exists('uploads/files/' . $data['uri'])) {
                $filesystem->copy('uploads/files/' . $data['uri'], 'uploads/files/copie_' . $data['uri'], true);
                $moduleFile->setUri('copie_' . $data['uri']);
                $moduleFile->setUploadAt(new \DateTime());
            }
        }

        return $moduleFile;
    }
}
