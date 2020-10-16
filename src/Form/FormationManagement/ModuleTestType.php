<?php

namespace App\Form\FormationManagement;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ModuleTestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('score', NumberType::class, [
            'required' => true,
            'attr' => [
                'class' => 'form-control',
                'min' => 1,
            ]
        ])
        ->add('numberTry', NumberType::class, [
            'required' => true,
            'attr' => [
                'class' => 'form-control',
                'min' => 1,
            ]
        ])
        ->add('chronoQuestion', CheckboxType::class, [
            'required' => false,
            'label' => 'formation.module_test.chrono_question',
            'translation_domain' => 'messages',
            'attr' => [
                'class' => 'form-control check-time',
                'data-target' => 'question'
            ]
        ])
        ->add('chronoTest', CheckboxType::class, [
            'required' => false,
            'label' => 'formation.module_test.chrono_session',
            'translation_domain' => 'messages',
            'attr' => [
                'class' => 'form-control check-time',
                'data-target' => 'test'
            ]
        ])
        ->add('chronoTestTime', TimeType::class, [
            'html5' => false,
            'widget' => 'single_text',
        ])
        ->add('chronoQuestionTime', TimeType::class, [
            'html5' => false,
            'widget' => 'single_text',
        ])
        ->add('module', EntityType::class, [
            'class' => 'App\Entity\FormationManagement\Module',
            'choice_label' => 'title',
            'choice_value' => 'slug',
            'expanded' => false,
            'multiple' => false,
            'required' => true])
            // test entity fields
        ->add('title', TextType::class, [
            'required' => true
        ])
        ->add('typeTest', EntityType::class, [
            'class' => 'App\Entity\LovManagement\TypeTest',
            'choice_label' => 'title',
            'expanded' => false,
            'multiple' => false,
            'required' => true
        ])
        ->add('isTestCommune', CheckboxType::class, [
          'required' => false,
          'value' => 1,
        ])
        ->add('isTestPresentiel', CheckboxType::class, [
            'required' => false,
            'value' => 1,
        ])
        ->add('theoricalDuration', TimeType::class, [
            'html5' => false,
            'widget' => 'single_text',
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => [
                'data_class' => "App\Entity\FormationManagement\ModuleTest",
                'allow_extra_fields' => true
            ]
        ]);
    }
}
