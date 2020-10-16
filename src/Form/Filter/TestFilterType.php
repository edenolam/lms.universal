<?php

namespace App\Form\Filter;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class TestFilterType extends AbstractType
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
    ])->add('typeTest', EntityType::class, [
      'class' => 'App\Entity\LovManagement\TypeTest',
      'choice_label' => 'title',
      'label' => 'lov.type_test',
      'translation_domain' => 'messages',
      'expanded' => false,
      'multiple' => false,
      'required' => false,
      'mapped' => false,
      'attr' => ['class' => 'form-control']
    ])->add('formationPath', EntityType::class, [
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
    ])->add('year', ChoiceType::class, [
      'choices' => $this->getYears(1990),
      'required' => false,
      'label' => 'global.literal.year',
      'translation_domain' => 'messages',
      'attr' => [
        'class' => 'form-control'
      ]
    ])->add('orderBy', ChoiceType::class, [
      'required' => false,
      'attr' => [
        'class' => 'form-control'
      ],
      'label' => 'global.order.literal',
      'translation_domain' => 'messages',
      'choices' => [
        'formation.formation_path.literal' => 1,
        'session.literal' => 2,
        'global.literal.year' => 3
      ],
      'choice_translation_domain' => 'messages'
    ])->add('order', ChoiceType::class, [
      'required' => true,
      'attr' => [
        'class' => 'form-control'
      ],
      'label' => 'global.order.way',
      'translation_domain' => 'messages',
      'choices' => [
        'global.order.asc' => 'ASC',
        'global.order.desc' => 'DESC'
      ],
      'choice_translation_domain' => 'messages'
    ]);
    }

    private function getYears($min, $max = 'current')
    {
        $years = range($min, ($max === 'current' ? date('Y') : $max));

        return array_combine($years, $years);
    }
}
