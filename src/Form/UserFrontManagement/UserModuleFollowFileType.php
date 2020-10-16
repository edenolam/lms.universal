<?php

namespace App\Form\UserFrontManagement;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class UserModuleFollowFileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
      'required' => true,
      'label' => 'user.nom',
      'translation_domain' => 'messages',
      'attr' => [
        'class' => 'form-control'
      ]])
      ->add('file', VichFileType::class, [
          'required' => false,
          'label' => 'formation.module.file',
          'translation_domain' => 'messages',
          'allow_delete' => false,
          'download_uri' => false,
          'download_label' => false,
          'attr' => [
            'class' => 'form-control check-mime',
            'data-mime' => 'application/pdf image/jpeg audio/mpeg video/mpeg application/vnd.ms-excel application/msword'
          ]
        ])
      ->add('isDownload', CheckboxType::class, [
        'required' => false,
        'label' => 'formation.module_file.downloadable',
        'translation_domain' => 'messages'
      ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
      'choices' => [
        'data_class' => "App\Entity\UserFrontManagement\UserModuleFollowFile",
        'allow_extra_fields' => true
      ],
    ]);
    }
}
