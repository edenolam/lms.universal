<?php

namespace App\Form\FormationManagement;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class FormationPathType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, [
              'required' => true,
            ])
            ->add('description', TextareaType::class, [
              'required' => false,
            ])
            ->add('realisationTime', TimeType::class, [
              // 'input'  => 'datetime',
              // 'with_seconds' =>false,
              // 'required' => true,
              // 'placeholder' => [
              //     'hour' => 'Hour', 'minute' => 'Minute'
              // ]
              'html5' => false,
              'widget' => 'single_text'
            ])
            ->add('formationPathModules', CollectionType::class, [
              'entry_type' => FormationPathModuleType::class,
              'entry_options' => ['label' => false],
              'allow_add' => true,
              'allow_delete' => true,
            // , array(
            //   // 'class' => 'App\Entity\FormationManagement\Module',
            //   // 'choice_label' => 'title',
            //   // 'expanded' => true,
            //   // 'multiple' => true,
            //   // 'required' => false,
            //   // 'mapped' => false,
            //   // 'query_builder' => function (EntityRepository $er) {
            //   //   return $er->createQueryBuilder('d')
            //   //       ->where('d.isValid = true');
            //   // },
            //   // 'choice_attr' => function($object, $key, $index) {
            //   //   return ['slug' => $object->getSlug(),
            //   //         'ordre'=> $object->getId()];
            //   // },
            //    )
            ])
            ->add('isModulesAleatoires', ChoiceType::class, [
              'required' => true,
              'expanded' => true,
              'multiple' => false,
              'choices' => ['formation.formation_path.aleatoire' => true, 'formation.formation_path.non_aleatoire' => false],
              'choice_translation_domain' => 'messages',
            ])
            ->add('file', VichFileType::class, [
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
                'download_label' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
      'data_class' => "App\Entity\FormationManagement\FormationPath",
    ]);
    }
}
