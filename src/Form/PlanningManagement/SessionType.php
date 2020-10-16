<?php

namespace App\Form\PlanningManagement;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SessionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $now = new \DateTime('now');
        $builder->add('title', TextType::class, [
          'required' => true,
          'label' => 'global.literal.title',
          'translation_domain' => 'messages',
          'attr' => [
            'class' => 'form-control'
        ]])
        ->add('openingDate', DateType::class, [
          'required' => true,
          'html5' => false,
          'widget' => 'single_text',
          'label' => 'formation.formation_path.opening',
          'translation_domain' => 'messages',
          'attr' => [
            'class' => 'js-datepicker form-control'
          ]
        ])
        ->add('closingDate', DateType::class, [
          'required' => true,
          'html5' => false,
          'widget' => 'single_text',
          'label' => 'formation.formation_path.closing',
          'translation_domain' => 'messages',
          'attr' => [
            'class' => 'js-datepicker form-control'
          ],
        ])
        ->add('formationPath', EntityType::class, [
          'class' => 'App\Entity\FormationManagement\FormationPath',
          'choice_label' => 'title',
          'label' => 'formation.formation_path.literal',
          'translation_domain' => 'messages',
          'expanded' => false,
          'multiple' => false,
          'required' => true,
          'placeholder' => "--",
          'attr' => ['class' => 'form-control']
        ])
        // ->add('divisions', EntityType::class, array(
        //   'class' => 'App\Entity\UserManagement\Division',
        //   'choice_label' => 'title',
        //   'label' => 'global.literal.division',
        //   'translation_domain' => 'messages',
        //   'expanded' => false,
        //   'multiple' => true,
        //   'required' => false,
        //   'attr' => ['class' => 'form-control']
        // ))
        // ->add('teams', EntityType::class, array(
        //   'class' => 'App\Entity\UserManagement\Team',
        //   'choice_label' => 'title',
        //   'label' => 'global.literal.team',
        //   'translation_domain' => 'messages',
        //   'expanded' => false,
        //   'multiple' => true,
        //   'required' => false,
        //   'attr' => ['class' => 'form-control']
        // ))
        ->add('users', EntityType::class, [
          'class' => 'App\Entity\UserManagement\User',
          'choice_label' => function ($user) {
              return $user->getFirstname() . ' ' . $user->getLastname();
          },
          // 'query_builder' => function (EntityRepository $er) {
          //     return $er->createQueryBuilder('d')
          //         ->leftJoin('d.groups','g')
          //         ->where('d.isValid = true')
          //         ->andWhere('g.id = 4');
          //   },
          'label' => 'global.literal.user',
          'translation_domain' => 'messages',
          'expanded' => false,
          'multiple' => true,
          'required' => false,
          'attr' => ['class' => 'form-control']
        ])
      ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
      'data_class' => "App\Entity\PlanningManagement\Session",
    ]);
    }
}
