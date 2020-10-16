<?php

namespace App\Serializer\FormationManagement;

use App\Entity\FormationManagement\Module;
use App\Persistence\ObjectManager;
use App\Repository\LovManagement\ModuleTypeRepository;
use App\Repository\LovManagement\ValidationModeRepository;
use Symfony\Component\Filesystem\Filesystem;

/**
 * ModuleSerializer
 *
 * @author null
 */
class ModuleSerializer
{
    private $validationModeRepository;
    private $moduleTypeRepository;
    private $om;

    public function __construct(ValidationModeRepository $validationModeRepository, ModuleTypeRepository $moduleTypeRepository, ObjectManager $om)
    {
        $this->validationModeRepository = $validationModeRepository;
        $this->moduleTypeRepository = $moduleTypeRepository;
        $this->om = $om;
    }

    /**
     * @param Module $module
     *
     * @return array
     */
    public function serialize(Module $module)
    {
        // use RevisionTrait, DateTrait, FileTrait, IsValidTrait;
        return [
            'id' => $module->getId(),
            'slug' => $module->getSlug(),
            'reference' => $module->getReference(),
            'regulatoryRef' => $module->getRegulatoryRef(),
            'title' => $module->getTitle(),
            'description' => $module->getDescription(),
            'lastModuleRequirement' => $module->getLastModuleRequirement(),
            'realisationTime' => $module->getRealisationTime(),
            'prerequisites' => $module->getPrerequisites(),
            'validationModes' => $this->validationModesSerialize($module), // ManyToMany validationModes
            'type' => ['id' => $module->getType()->getId(),
                        'title' => $module->getType()->getTitle()],
            'revision' => $module->getRevision(), // RevisionTrait
            'isValid' => $module->getIsValid(), // IsValidTrait
            'isArchived' => $module->getIsArchived(), // IsArchivedTrait
            'uri' => $module->getUri(), // FileTrait
            'uploadAt' => ($module->getUploadAt()) ? $module->getUploadAt()->format('Y-m-d\TH:i:s') : null, // FileTrait
            'createDate' => $module->getCreateDate()->format('Y-m-d\TH:i:s'), // DateTrait
            'updateDate' => $module->getUpdateDate()->format('Y-m-d\TH:i:s') // DateTrait
        ];
        // ManyToOne type = ModuleType
        // OneToMany moduleCourses
        // OneToMany moduleTests
        // OneToMany files = ModuleFile
        // ManyToMany validationModes
    }

    private function validationModesSerialize(Module $module)
    {
        $validationModes = [];
        foreach ($module->getValidationModes() as $validationMode) {
            array_push(
            $validationModes,
            ['id' => $validationMode->getId(),
              'title' => $validationMode->getTitle()]
        );
        }

        return $validationModes;
    }

    public function deserialize(array $data)
    {
        $module = new Module();
        $module->setReference($data['reference']);
        $module->setRegulatoryRef($data['regulatoryRef']);
        $module->setTitle('copie_'.$data['title']);
        $module->setDescription($data['description']);
        $module->setLastModuleRequirement($data['lastModuleRequirement']);
        $module->setRealisationTime($data['realisationTime']);
        $module->setPrerequisites($data['prerequisites']);
        // ManyToMany validationModes
        if (isset($data['validationModes'])) {
            foreach ($data['validationModes'] as $_validationMode) {
                $validationMode = $this->validationModeRepository->findOneBy(
                    [
                    'id' => $_validationMode['id']
                    ]
                );

                if (!empty($validationMode)) {
                    $module->addValidationMode($validationMode);
                }
            }
        }
        // ManyToOne type = ModuleType
        if (isset($data['type']) && isset($data['type']['id'])) {
            $type = $this->moduleTypeRepository->findOneBy([
                'id' => $data['type']['id']
            ]);

            if (!empty($type)) {
                $module->setType($type);
            }
        }
        $module->setIsValid(false);
        $module->setIsArchived($data['isArchived']);
        if ($data['uri']) {
            $filesystem = new Filesystem();
            if ($filesystem->exists('uploads/files/' . $data['uri'])) {
                $filesystem->copy('uploads/files/' . $data['uri'], 'uploads/files/copie_' . $data['uri'], true);
                $module->setUri('copie_' . $data['uri']);
                $module->setUploadAt(new \DateTime());
            }
        }

        return $module;
    }
}
