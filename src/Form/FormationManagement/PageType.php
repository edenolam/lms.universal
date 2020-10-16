<?php

namespace App\Form\FormationManagement;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class PageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, [
                'required' => true,
                'label' => 'global.literal.title',
                'translation_domain' => 'messages',
            ])
            ->add('inSummary', ChoiceType::class, [
                'required' => true,
                'multiple' => false,
                'choices' => ['global.literal.oui' => true, 'global.literal.non' => false],
                'choice_translation_domain' => 'messages',
            ])
            ->add('contentCode', 'FOS\CKEditorBundle\Form\Type\CKEditorType', [
                'required' => false,
                'config_name' => 'page_config',
                'label' => 'formation.page.content_code',
                'translation_domain' => 'messages',
            ])
            ->add('jsCode', TextareaType::class, [
                'required' => false,
                'label' => 'formation.page.js_code',
                'translation_domain' => 'messages',
            ])
            ->add('textualContent', 'FOS\CKEditorBundle\Form\Type\CKEditorType', [
                'required' => false,
                'config_name' => 'page_config',
                'label' => 'formation.page.textual_content',
                'translation_domain' => 'messages',
            ])
            ->add('file', VichFileType::class, [
                'label' => 'formation.module.file',
                'translation_domain' => 'messages',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
                'download_label' => false,
                'attr' => [
                    'class' => 'form-control check-mime ',
                    'data-mime' => 'image/jpeg video/webm video/mpeg video/mp4 application/pdf'
                ]
            ])
            ->add('pageType', EntityType::class, [
                'class' => 'App\Entity\LovManagement\PageType',
                'choice_label' => 'title',
                'expanded' => true,
                'multiple' => false,
                'required' => true,
            ])
            ->add('course', EntityType::class, [
                'class' => 'App\Entity\FormationManagement\Course',
                'query_builder' => function (EntityRepository $er) use ($options) {
                    return $er->createQueryBuilder('C')
                        ->leftJoin('C.moduleCourses', 'CM')
                        ->where('CM.module = :module')
                        ->andWhere('C.isValid = true')
                        ->setParameter('module', $options['module'])
                        ->orderBy('CM.sort', 'ASC');
                },
                'choice_label' => 'title',
                'expanded' => false,
                'multiple' => false,
                'required' => true,
            ])
            ->add('fileAudio', VichFileType::class, [
                'label' => 'formation.page.file_audio',
                'translation_domain' => 'messages',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
                'download_label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
                'choices' => [
                    'data_class' => "App\Entity\FormationManagement\Page",
                    'allow_extra_fields' => true,
                ]
            ])
            ->setRequired('module')
            ;
    }
}
