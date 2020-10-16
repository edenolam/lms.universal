<?php

namespace App\Form\FormationManagement;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ReferenceLiteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, [
      'required' => true,
      'label' => false,
      'translation_domain' => 'messages',
      'attr' => [
        'class' => 'form-control',
        'placeholder' => 'global.literal.title'
      ]
      ])
      ->add('author', TextType::class, [
        'required' => true,
        'label' => false,
        'translation_domain' => 'messages',
        'attr' => [
          'class' => 'form-control',
          'placeholder' => 'global.author'
        ]
      ])
      ->add('date', DateType::class, [
        'required' => true,
        'html5' => true,
        'widget' => 'single_text',
        'label' => false,
        'translation_domain' => 'messages',
        'attr' => [
          'class' => 'form-control js-datepicker',
          'placeholder' => 'formation.reference.date'
        ]
      ])
     ;
    }
}
