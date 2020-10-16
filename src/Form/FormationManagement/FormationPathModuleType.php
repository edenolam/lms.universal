<?php

namespace App\Form\FormationManagement;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormationPathModuleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('module', EntityType::class, [
                  'class' => 'App\Entity\FormationManagement\Module',
                  'choice_label' => 'title',
                  'expanded' => false,
                  'multiple' => false,
                  'required' => true,
                  'query_builder' => function (EntityRepository $er) {
                      return $er->createQueryBuilder('m')
                         ->where('m.isValid = true');
                  },
                  'choice_attr' => function ($object, $key, $index) {
                      return ['slug' => $object->getSlug(),
                          'ordre' => $object->getId()];
                  }, ])
            ->add('sort', NumberType::class, [
                  'required' => true,
                  ])
            ->add('title', TextType::class, [
              'required' => true,
              ])
            ->add('formationPath', EntityType::class, [
                    'class' => 'App\Entity\FormationManagement\FormationPath',
                    'choice_label' => 'title',
                    'expanded' => false,
                    'multiple' => false,
                    'required' => true,
                  ])
    ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
      'data_class' => "App\Entity\FormationManagement\FormationPathModule",
    ]);
    }
}
