<?php

namespace App\Form\UserManagement;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LaboratoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, [
                    'required' => true, ])
                ->add('description', TextareaType::class, [
                    'required' => false, ])
                ->add('keywords', TextType::class, [
                    'required' => false, ])
                ->add('sort', NumberType::class, [
                    'required' => false, ])
                ->add('url', TextType::class, ['required' => false])
                ->add('city', TextType::class, ['required' => false])
                ->add('country', EntityType::class, [
                        'class' => 'App\Entity\LovManagement\Country',
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('c')
                                ->where('c.isValid = true');
                        },
                        'choice_label' => 'title',
                        'expanded' => false,
                        'multiple' => false,
                        'required' => true])
                ->add('address', TextType::class, ['required' => false])
                ->add('addressBis', TextType::class, ['required' => false])
                ->add('zipCode', TextType::class, ['required' => false])
                ->add('phone', TextType::class, ['required' => false])
                ->add('email', TextType::class, ['required' => false])
                ->add('image', FileType::class, ['required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => "App\Entity\UserManagement\Laboratory",
            'csrf_protection' => true,
        ]);
    }
}
