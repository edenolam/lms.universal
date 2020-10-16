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

class UserType extends AbstractType
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

                ->add('laboratory', EntityType::class, [
                        'class' => 'App\Entity\UserManagement\Laboratory',
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('l')
                                ->where('l.isValid = true');
                        },
                        'choice_label' => 'title',
                        'expanded' => false,
                        'multiple' => false,
                        'required' => false])
                ->add('division', EntityType::class, [
                        'class' => 'App\Entity\UserManagement\Division',
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('d')
                                ->where('d.isValid = true');
                        },
                        'choice_label' => 'title',
                        'expanded' => false,
                        'multiple' => false,
                        'required' => false])
                ->add('team', EntityType::class, [
                        'class' => 'App\Entity\UserManagement\Team',
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('t')
                                ->where('t.isValid = true');
                        },
                        'choice_label' => 'title',
                        'expanded' => false,
                        'multiple' => false,
                        'required' => false])
                ->add('hierarchyLevel', NumberType::class, [
                            'required' => true,
                            'attr' => [
                                'min' => '0',
                                'max' => '10', ]
                            ])

                  ->add('supervisors', EntityType::class, [
                        'class' => 'App\Entity\UserManagement\User',
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('u')
                                ->leftJoin('u.groups', 'g')
                                ->where('u.isValid = true')
                                ->andWhere('u.enabled = true')
                                ->andWhere('u.id != 1')
                                ->andWhere('g.id = 3');
                        },
                      'choice_attr' => function ($object, $key, $index) {
                          return ['hLevel' => $object->getHierarchyLevel()];
                      },
                        'choice_label' => 'username',
                        'choice_value' => 'id',
                        'expanded' => false,
                        'multiple' => true,
                        'required' => false])
                ->add('groups', EntityType::class, [
                        'class' => 'App\Entity\UserManagement\Group',
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('g')
                                ->where('g.isValid = true');
                        },
                        'choice_label' => 'name',
                        'choice_value' => 'id',
                        'expanded' => true,
                        'multiple' => true,
                        'required' => false])
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
                ->add('email', TextType::class, ['required' => false])
                ->add('function', TextType::class, ['required' => false])
                ->add('image', FileType::class, ['required' => false])
                ->add('info1', TextareaType::class, [
                    'required' => false,
                  ])
                ->add('info2', TextareaType::class, [
                    'required' => false,
                  ])
                ->add('info3', TextareaType::class, [
                    'required' => false,
                  ])
                ;
    }

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';
    }

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
        return 'user_registration';
    }
}
