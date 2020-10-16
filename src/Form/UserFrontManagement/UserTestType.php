<?php

namespace App\Form\UserFrontManagement;

use App\Entity\UserFrontManagement\UserTest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserTestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('datePass', DateType::class, [
              'required' => false,
              'html5' => true,
              'widget' => 'single_text',
              'attr' => [
                  'class' => 'form-control js-datepicker',
                  'placeholder' => 'formation.reference.date'
                ]
            ])
            ->add('dateDown', DateType::class, [
              'required' => true,
              'html5' => true,
              'widget' => 'single_text',
              'attr' => [
                  'class' => 'form-control js-datepicker',
                  'placeholder' => 'formation.reference.date'
                ]
            ])
            ->add('score', NumberType::class, [
                'required' => true,
            ])
            ->add('duration', NumberType::class, [
                'required' => true,
            ])
            ->add('tentative', NumberType::class, [
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserTest::class,
        ]);
    }
}
