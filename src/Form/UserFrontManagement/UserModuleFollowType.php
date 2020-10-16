<?php

namespace App\Form\UserFrontManagement;

use App\Entity\UserFrontManagement\UserModuleFollow;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserModuleFollowType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('validationDate', DateType::class, [
              'required' => false,
              'html5' => true,
              'widget' => 'single_text',
              'attr' => [
                'class' => 'form-control js-datepicker',
                'placeholder' => 'formation.reference.date'
              ]
            ])
            ->add('startDate', DateType::class, [
              'required' => true,
              'html5' => true,
              'widget' => 'single_text',
              'attr' => [
                'class' => 'form-control js-datepicker',
                'placeholder' => 'formation.reference.date'
              ]
            ])
            ->add('endDate', DateType::class, [
              'required' => true,
              'html5' => true,
              'widget' => 'single_text',
              'attr' => [
                'class' => 'form-control js-datepicker',
                'placeholder' => 'formation.reference.date'
              ]
            ])
            ->add('durationTotalSec', NumberType ::class, [
              'required' => false,
            ]) // realisationTime
            ->add('success')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserModuleFollow::class,
        ]);
    }
}
