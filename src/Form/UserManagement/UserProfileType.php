<?php

namespace App\Form\UserManagement;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('civility', EntityType::class, [
                            'class' => 'App\Entity\LovManagement\Civility',
                            'query_builder' => function (EntityRepository $er) {
                                return $er->createQueryBuilder('c')
                                    ->where('c.isValid = true');
                            },
                            'choice_label' => 'title',
                            'expanded' => false,
                            'multiple' => false,
                            'required' => false])
                ->add('firstname', TextType::class, ['required' => true])
                ->add('lastname', TextType::class, ['required' => true])
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
                        'required' => false])
                ->add('address', TextType::class, ['required' => false])
                ->add('addressBis', TextType::class, ['required' => false])
                ->add('zipCode', TextType::class, ['required' => false])
                ->add('phone', TextType::class, ['required' => false])
                ->add('function', TextType::class, ['required' => false])
                ->add('image', FileType::class, ['required' => false])
                ;
    }

    // public function getParent()
    // {
    //    return 'FOS\UserBundle\Form\Type\RegistrationFormType';
    // }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => "App\Entity\UserManagement\User",
            'intention' => 'registration',
            'csrf_protection' => true,
        ]);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix()
    {
        return 'user_profile';
    }
}
