<?php

namespace App\Form\Filter;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PageFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('course', EntityType::class, [
      'class' => 'App\Entity\FormationManagement\Course',
      'choice_label' => 'title',
      'label' => 'course',
      'translation_domain' => 'messages',
      'expanded' => false,
      'multiple' => false,
      'required' => false,
      'mapped' => false,
      'attr' => ['class' => 'form-control']
    ])->add('pageType', EntityType::class, [
      'class' => 'App\Entity\LovManagement\PageType',
      'choice_label' => 'title',
      'label' => 'pagetype',
      'translation_domain' => 'messages',
      'expanded' => false,
      'multiple' => false,
      'required' => false,
      'mapped' => false,
      'attr' => ['class' => 'form-control']
    ]);
    }
}
