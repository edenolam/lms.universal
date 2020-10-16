<?php

namespace App\Form\FormationManagement;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, [
        'required' => true
      ])
     ->add('description', TextareaType::class, [
        'required' => false
      ])

      ->add('file', FileType::class, [
          'label' => 'formation.module.file',
          'translation_domain' => 'messages',
          'required' => false,
          'attr' => [
              'class' => 'form-control check-mime ',
              'data-mime' => 'application/pdf'
          ]
      ])
    ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
        [
        'choices' => [
          'data_class' => "App\Entity\FormationManagement\Course",
          'allow_extra_fields' => true
          ],
        ]
    );
    }
}
