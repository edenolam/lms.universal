<?php

namespace App\Form\Filter;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ModuleFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('formationPath', EntityType::class, [
      'class' => 'App\Entity\FormationManagement\FormationPath',
      'choice_label' => 'title',
      'label' => 'formation.formation_path.literal',
      'translation_domain' => 'messages',
      'expanded' => false,
      'multiple' => false,
      'required' => false,
      'mapped' => false,
      'attr' => ['class' => 'form-control']
    ])->add('course', EntityType::class, [
      'class' => 'App\Entity\FormationManagement\Course',
      'choice_label' => 'title',
      'label' => 'formation.course.title',
      'translation_domain' => 'messages',
      'expanded' => false,
      'multiple' => false,
      'required' => false,
      'mapped' => false,
      'attr' => ['class' => 'form-control']
    ])->add('validationMode', EntityType::class, [
      'class' => 'App\Entity\LovManagement\ValidationMode',
      'choice_label' => 'title',
      'label' => 'lov.validation_mode',
      'translation_domain' => 'messages',
      'expanded' => false,
      'multiple' => false,
      'required' => false,
      'mapped' => false,
      'attr' => ['class' => 'form-control']
    ]);
    }
}
