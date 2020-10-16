<?php

namespace App\Form\TestManagement;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class AnswerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('content', TextareaType::class, [
                    'required' => true
                ])
                ->add('status', CheckboxType::class, [
                    'required' => false
                ])
                ->add('isValid', ChoiceType::class, [
                    'required' => false,
                    'multiple' => false,
                    'choices' => ['global.literal.oui' => true, 'global.literal.non' => false],
                    'choice_translation_domain' => 'messages'
                ]);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix()
    {
        return 'TestManagement_questiontype';
    }
}
