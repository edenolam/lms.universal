<?php

namespace App\Form\FormationManagement;

use App\Entity\FormationManagement\Page;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderReferenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('pageReferences', CollectionType::class, [
      'entry_type' => PageReferenceType::class,
      'entry_options' => ['label' => false],
      'prototype' => true,
      'attr' => [
        'class' => 'sort-selector',
      ],
    ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
      'data_class' => Page::class,
    ]);
    }
}
