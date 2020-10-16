<?php

namespace App\Form\FormationManagement;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class DownloadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, [
        'required' => true,
        'label' => false,
        'translation_domain' => 'messages',
        'attr' => [
          'class' => 'form-control',
          'placeholder' => 'formation.page.download'
        ]
      ])
      ->add('isDownload', ChoiceType::class, [
                'required' => true,
                'multiple' => false,
                'choices' => ['global.literal.oui' => true, 'global.literal.non' => false],
                'choice_translation_domain' => 'messages',
            ])
      ->add('file', VichFileType::class, [
          'label' => 'formation.module.file',
          'translation_domain' => 'messages',
          'required' => true,
          'allow_delete' => true,
          'download_uri' => false,
          'download_label' => false,
          'attr' => [
              'class' => 'form-control'
          ]
      ])
     ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        'data_class' => "App\Entity\FormationManagement\Download",
      ]);
    }
}
