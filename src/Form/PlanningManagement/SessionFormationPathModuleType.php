<?php

namespace App\Form\PlanningManagement;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SessionFormationPathModuleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('openingDate', DateType::class, [
              'required' => false,
              'html5' => false,
              'widget' => 'single_text',
              'label' => 'formation.formation_path.opening',
              'translation_domain' => 'messages',
              'attr' => [
                'class' => 'js-datepicker form-control'
              ]
            ])
            ->add('closingDate', DateType::class, [
              'required' => false,
              'html5' => false,
              'widget' => 'single_text',
              'label' => 'formation.formation_path.closing',
              'translation_domain' => 'messages',
              'attr' => [
                'class' => 'js-datepicker form-control'
              ]
            ])
            ->add('module', EntityType::class, [
                'class' => 'App\Entity\FormationManagement\Module',
                'choice_label' => 'title',
                'translation_domain' => 'messages',
                'expanded' => false,
                'multiple' => false,
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('lieu', TextType::class, [
                'attr' => ['class' => 'form-control']
            ])
            ->add('codeClient', TextType::class, [
                'attr' => ['class' => 'form-control']
            ])
            ->add('session', EntityType::class, [
                'class' => 'App\Entity\PlanningManagement\Session',
                'choice_label' => 'title',
                'translation_domain' => 'messages',
                'expanded' => false,
                'multiple' => false,
                'required' => false,
                'attr' => ['class' => 'form-control']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => "App\Entity\PlanningManagement\SessionFormationPathModule",
        ]);
    }
}
