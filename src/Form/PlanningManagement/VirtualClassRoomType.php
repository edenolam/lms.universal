<?php

namespace App\Form\PlanningManagement;

use App\Entity\PlanningManagement\VirtualClassRoom;
use App\Entity\UserManagement\GROUP;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VirtualClassRoomType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
              'required' => true])
            ->add('openingDate', DateTimeType::class, [
                'required' => true,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd HH:mm:ss',
            ])
            ->add('closingDate', DateTimeType::class, [
                'required' => true,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd HH:mm:ss',
            ])
            ->add('teacher', EntityType::class, [
                'class' => 'App\Entity\UserManagement\User',
                'query_builder' => function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('u');
                    $qb->join('u.groups', 'g')->where($qb->expr()->eq('g.id', GROUP::TUTOR_GROUP));

                    return $qb->addOrderBy('u.firstname', 'DESC')->addOrderBy('u.lastname', 'DESC');
                },
                'choice_label' => function ($user) {
                    return $user->getFirstname() . ' ' . $user->getLastname();
                },
                'expanded' => false,
                'multiple' => false,
                'required' => true
            ])
            ->add('students', EntityType::class, [
                'class' => 'App\Entity\UserManagement\User',
                'query_builder' => function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('u');
                    $qb->join('u.groups', 'g')->where($qb->expr()->eq('g.id', GROUP::BASIC_GROUP));

                    return $qb->addOrderBy('u.firstname', 'DESC')->addOrderBy('u.lastname', 'DESC');
                },
                'choice_label' => function ($user) {
                    return $user->getFirstname() . ' ' . $user->getLastname();
                },
                'expanded' => false,
                'multiple' => true,
                'required' => true
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => VirtualClassRoom::class,
            'csrf_protection' => true,
        ]);
    }
}
