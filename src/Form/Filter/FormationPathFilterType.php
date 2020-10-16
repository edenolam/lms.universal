<?php

namespace App\Form\Filter;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class FormationPathFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('module', EntityType::class, [
      'class' => 'App\Entity\FormationManagement\Module',
      'choice_label' => 'title',
      'label' => 'formation.module.literal',
      'translation_domain' => 'messages',
      'expanded' => false,
      'multiple' => false,
      'required' => false,
      'mapped' => false,
      'attr' => ['class' => 'form-control']
    ])->add('session', EntityType::class, [
      'class' => 'App\Entity\PlanningManagement\Session',
      'choice_label' => 'title',
      'label' => 'session.literal',
      'translation_domain' => 'messages',
      'expanded' => false,
      'multiple' => false,
      'required' => false,
      'mapped' => false,
      'attr' => ['class' => 'form-control']
    ]);
    }
}
