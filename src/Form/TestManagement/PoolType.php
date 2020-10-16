<?php

namespace App\Form\TestManagement;

//form use stetment
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PoolType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, [
                    'required' => true
                ])
                ->add('nbRequQuestions', NumberType::class, [
                    'required' => true
                ])
                ->add('test', EntityType::class, [
                    'class' => 'App\Entity\TestManagement\Test',
                    'choice_label' => 'title',
                    'expanded' => false,
                    'multiple' => false,
                    'required' => true,
                ])

               ;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix()
    {
        return 'TestManagement_questiontype';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => false,
        ]);
    }
}
