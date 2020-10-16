<?php

namespace App\Form\FormationManagement;

use App\Entity\FormationManagement\Module;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderCourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('moduleCourses', CollectionType::class, [
      'entry_type' => ModuleCourseType::class,
      'entry_options' => ['label' => false],
      'attr' => [
        'class' => 'sort-selector',
      ],
    ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
      'data_class' => Module::class,
    ]);
    }
}
