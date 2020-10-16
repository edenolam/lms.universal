<?php

namespace App\Form\FormationManagement;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KnowledgeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, [
        'required' => false,
        'label' => false,
        'translation_domain' => 'messages',
        'attr' => [
          'class' => 'form-control',
          'placeholder' => 'formation.page.connaissances'
        ]
      ])
     ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        'data_class' => "App\Entity\FormationManagement\Knowledge",
      ]);
    }
}
