<?php

namespace App\Form\ResultManagement;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;


class BilanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $userLook = $options['user'];
        $tutorFollow = $options['tutorFollow'];
        $role = $options['role'];
      
        
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use( $userLook, $tutorFollow,$role){
            $bilanForm = $event->getForm();
            $divisions = $teams = $users =$formations = $sessions = $modules = null;
            $this->formAdd($bilanForm, $userLook, $tutorFollow, $role, $divisions, $teams,$users, $formations ,$sessions ,$modules);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use( $userLook, $tutorFollow,$role){
            $bilanForm = $event->getForm();
            $data = $event->getData();
            if(array_key_exists('division',$data)){$divisions = $data['division'];}else{$divisions=null;}
            if(array_key_exists('team',$data)){$teams = $data['team'];}else{$teams=null;}
            if(array_key_exists('user',$data)){$users = $data['user'];}else{$users=null;}
            if(array_key_exists('formation',$data)){$formations = $data['formation'];}else{$formations=null;}
            if(array_key_exists('session',$data)){$sessions = $data['session'];}else{$sessions=null;}
            if(array_key_exists('module',$data)){$modules = $data['module'];}else{$modules=null;}
            $this->formAdd($bilanForm, $userLook, $tutorFollow,$role, $divisions, $teams,$users, $formations ,$sessions ,$modules);
        
    
        });

            
    }

    public function formAdd($bilanForm, $userLook, $tutorFollow,$role, $divisions,$teams, $users, $formations,$sessions, $modules){
        
        $bilanForm->add('division',EntityType::class,[
            'class' => 'App\Entity\UserManagement\Division',
            'choice_label' => 'title',
            'placeholder' => 'Sélectionnez la division',
            'query_builder' => function (EntityRepository $er) use($teams, $users, $formations,$sessions, $modules){
                $query = $er->createQueryBuilder('d')
                            ->leftJoin('d.teams', 't')
                            ->leftJoin('t.users', 'u')
                            ->leftJoin('u.sessions', 'ses')
                            ->leftJoin('ses.sessionFormationPathModules', 'sesfp')
                            ->leftJoin('ses.formationPath', 'fp')
                            ->leftJoin('sesfp.module', 'm')
                            ->where('d.isValid = true');
                if($teams!=null){
                    $query->andWhere('t.id IN (:teams)')
                        ->setParameter('teams', $teams);
                }
                $query->orderBy('d.title', 'ASC');
                
                 //var_dump($query->getQuery()->getParameters());
                return $query;
            },
            'expanded' => false,
            'multiple' => true,
            'required' => false,
            'placeholder' => '--',
            ])
            ->add('team',EntityType::class,[
                'class' => 'App\Entity\UserManagement\Team',
                'query_builder' => function (EntityRepository $er) use($divisions, $users, $formations,$sessions, $modules){
                    $query = $er->createQueryBuilder('t')
                                ->leftJoin('t.users', 'u')
                                ->leftJoin('t.division', 'd')
                                ->leftJoin('u.sessions', 'ses')
                                ->leftJoin('ses.sessionFormationPathModules', 'sesfp')
                                ->leftJoin('ses.formationPath', 'fp')
                                ->leftJoin('sesfp.module', 'm')
                                ->where('t.isValid = true');
                    if($divisions!=null){
                        $query->andWhere('d.id IN (:divisions)')
                            ->setParameter('divisions', $divisions);
                    }
                    if($users!=null){
                        $query->andWhere('u.id IN (:users)')
                            ->setParameter('users', $users);
                    }
                    if($formations!=null){
                        $query->andWhere('fp.id IN (:formations)')
                            ->setParameter('formations', $formations);
                    }
                    if($sessions!=null){
                        $query->andWhere('ses.id IN (:sessions)')
                            ->setParameter('sessions', $sessions);
                    }
                    if($modules!=null && $modules!=null){
                        $query->andWhere('m.id IN (:modules)')
                            ->setParameter('modules', $modules);
                    }
                    $query->orderBy('t.title', 'ASC');

                    return $query;
                },
                'expanded' => false,
                'multiple' => true,
                'choice_label' => 'title',
                'placeholder' => '--',
                'required' => false
                ])
            ->add('user', EntityType::class,[
                'class' => 'App\Entity\UserManagement\User',
                'query_builder' => function (EntityRepository $er) use ($role, $tutorFollow, $userLook, $divisions,$teams, $formations,$sessions, $modules) {
                    $query = $er->createQueryBuilder('u')
                                ->leftJoin('u.division', 'd')
                                ->leftJoin('u.supervisors', 's')
                                ->leftJoin('u.team', 't')
                                ->leftJoin('u.sessions', 'ses')
                                ->leftJoin('ses.sessionFormationPathModules', 'sesfp')
                                ->leftJoin('ses.formationPath', 'fp')
                                ->leftJoin('sesfp.module', 'm');

                    if ($role == 'tuteur') {
                        $query->where('s.id = :supervisor' . $userLook->getId() . '')
                            ->setParameter('supervisor' . $userLook->getId() . '', $userLook->getId());
                        if ($tutorFollow != null) {
                            foreach ($tutorFollow as $tutor) {
                                $query->orWhere('s.id = :supervisor' . $tutor->getId() . '')
                                    ->setParameter('supervisor' . $tutor->getId() . '', $tutor->getId());
                            }
                        }
                        $query->andWhere('u.hierarchyLevel < :hierarchyLevel')
                            ->setParameter('hierarchyLevel', $userLook->getHierarchyLevel());
                    }
                    if($teams!=null){
                        $query->andWhere('t.id IN (:teams)')
                            ->setParameter('teams', $teams);
                    }
                    if($divisions!=null){
                        $query->andWhere('d.id IN (:divisions)')
                            ->setParameter('divisions', $divisions);
                    }
                    if($modules!=null){
                        $query->andWhere('m.id IN (:modules)')
                            ->setParameter('modules', $modules);
                    }
                    if($formations!=null){
                        $query->andWhere('fp.id IN (:formations)')
                            ->setParameter('formations', $formations);
                    }
                    if($sessions!=null){
                        $query->andWhere('ses.id IN (:sessions)')
                            ->setParameter('sessions', $sessions);
                    }
                    
                    $query->andWhere('u.isValid = true')
                        ->andWhere('u.enabled = true')
                        ->orderBy('u.lastname', 'ASC');
                        // var_dump($query->getDQL());
                    return $query;
                },
                'expanded' => false,
                'multiple' => true,
                'placeholder' => '--',
                'choice_label' => function ($u) {
                    $label = ''.$u->getLastName().' '.$u->getFirstName();
                     return $label ;
                },
                'required' => false
                ])
            ->add('formation',EntityType::class,[
                'class' => 'App\Entity\FormationManagement\FormationPath',
                'query_builder' => function (EntityRepository $er) use($divisions,$teams, $users,$sessions, $modules){
                    $query = $er->createQueryBuilder('f')
                                ->leftJoin('f.formationPathModules', 'fpm')
                                ->leftJoin('fpm.module', 'm')
                                ->leftJoin('f.sessions', 's')
                                ->leftJoin('s.users', 'u')
                                ->leftJoin('u.team', 't')
                                ->leftJoin('u.division', 'd')
                                ->where('f.isValid = true');
                    if($users!=null){
                        $query->andWhere('u.id IN (:users)')
                            ->setParameter('users', $users);
                    }
                    if($users!=null && $teams!=null){
                        $query->andWhere('t.id IN (:teams)')
                            ->setParameter('teams', $teams);
                    }
                    if($users!=null && $teams!=null&& $divisions!=null){
                        $query->andWhere('d.id IN (:divisions)')
                            ->setParameter('divisions', $divisions);
                    }
                    if($modules!=null){
                        $query->andWhere('m.id IN (:modules)')
                            ->setParameter('modules', $modules);
                    }
                    if($sessions!=null){
                        $query->andWhere('s.id IN (:sessions)')
                            ->setParameter('sessions', $sessions);
                    }
                    $query->orderBy('f.title', 'ASC');
                    return $query;
                },
                'expanded' => false,
                'multiple' => true,
                'choice_label' => 'title',
                'placeholder' => '--',
                'required' => false
                ])
            ->add('session',EntityType::class,[
                'class' => 'App\Entity\PlanningManagement\Session',
                'query_builder' => function (EntityRepository $er) use($divisions,$teams, $users, $formations, $modules){
                    $query = $er->createQueryBuilder('s')
                                ->leftJoin('s.sessionFormationPathModules', 'sfm')
                                ->leftJoin('sfm.module', 'm')
                                ->leftJoin('s.formationPath', 'f')
                                ->leftJoin('s.users', 'u')
                                ->leftJoin('u.team', 't')
                                ->leftJoin('u.division', 'd')
                                ->where('s.isValid = true');

                    if($users!=null){
                        $query->andWhere('u.id IN (:users)')
                            ->setParameter('users', $users);
                    }
                    if($users!=null && $teams!=null){
                        $query->andWhere('t.id IN (:teams)')
                            ->setParameter('teams', $teams);
                    }
                    if($users!=null && $teams!=null&& $divisions!=null){
                        $query->andWhere('d.id IN (:divisions)')
                            ->setParameter('divisions', $divisions);
                    }
                    if($modules!=null){
                        $query->andWhere('m.id IN (:modules)')
                            ->setParameter('modules', $modules);
                    }
                    if($formations!=null){
                        $query->andWhere('f.id IN (:formations)')
                            ->setParameter('formations', $formations);
                    }

                    $query->orderBy('s.title', 'ASC');
                    return $query;
                },
                'choice_label' => 'title',
                'expanded' => false,
                'multiple' => true,
                'required' => false,
                'placeholder' => '--',
                ])
            ->add('module', EntityType::class, [
                'class' => 'App\Entity\FormationManagement\Module',
                'query_builder' => function (EntityRepository $er) use($divisions,$teams, $users, $formations, $sessions){
                    $query = $er->createQueryBuilder('m')
                                ->leftJoin('m.formationPathModules', 'fpm')
                                ->leftJoin('fpm.formationPath', 'f')
                                ->leftJoin('m.sessionFormationPathModules', 'sfpm')
                                ->leftJoin('sfpm.session', 's')
                                ->leftJoin('s.users', 'u')
                                ->leftJoin('m.type', 'ty')
                                ->leftJoin('u.team', 't')
                                ->leftJoin('u.division', 'd')
                                ->where('m.isValid = true');
                    if($users!=null){
                        $query->andWhere('u.id IN (:users)')
                            ->setParameter('users', $users);
                    }
                    if($users!=null && $teams!=null){
                        $query->andWhere('t.id IN (:teams)')
                            ->setParameter('teams', $teams);
                    }
                    if($users!=null && $teams!=null&& $divisions!=null){
                        $query->andWhere('d.id IN (:divisions)')
                            ->setParameter('divisions', $divisions);
                    }
                    if($sessions!=null){
                        $query->andWhere('s.id IN (:sessions)')
                            ->setParameter('sessions', $sessions);
                    }
                    if($formations!=null){
                        $query->andWhere('f.id IN (:formations)')
                            ->setParameter('formations', $formations);
                    }
                    $query->andWhere('ty.conditional <> :notFollow')
                        ->setParameter('notFollow', "notFollow")
                        ->orderBy('m.title', 'ASC');
                    //var_dump($query->getDQL());

                    return $query;
                },
                'choice_label' => 'title',
                'expanded' => false,
                'multiple' => true,
                'required' => false,
                'placeholder' => '--',
                ])
            ->add('name', EntityType::class, [
                'class' => 'App\Entity\ResultManagement\Requete',
                'choice_label' => 'name',
                'placeholder' => 'liste des favories',
                //'label' => 'results.form.placeholder',
                //'translation_domain' => 'messages',
                'query_builder' => function (EntityRepository $er) use ($userLook) {
                    return $er->createQueryBuilder('r')
                        ->where('r.user = :user')
                        ->orderBy('r.name', 'ASC')
                        ->setParameter('user', $userLook);
                },
                'required' => false
                ])
            ->add('datestart', DateType::class, [
                    'required' => false,
                    'html5' => false,
                    'widget' => 'single_text',
                    'label' => 'results.bilan.datestart',
                    'translation_domain' => 'messages',
                    'attr' => [
                        'class' => 'js-datepicker form-control'
                        ]
            ])
            ->add('dateend', DateType::class, [
                    'required' => false,
                    'html5' => false,
                    'widget' => 'single_text',
                    'label' => 'results.bilan.dateend',
                    'translation_domain' => 'messages',
                    'attr' => [
                        'class' => 'js-datepicker form-control'
                    ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([])
            ->setRequired('tutorFollow')
            ->setRequired('user')
            ->setRequired('role');
    }
}
