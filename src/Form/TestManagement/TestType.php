<?php

namespace App\Form\TestManagement;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

//autre use statement

class TestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, [
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

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix()
    {
        return 'TestManagement_testype';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => [
                'data_class' => "App\Entity\TestManagement\Test",
                'allow_extra_fields' => true], ]);
    }
}
