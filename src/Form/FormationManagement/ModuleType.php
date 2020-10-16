<?php

namespace App\Form\FormationManagement;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Symfony\Component\Validator\Constraints\File;

class ModuleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, [
            'required' => true,
            'label' => 'global.literal.title',
            'translation_domain' => 'messages',
            ])
            ->add('scormZip', FileType::class, [
                'required' => false,
                'constraints' => [
                        new File([
                            'mimeTypes' => [
                                'application/zip',
                                'application/octet-stream',
                                'application/x-zip-compressed',
                                'multipart/x-zip',
                            ],
                            'mimeTypesMessage' => 'Veuillez télécharger un fichier Scorm Zip  '
                        ])
                    ]
            ])
            ->add('lieuFormation', TextType::class, [
                'required' => false,
                'label' => 'formation.module.lieu_formation',
                'translation_domain' => 'messages',
            ])
            ->add('nomAnimateur', TextType::class, [
                'required' => true,
                'label' => 'formation.module.nom_animateur',
                'translation_domain' => 'messages',
            ])
            ->add('codeClient', TextType::class, [
                'required' => false,
                'label' => 'formation.module.code_client',
                'translation_domain' => 'messages',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'lov.description',
                'translation_domain' => 'messages',
            ])
            ->add('regulatoryRef', TextType::class, [
                'required' => true,
                'label' => 'formation.module.regulatory_ref',
                'translation_domain' => 'messages',
            ])
            ->add('prerequisites', TextareaType::class, [
                'required' => false,
                'label' => 'formation.module.list.prerequisites',
                'translation_domain' => 'messages',
            ])
            ->add('realisationTime', TimeType::class, [
                'html5' => false,
                'widget' => 'single_text'
            ])
            ->add('type', EntityType::class, [
                'class' => 'App\Entity\LovManagement\ModuleType',
                'choice_label' => 'title',
                'label' => 'lov.module_type',
                'translation_domain' => 'messages',
                'expanded' => false,
                'multiple' => false,
                'required' => true,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->where('t.isValid = true');
                }
            ])
            ->add('validationModes', EntityType::class, [
                'class' => 'App\Entity\LovManagement\ValidationMode',
                'choice_label' => 'title',
                'label' => 'lov.validation_mode',
                'translation_domain' => 'messages',
                'expanded' => true,
                'multiple' => true,
                'required' => true,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('v')
                        ->where('v.isValid = true');
                }
            ])
            ->add('file', VichFileType::class, [
                'label' => 'formation.module.file',
                'translation_domain' => 'messages',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
                'download_label' => false,
                'attr' => [
                    'class' => 'check-mime',
                    'data-mime' => 'image/jpeg'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => [
                'data_class' => "App\Entity\FormationManagement\Module",
                'allow_extra_fields' => false
            ],
        ]);
    }
}
