<?php

namespace App\Form\PlanningManagement;

use App\Entity\PlanningManagement\Signature;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SignatureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code')
            ->add('date')
            ->add('ip')
            ->add('raison')
            ->add('isValid')
            ->add('sessionFormationPathModule')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Signature::class,
        ]);
    }
}
