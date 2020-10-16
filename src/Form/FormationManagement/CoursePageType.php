<?php

namespace App\Form\FormationManagement;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CoursePageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, [
        'required' => false,
        'disabled' => true,
        'label' => false,
        'attr' => [
          'class' => 'form-control'
        ]
      ])->add('sort', HiddenType::class, [
        'required' => true,
        'attr' => [
          'class' => 'sort-hidden'
        ]
      ])
    ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
      'data_class' => "App\Entity\FormationManagement\Page",
    ]);
    }
}
