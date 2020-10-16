<?php

namespace App\Form\Filter;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SessionFilterType extends AbstractType
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
    ])->add('user', EntityType::class, [
      'class' => 'App\Entity\UserManagement\User',
      'choice_label' => 'username',
      'label' => 'tracking.user',
      'translation_domain' => 'messages',
      'expanded' => false,
      'multiple' => false,
      'required' => false,
      'mapped' => false,
      'attr' => ['class' => 'form-control']
    ])->add('division', EntityType::class, [
      'class' => 'App\Entity\UserManagement\Division',
      'choice_label' => 'title',
      'label' => 'laboratory.division',
      'translation_domain' => 'messages',
      'expanded' => false,
      'multiple' => false,
      'required' => false,
      'mapped' => false,
      'attr' => ['class' => 'form-control']
    ])->add('team', EntityType::class, [
      'class' => 'App\Entity\UserManagement\Team',
      'choice_label' => 'title',
      'label' => 'team.literal',
      'translation_domain' => 'messages',
      'expanded' => false,
      'multiple' => false,
      'required' => false,
      'mapped' => false,
      'attr' => ['class' => 'form-control']
    ]);
    }
}
