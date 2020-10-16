<?php

namespace App\Form\ResultManagement;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SondageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'division',
            EntityType::class,
            [
            'class' => 'App\Entity\UserManagement\Division',
            'choice_label' => 'title',
            'placeholder' => 'Sélectionnez la division',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('d')
                    ->where('d.isValid = true')
                    ->orderBy('d.title', 'ASC');
            },
            'required' => false]
            )
            ->add(
                'team',
                EntityType::class,
                [
                'class' => 'App\Entity\UserManagement\Team',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->where('t.isValid = true')
                        ->orderBy('t.title', 'ASC');
                },
                'choice_label' => 'title',
                'placeholder' => 'Sélectionnez l\'équipe',
                'required' => false]
            )
            ->add(
                'user',
                EntityType::class,
                [
                'class' => 'App\Entity\UserManagement\User',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                                ->andWhere('u.isValid = true')
                                ->andWhere('u.enabled = true')
                                ->orderBy('u.username', 'ASC');
                },
                'placeholder' => 'Sélectionnez un utilisateur',
                'choice_label' => 'username',
                'required' => false]
            )
            ->add(
                'formation',
                EntityType::class,
                [
                'class' => 'App\Entity\FormationManagement\FormationPath',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('f')
                        ->where('f.isValid = true')
                        ->orderBy('f.title', 'ASC');
                },
                'choice_label' => 'title',
                'placeholder' => 'Sélectionnez la formation',
                'required' => false]
            )
            ->add(
                'session',
                EntityType::class,
                [
                'class' => 'App\Entity\PlanningManagement\Session',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->where('s.isValid = true')
                        ->orderBy('s.title', 'ASC');
                },
                'choice_label' => 'title',
                'expanded' => false,
                'multiple' => true,
                'required' => false]
            )
            ->add('test', EntityType::class, [
                'class' => 'App\Entity\TestManagement\Test',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('t')
                        ->leftJoin('t.typeTest', 'tt')
                        ->where('tt.conditional = :conditional')
                        ->setParameter('conditional', 'sondage')
                        ->orderBy('t.title', 'ASC');
                },
                'choice_label' => 'title',
                'placeholder' => 'Sélectionnez un sondage',
                'required' => false])
           ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);
    }
}
