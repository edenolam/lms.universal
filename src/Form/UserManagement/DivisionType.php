<?php

namespace App\Form\UserManagement;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DivisionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('laboratory', EntityType::class, [
                        'class' => 'App\Entity\UserManagement\Laboratory',
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('l')
                                  ->where('l.isValid = true');
                        },
                        'choice_label' => 'title',
                        'expanded' => false,
                        'multiple' => false,
                        'required' => true])
                ->add('title', TextType::class, [
                    'required' => true, ])
                ->add('description', TextareaType::class, [
                    'required' => false, ])
                ->add('keywords', TextType::class, [
                    'required' => false, ])
                ->add('sort', NumberType::class, [
                    'required' => false, ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => "App\Entity\UserManagement\Division",
            'csrf_protection' => true,
        ]);
    }
}
