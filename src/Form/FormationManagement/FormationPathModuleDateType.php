<?php

namespace App\Form\FormationManagement;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormationPathModuleDateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('openingDate', DateType::class, [
      'required' => true,
      'html5' => false,
      'widget' => 'single_text',
      'label' => 'formation.formation_path.opening',
      'translation_domain' => 'messages',
      'attr' => [
        'class' => 'form-control js-datepicker'
      ]
    ])
    ->add('closingDate', DateType::class, [
      'required' => true,
      'html5' => false,
      'widget' => 'single_text',
      'label' => 'formation.formation_path.closing',
      'translation_domain' => 'messages',
      'attr' => [
        'class' => 'form-control js-datepicker'
      ]
      ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
      'choices' => [
        'data_class' => "App\Entity\FormationManagement\FormationPathModule",
        'allow_extra_fields' => true
      ],
    ]);
    }
}
