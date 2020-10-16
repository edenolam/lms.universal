<?php

namespace App\Form\FormationManagement;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ReferenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, [
      'required' => true,
      'label' => 'global.literal.title',
      'translation_domain' => 'messages',
      'attr' => [
        'class' => 'form-control'
      ]
      ])
      ->add('author', TextType::class, [
        'required' => false,
        'label' => 'global.author',
        'translation_domain' => 'messages',
        'attr' => [
          'class' => 'form-control'
        ]
      ])
      ->add('supportIndication', TextType::class, [
        'required' => false,
        'label' => 'formation.reference.support_indication',
        'translation_domain' => 'messages',
        'attr' => [
          'class' => 'form-control'
        ]
      ])
      ->add('edition', TextType::class, [
        'required' => false,
        'label' => 'formation.reference.edition',
        'translation_domain' => 'messages',
        'attr' => [
          'class' => 'form-control'
        ]
      ])
      ->add('location', TextType::class, [
        'required' => false,
        'label' => 'formation.reference.location',
        'translation_domain' => 'messages',
        'attr' => [
          'class' => 'form-control'
        ]
      ])
      ->add('editor', TextType::class, [
        'required' => false,
        'label' => 'formation.reference.editor',
        'translation_domain' => 'messages',
        'attr' => [
          'class' => 'form-control'
        ]
      ])
      ->add('date', DateType::class, [
        'required' => true,
        'html5' => false,
        'widget' => 'single_text',
        'label' => 'formation.reference.date',
        'translation_domain' => 'messages',
        'attr' => [
          'class' => 'form-control js-datepicker'
        ]
      ])
      ->add('publicationTitle', TextType::class, [
        'required' => false,
        'label' => 'formation.reference.publication_title',
        'translation_domain' => 'messages',
        'attr' => [
          'class' => 'form-control'
        ]
      ])
      ->add('numerotation', TextType::class, [
        'required' => false,
        'label' => 'formation.reference.numerotation',
        'translation_domain' => 'messages',
        'attr' => [
          'class' => 'form-control'
        ]
      ])
      ->add('normaliseNumber', TextType::class, [
        'required' => false,
        'label' => 'formation.reference.normalise_number',
        'translation_domain' => 'messages',
        'attr' => [
          'class' => 'form-control'
        ]
      ])
      ->add('disponibility', CheckboxType::class, [
        'required' => false,
        'label' => 'formation.reference.disponibility',
        'translation_domain' => 'messages',
        'attr' => [
          'class' => 'form-control'
        ]
      ])
      ->add('informations', TextType::class, [
        'required' => false,
        'label' => 'formation.reference.informations',
        'translation_domain' => 'messages',
        'attr' => [
          'class' => 'form-control'
        ]
      ])
      ->add('url', TextType::class, [
        'required' => false,
        'label' => 'formation.reference.url',
        'translation_domain' => 'messages',
        'attr' => [
          'class' => 'form-control'
        ]
      ])
      ;
    }
}
