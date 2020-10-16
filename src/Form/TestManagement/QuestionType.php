<?php

namespace App\Form\TestManagement;

//form use stetment
use App\Entity\FormationManagement\Page;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

//autre use statement

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, [
                    'required' => false
                ])
                ->add('question', TextAreaType::class, [
                    'required' => true
                ])
                ->add('weight', NumberType::class, [
                    'required' => true
                ])
                ->add('comment', TextAreaType::class, [
                    'required' => false
                ])
                ->add('content', TextAreaType::class, [
                    'required' => false
                ])
                // ->add('content', 'FOS\CKEditorBundle\Form\Type\CKEditorType', array(
                //     'required' => false,
                //     'config_name' => 'page_config',
                //     'label' => 'formation.page.content_code',
                //     'translation_domain' => 'messages',
                // ))
                ->add('required', ChoiceType::class, [
                    'required' => true,
                    //'expanded' => true,
                    'multiple' => false,
                    'choices' => ['global.literal.non' => false, 'global.literal.oui' => true],
                    'choice_translation_domain' => 'messages',
                ])
                ->add('knowledges', EntityType::class, [
                    'class' => 'App\Entity\FormationManagement\Knowledge',
                    'choice_label' => 'title',
                    'query_builder' => function (EntityRepository $er) use ($options) {
                        return $er->createQueryBuilder('k')
                                ->leftJoin('k.pages', 'p')
                                ->leftJoin('p.course', 'c')
                                ->leftJoin('c.moduleCourses', 'mc')
                                ->where('mc.module = :module')
                                ->andWhere('k.isValid = 1')
                                ->setParameter('module', $options['moduletest']->getModule());
                    },
                    'expanded' => false,
                    'multiple' => true,
                    'required' => false
                ])
                ->add('pool', EntityType::class, [
                    'class' => 'App\Entity\TestManagement\Pool',
                    'choice_label' => 'title',
                    'query_builder' => function (EntityRepository $er) use ($options) {
                        return $er->createQueryBuilder('p')
                                ->where('p.isValid = 1')
                                ->andWhere('p.test = :test')
                                ->setParameter('test', $options['test'])
                                ->orderBy('p.id', 'DESC');
                    },
                    'expanded' => false,
                    'multiple' => false,
                    'required' => true
                ])
                ->add('answerType', EntityType::class, [
                    'class' => 'App\Entity\LovManagement\AnswerType',
                    'choice_label' => 'title',
                    'query_builder' => function (EntityRepository $er) use ($options) {
                        return $er->createQueryBuilder('rt')
                            ->where('rt.isValid = 1');
                    },
                    //'expanded' => true,
                    'multiple' => false,
                    'required' => true
                ])

               ;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix()
    {
        return 'TestManagement_questiontype';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => false,
        ])
        ->setRequired('test')
        ->setRequired('moduletest')
        ->setRequired('pool')
        ->setRequired('question');
    }
}
