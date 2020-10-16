<?php

namespace App\DataFixtures;

use App\Entity\LovManagement\AnswerType;
use App\Entity\LovManagement\Civility;
use App\Entity\LovManagement\Country;
use App\Entity\LovManagement\ModuleType;
use App\Entity\LovManagement\PageType;
use App\Entity\LovManagement\TypeTest;
use App\Entity\LovManagement\ValidationMode;
use App\Entity\TestManagement\Answer;
use App\Entity\UserManagement\Group;
use App\Entity\UserManagement\Laboratory;
use App\Entity\FormationManagement\VersioningModule;
use App\Entity\FormationManagement\ModuleTest;
use App\Entity\FormationManagement\Module;
use App\Entity\TestManagement\Test;
use App\Entity\TestManagement\Pool;
use App\Entity\TestManagement\Question;

use Doctrine\Bundle\FixturesBundle\Fixture;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AppFixtures extends Fixture implements ContainerAwareInterface
{
    public const SUPER_ADMIN_PASSWORD = 'admin2019!';
    public const SUPER_ADMIN_USERNAME = 'admin';
    public const SUPER_ADMIN_NOM = 'admin';
    public const SUPER_ADMIN_PRENOM = 'admin';
    public const SUPER_ADMIN_EMAIL = 'admin@universalmedica.com';

    public const ADMIN_PASSWORD = 'admini2019!';
    public const ADMIN_USERNAME = 'admini';
    public const ADMIN_NOM = 'admini';
    public const ADMIN_PRENOM = 'admini';
    public const ADMIN_EMAIL = 'admini@universalmedica.com';

    public const SUPER_ADMIN_GROUP = 'Super administrateur';
    public const ADMIN_GROUP = 'Administrateur';
    public const TUTEUR_GROUP = 'Tuteur';
    public const APPRENANT_GROUP = 'Apprenant';
    public const CONCEPTEUR_GROUP = 'Concepteur';
    public const RESPONSABLE_GROUP = 'Responsable';

    public const TUTEUR_PASSWORD = 'tuteur2019!';
    public const TUTEUR_USERNAME = 'tuteur';
    public const TUTEUR_NOM = 'tuteur';
    public const TUTEUR_PRENOM = 'tuteur';
    public const TUTEUR_EMAIL = 'tuteur@universalmedica.com';

    public const APPRENANT_PASSWORD = 'apprenant2019!';
    public const APPRENANT_USERNAME = 'apprenant';
    public const APPRENANT_NOM = 'apprenant';
    public const APPRENANT_PRENOM = 'apprenant';
    public const APPRENANT_EMAIL = 'yi.shen@universalmedica.com';
    public const NB_APPRENANT_USER = 10;

    public const CONCEPTEUR_PASSWORD = 'concepteur2019!';
    public const CONCEPTEUR_USERNAME = 'concepteur';
    public const CONCEPTEUR_NOM = 'concepteur';
    public const CONCEPTEUR_PRENOM = 'concepteur';
    public const CONCEPTEUR_EMAIL = 'concepteur@universalmedica.com';

    public const RESPONSABLE_PASSWORD = 'responsable2019!';
    public const RESPONSABLE_USERNAME = 'responsable';
    public const RESPONSABLE_NOM = 'responsable';
    public const RESPONSABLE_PRENOM = 'responsable';
    public const RESPONSABLE_EMAIL = 'responsabl@universalmedica.com';
    /**
     * @var Faker\Factory
     */
    private $faker;

    /**
     * Sets the Faker\Factory.
     * @param null
     */
    public function setFaker()
    {
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager)
    {
        $this->setFaker();

        $this->loadGroup($manager);
        $this->loadUser($manager);
        $this->loadCountry($manager);
        $this->loadCivility($manager);
        $this->loadLaboratory($manager);
        
        $this->loadModuleType($manager);
        $this->loadTypeTest($manager);
        $this->loadAnswerType($manager);
        $this->loadPageType($manager);
        $this->loadValidationMode($manager);


        $this->loadModuleSondageUMG($manager);
        $this->loadTestSondageUMG($manager);
        $this->loadPoolSondageUMG($manager);
        $this->loadQuestionSondageUMG($manager);

    }

    public function loadGroup()
    {
        $groupManager = $this->container->get('fos_user.group_manager');

        // SUPER_ADMIN_GROUP
        $group = $groupManager->createGroup('');
        $group->setName(self::SUPER_ADMIN_GROUP);
        $group->setRoles(['ROLE_SUPER_ADMIN']);
        $groupManager->updateGroup($group);

        // ADMIN_GROUP
        $group = $groupManager->createGroup('');
        $group->setName(self::ADMIN_GROUP);
        $group->setRoles(['ROLE_ADMIN']);
        $groupManager->updateGroup($group);

        // TUTEUR_GROUP
        $group = $groupManager->createGroup('');
        $group->setName(self::TUTEUR_GROUP);
        $group->setRoles(['ROLE_TUTEUR']);
        $groupManager->updateGroup($group);
        $this->addReference('group-tuteur', $group);

        // APPRENANT_GROUP
        $group = $groupManager->createGroup('');
        $group->setName(self::APPRENANT_GROUP);
        $group->setRoles(['ROLE_APPRENANT']);
        $groupManager->updateGroup($group);
        $this->addReference('group-apprenant', $group);

        // concepteur group
        $group = $groupManager->createGroup('');
        $group->setName(self::CONCEPTEUR_GROUP);
        $group->setRoles(['ROLE_CONCEPTEUR']);
        $groupManager->updateGroup($group);
        $this->addReference('group-concepteur', $group);

        // responsable group
        $group = $groupManager->createGroup('');
        $group->setName(self::RESPONSABLE_GROUP);
        $group->setRoles(['ROLE_RESPONSABLE_FORMATION']);
        $groupManager->updateGroup($group);
        $this->addReference('group-responsable', $group);
    }

    public function loadUser(ObjectManager $manager)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $passwordEncoder = $this->container->get('security.password_encoder');
    

        $groupManager = $this->container->get('fos_user.group_manager');
        $group = $groupManager->findGroupByName(self::SUPER_ADMIN_GROUP);

        // Create super admin
        $root = $userManager->createUser();
        $root->setUsername(self::SUPER_ADMIN_USERNAME);
        $root->setFirstname(self::SUPER_ADMIN_USERNAME);
        $root->setLastname(self::SUPER_ADMIN_USERNAME);
        $root->setEmail(self::SUPER_ADMIN_EMAIL);
        $encodedPassword = $passwordEncoder->encodePassword($root, self::SUPER_ADMIN_PASSWORD);
        $root->setPassword($encodedPassword);
        $root->setEnabled(true);
        $root->setCreateUser($root);
        $root->setUpdateUser($root);
        $root->setLastChangePassword(new \DateTime());
        $root->setRoles(['ROLE_SUPER_ADMIN']);
        $root->addGroup($group);
        $userManager->updateUser($root, true);
        $this->addReference('user-super-admin', $root);

        //{Create admin
            // $admin = $userManager->createUser();
            // $admin->setUsername(self::ADMIN_USERNAME);
            // $admin->setFirstname(self::ADMIN_USERNAME);
            // $admin->setLastname(self::ADMIN_USERNAME);
            // $admin->setEmail(self::ADMIN_EMAIL);
            // $encodedPassword = $passwordEncoder->encodePassword($admin, self::ADMIN_PASSWORD);
            // $admin->setPassword($encodedPassword);
            // $admin->setEnabled(true);
            // $admin->setCreateUser($admin);
            // $admin->setUpdateUser($admin);
            // $admin->setLastChangePassword(new \DateTime());
            // $admin->setRoles(['ROLE_ADMIN']);
            // $admin->addGroup($group);
            // $userManager->updateUser($admin, true);

            //$this->addReference('user-admin', $root);

            // // Create tuteur
            // $tuteur = $userManager->createUser();
            // $tuteur->setUsername(self::TUTEUR_USERNAME);
            // $tuteur->setFirstname(self::TUTEUR_USERNAME);
            // $tuteur->setLastname(self::TUTEUR_USERNAME);
            // $tuteur->setEmail(self::TUTEUR_EMAIL);
            // $encodedPassword = $passwordEncoder->encodePassword($tuteur, self::TUTEUR_PASSWORD);
            // $tuteur->setPassword($encodedPassword);
            // $tuteur->setEnabled(true);
            // $tuteur->setCreateUser($admin);
            // $tuteur->setUpdateUser($admin);
            // $tuteur->setLastChangePassword(new \DateTime());
            // $tuteur->addGroup($this->getReference('group-tuteur'));
            // $userManager->updateUser($tuteur, true);

            // Create apprenant
            // $apprenant = $userManager->createUser();
            // $apprenant->setUsername(self::APPRENANT_USERNAME);
            // $apprenant->setFirstname(self::APPRENANT_USERNAME);
            // $apprenant->setLastname(self::APPRENANT_USERNAME);
            // $apprenant->setEmail(self::APPRENANT_EMAIL);
            // $encodedPassword = $passwordEncoder->encodePassword($apprenant, self::APPRENANT_PASSWORD);
            // $apprenant->setPassword($encodedPassword);
            // $apprenant->setEnabled(true);
            // $apprenant->setCreateUser($admin);
            // $apprenant->setUpdateUser($admin);
            // $apprenant->setLastChangePassword(new \DateTime());
            // $apprenant->addGroup($this->getReference('group-apprenant'));
            // $userManager->updateUser($apprenant, true);

            // Create concepteur
            // $concepteur = $userManager->createUser();
            // $concepteur->setUsername(self::CONCEPTEUR_USERNAME);
            // $concepteur->setFirstname(self::CONCEPTEUR_USERNAME);
            // $concepteur->setLastname(self::CONCEPTEUR_USERNAME);
            // $concepteur->setEmail(self::CONCEPTEUR_EMAIL);
            // $encodedPassword = $passwordEncoder->encodePassword($concepteur, self::CONCEPTEUR_PASSWORD);
            // $concepteur->setPassword($encodedPassword);
            // $concepteur->setEnabled(true);
            // $concepteur->setCreateUser($admin);
            // $concepteur->setUpdateUser($admin);
            // $concepteur->setLastChangePassword(new \DateTime());
            // $concepteur->addGroup($this->getReference('group-concepteur'));
            // $userManager->updateUser($concepteur, true);

            // Create responsable
            // $responsable = $userManager->createUser();
            // $responsable->setUsername(self::RESPONSABLE_USERNAME);
            // $responsable->setFirstname(self::RESPONSABLE_USERNAME);
            // $responsable->setLastname(self::RESPONSABLE_USERNAME);
            // $responsable->setEmail(self::RESPONSABLE_EMAIL);
            // $encodedPassword = $passwordEncoder->encodePassword($responsable, self::RESPONSABLE_PASSWORD);
            // $responsable->setPassword($encodedPassword);
            // $responsable->setEnabled(true);
            // $responsable->setCreateUser($admin);
            // $responsable->setUpdateUser($admin);
            // $responsable->setLastChangePassword(new \DateTime());
            // $responsable->addGroup($this->getReference('group-responsable'));
            // $userManager->updateUser($responsable, true);
        //}

        $usernames = [];

        // for ($i = 0; $i < self::NB_APPRENANT_USER; $i++) {
        //     // Create 100 utilisateur basique avec le mote de passe "azerty"
        //     $basique = $userManager->createUser();

        //     $firstName = $this->faker->firstName;
        //     $lastName = $this->faker->lastName;
        //     $username = strtolower($firstName . $lastName);

        //     if (!in_array($username, $usernames)) {
        //         $basique->setUsername($username);
        //         $basique->setFirstname($firstName);
        //         $basique->setLastname($lastName);
        //         $basique->setEmail($firstName . '.' . $lastName . '@universalmedica.com');
        //         $encodedPassword = $passwordEncoder->encodePassword($basique, 'azerty');
        //         $basique->setPassword($encodedPassword);
        //         $basique->setEnabled(true);
        //         $basique->setCreateUser($admin);
        //         $basique->setUpdateUser($admin);
        //         $basique->setLastChangePassword(new \DateTime());
        //         $basique->addGroup($this->getReference('group-apprenant'));

        //         $userManager->updateUser($basique, true);
        //         $this->addReference('user-apprenant' . ($i + 1), $basique);
        //         $usernames[] = $username;
        //     }
        // }
        
    }

    public function loadLaboratory(ObjectManager $manager)
    {
        $laboratory = new Laboratory();
        $laboratory->setTitle('Universal Medica Group');
        $laboratory->setCountry($this->getReference('country'));
        $laboratory->setCreateUser($this->getReference('user-super-admin'));
        $laboratory->setUpdateUser($this->getReference('user-super-admin'));

        $manager->persist($laboratory);
        $manager->flush();

        $this->addReference('laboratory', $laboratory);

        $admin = $this->getReference('user-super-admin');
        $admin->setLaboratory($laboratory);
        $manager->persist($admin);
        $manager->flush();
    }

    public function loadCivility(ObjectManager $manager)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $admin = $userManager->findUserByUsername(self::SUPER_ADMIN_USERNAME);

        $civilities = ['Mlle', 'Mme', 'M', 'Dr', 'Pr'];
        foreach ($civilities as $key => $value) {
            $civility = new Civility();
            $civility->setTitle($value);
            $civility->setDescription($value);
            $civility->setSort($key + 1);
            $civility->setCreateUser($admin);
            $civility->setUpdateUser($admin);
            $civility->setRevision(0);
            $manager->persist($civility);
            $manager->flush();
        }
    }

    public function loadPageType(ObjectManager $manager)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $admin = $userManager->findUserByUsername('admin');

        //EXPERT TYPE
        $pageType1 = new PageType();
        $pageType1->setTitle('Mode expert');
        $pageType1->setDescription("Mode permetant la création d'une page avec du code HTML, JS.");
        $pageType1->setKeywords('expert');
        $pageType1->setConditional('expert');
        $pageType1->setSort(0);
        $pageType1->setCreateUser($admin);
        $pageType1->setUpdateUser($admin);
        $pageType1->setRevision(0);
        $manager->persist($pageType1);

        //EXPERT TYPE
        $pageType2 = new PageType();
        $pageType2->setTitle('Mode vidéo');
        $pageType2->setDescription("Mode permetant la création d'une page avec une vidéo.");
        $pageType2->setKeywords('video');
        $pageType2->setConditional('video');
        $pageType2->setSort(1);
        $pageType2->setCreateUser($admin);
        $pageType2->setUpdateUser($admin);
        $pageType2->setRevision(0);
        $manager->persist($pageType2);

        //EXPERT TYPE
        $pageType3 = new PageType();
        $pageType3->setTitle('Mode pédagogique');
        $pageType3->setDescription("Mode permetant la création d'une page en mode pédagogique.");
        $pageType3->setKeywords('pedago');
        $pageType3->setConditional('pedago');
        $pageType3->setSort(1);
        $pageType3->setCreateUser($admin);
        $pageType3->setUpdateUser($admin);
        $pageType3->setRevision(0);
        $manager->persist($pageType3);

        //EXPERT TYPE
        $pageType4 = new PageType();
        $pageType4->setTitle('Mode PDF');
        $pageType4->setDescription("Mode permetant la création d'une page en mode PDF.");
        $pageType4->setKeywords('pdf');
        $pageType4->setConditional('pdf');
        $pageType4->setSort(1);
        $pageType4->setCreateUser($admin);
        $pageType4->setUpdateUser($admin);
        $pageType4->setRevision(0);
        $manager->persist($pageType4);

        $manager->flush();
    }

    public function loadAnswerType(ObjectManager $manager)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $admin = $userManager->findUserByUsername('admin');

        // MULTIPLE CHOICE ANSWER
        $answerType = new AnswerType();
        $answerType->setTitle('Choix multiple');
        $answerType->setDescription('Réponses avec cases à cocher. Plusieurs possibles');
        $answerType->setKeywords('multiple');
        $answerType->setConditional('multiple');
        $answerType->setSort(1);
        $answerType->setCreateUser($admin);
        $answerType->setUpdateUser($admin);
        $answerType->setRevision(0);
        $manager->persist($answerType);
        $this->addReference('multiple', $answerType);
        

        // SIMPLE CHOICE ANSWER
        $answerType2 = new AnswerType();
        $answerType2->setTitle('Choix unique');
        $answerType2->setDescription('Réponses à choix unique. Bouton radio');
        $answerType2->setKeywords('unique');
        $answerType2->setConditional('unique');
        $answerType2->setSort(2);
        $answerType2->setCreateUser($admin);
        $answerType2->setUpdateUser($admin);
        $manager->persist($answerType2);

        //  text
        $answerType3 = new AnswerType();
        $answerType3->setTitle('Text');
        $answerType3->setDescription('Réponses par un text');
        $answerType3->setKeywords('text');
        $answerType3->setConditional('text');
        $answerType3->setSort(3);
        $answerType3->setCreateUser($admin);
        $answerType3->setUpdateUser($admin);
        $manager->persist($answerType3);
        $this->addReference('text', $answerType3);

        //  text
        $answerType4 = new AnswerType();
        $answerType4->setTitle('Graduation');
        $answerType4->setDescription('Note en 1 et 10');
        $answerType4->setKeywords('graduation');
        $answerType4->setConditional('graduation');
        $answerType4->setSort(4);
        $answerType4->setCreateUser($admin);
        $answerType4->setUpdateUser($admin);
        $manager->persist($answerType4);
        $this->addReference('graduation', $answerType4);

        $manager->flush();
    }

    public function loadModuleType(ObjectManager $manager)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $admin = $userManager->findUserByUsername('admin');

        $moduleType1 = new ModuleType();
        $moduleType1->setTitle('Module Standard');
        $moduleType1->setRevision(0);
        $moduleType1->setConditional('standard');
        $moduleType1->setCreateUser($admin);
        $moduleType1->setUpdateUser($admin);
        $manager->persist($moduleType1);

        $moduleType2 = new ModuleType();
        $moduleType2->setTitle('Module Scorm');
        $moduleType2->setRevision(0);
        $moduleType2->setConditional('scorm');
        $moduleType2->setCreateUser($admin);
        $moduleType2->setUpdateUser($admin);
        $manager->persist($moduleType2);

        $moduleType3 = new ModuleType();
        $moduleType3->setTitle('Module Présentiel');
        $moduleType3->setConditional('presentiel');
        $moduleType3->setRevision(0);
        $moduleType3->setCreateUser($admin);
        $moduleType3->setUpdateUser($admin);
        $manager->persist($moduleType3);

        $moduleType4 = new ModuleType();
        $moduleType4->setTitle('Module sans suivi');
        $moduleType4->setConditional('notFollow');
        $moduleType4->setRevision(0);
        $moduleType4->setCreateUser($admin);
        $moduleType4->setUpdateUser($admin);
        $manager->persist($moduleType4);
        $this->addReference('notFollow', $moduleType4);

        $manager->flush();
    }

    public function loadTypeTest(ObjectManager $manager)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $admin = $userManager->findUserByUsername('admin');

        $testType1 = new TypeTest();
        $testType1->setTitle('Pré-test');
        $testType1->setDescription('');
        $testType1->setSort(1);
        $testType1->setConditional('pretest');
        $testType1->setCreateUser($admin);
        $testType1->setUpdateUser($admin);
        $testType1->setRevision(0);
        $manager->persist($testType1);

        $testType2 = new TypeTest();
        $testType2->setTitle('Entrainement');
        $testType2->setDescription('');
        $testType2->setSort(2);
        $testType2->setConditional('training');
        $testType2->setCreateUser($admin);
        $testType2->setUpdateUser($admin);
        $testType2->setRevision(0);
        $manager->persist($testType2);

        $testType3 = new TypeTest();
        $testType3->setTitle('Sondage');
        $testType3->setDescription('Stockage de questions pour d\'autres utilisations');
        $testType3->setSort(3);
        $testType3->setConditional('sondage');
        $testType3->setCreateUser($admin);
        $testType3->setUpdateUser($admin);
        $testType3->setRevision(0);
        $manager->persist($testType3);
        $this->addReference('sondage', $testType3);

        $testType4 = new TypeTest();
        $testType4->setTitle('Evaluation');
        $testType4->setDescription('');
        $testType4->setSort(4);
        $testType4->setConditional('eval');
        $testType4->setCreateUser($admin);
        $testType4->setUpdateUser($admin);
        $testType4->setRevision(0);
        $manager->persist($testType4);

        $manager->flush();
    }

    public function loadValidationMode(ObjectManager $manager)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $admin = $userManager->findUserByUsername('admin');

        $validationMode1 = new ValidationMode();
        $validationMode1->setTitle('Pré-test validant');
        $validationMode1->setDescription('');
        $validationMode1->setSort(1);
        $validationMode1->setConditional('pre-test-valid');
        $validationMode1->setCreateUser($admin);
        $validationMode1->setUpdateUser($admin);
        $validationMode1->setRevision(0);
        $manager->persist($validationMode1);

        $validationMode2 = new ValidationMode();
        $validationMode2->setTitle('Pré-test informatif');
        $validationMode2->setDescription('');
        $validationMode2->setSort(2);
        $validationMode2->setConditional('pre-test-non-valid');
        $validationMode2->setCreateUser($admin);
        $validationMode2->setUpdateUser($admin);
        $validationMode2->setRevision(0);
        $manager->persist($validationMode2);

        $validationMode3 = new ValidationMode();
        $validationMode3->setTitle('Evaluation');
        $validationMode3->setDescription('');
        $validationMode3->setSort(3);
        $validationMode3->setConditional('eval');
        $validationMode3->setCreateUser($admin);
        $validationMode3->setUpdateUser($admin);
        $validationMode3->setRevision(0);
        $manager->persist($validationMode3);

        $validationMode4 = new ValidationMode();
        $validationMode4->setTitle('Lecture complète');
        $validationMode4->setDescription('');
        $validationMode4->setSort(4);
        $validationMode4->setConditional('lecture');
        $validationMode4->setCreateUser($admin);
        $validationMode4->setUpdateUser($admin);
        $validationMode4->setRevision(0);
        $manager->persist($validationMode4);

        $validationMode5 = new ValidationMode();
        $validationMode5->setTitle('Pas de validation');
        $validationMode5->setDescription('');
        $validationMode5->setSort(5);
        $validationMode5->setConditional('noValidation');
        $validationMode5->setCreateUser($admin);
        $validationMode5->setUpdateUser($admin);
        $validationMode5->setRevision(0);
        $manager->persist($validationMode5);

        $validationMode6 = new ValidationMode();
        $validationMode6->setTitle('Présence');
        $validationMode6->setDescription('');
        $validationMode6->setSort(6);
        $validationMode6->setConditional('presence');
        $validationMode6->setCreateUser($admin);
        $validationMode6->setUpdateUser($admin);
        $validationMode6->setRevision(0);
        $manager->persist($validationMode6);

        $manager->flush();
    }

    public function loadCountry(ObjectManager $manager)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $admin = $userManager->findUserByUsername('admin');

        $country = ['France' => 'FR'];
        $sort = 1;
        foreach ($country as $key => $value) {
            $country = new Country();
            $country->setTitle($key);
            $country->setDescription($key);
            $country->setKeywords($value);
            $country->setSort($sort);
            $country->setCreateUser($admin);
            $country->setUpdateUser($admin);
            $country->setRevision(0);
            $manager->persist($country);
            $manager->flush();
            if ($sort == 1) {
                $this->addReference('country', $country);
            }
            $sort++;
        }
    }

    public function loadModuleSondageUMG(ObjectManager $manager)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $admin = $userManager->findUserByUsername('admin');

        $moduleSondages[1] = array(
            'reference'=>'FORMULAIRE-SONDAGE-UMG', 
            'regulatory_ref'=>'FO-TRA-UMG-004 V01', 
            'title'=>'Satisfaction', 
            'realisation_time'=>new \DateTime('00:10:00'), 
            'prerequisites'=>'Merci pour le temps que vous nous avez accordé',
            'type_module'=> $this->getReference('notFollow'),
        );

        foreach ($moduleSondages as $module) {
            $moduleS = new Module();
            $moduleS->setReference($module['reference']);
            $moduleS->setRegulatoryRef($module['regulatory_ref']);
            $moduleS->setTitle($module['title']);
            $moduleS->setRealisationTime($module['realisation_time']);
            $moduleS->setPrerequisites($module['prerequisites']);
            $moduleS->setType($module['type_module']);
            $moduleS->setCreateDate(new \DateTime('now'));
            $moduleS->setUpdateDate(new \DateTime('now'));
            $moduleS->setIsValid(true);
            $moduleS->setIsDefaultModule(true);
            
            $manager->persist($moduleS);
            $manager->flush();

            $moduleV1 = new VersioningModule();
            $moduleV1->setActor($admin);
            $moduleV1->setModule($moduleS);
            $moduleV1->setModuleVersion($moduleS->getVersion());
            $moduleV1->setAction('Mise en Conception');
            $moduleV1->setJustification('');
            $moduleV1->setHasRequiredRole(true);

            $manager->persist($moduleV1);

            $moduleV2 = new VersioningModule();
            $moduleV2->setActor($admin);
            $moduleV2->setModule($moduleS);
            $moduleV2->setModuleVersion($moduleS->getVersion());
            $moduleV2->setAction('Mise en ligne');
            $moduleV2->setJustification(null);
            $moduleV2->setHasRequiredRole(true);

            $manager->persist($moduleV2);

            $manager->flush();
            $this->addReference('moduleSondage', $moduleS);
        }
    }

    public function loadTestSondageUMG(ObjectManager $manager)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $admin = $userManager->findUserByUsername('admin');

        $testSondages[1] = array(
            'reference'=>'FORMULAIRE-SONDAGE-UMG',  
            'title'=>'Enquête de satisfaction post-formation', 
            'type_test_id'=> $this->getReference('sondage'),
        );

        foreach ($testSondages as $test) {
            $testS = new Test();
            $testS->setRef($test['reference']);
            $testS->setTitle($test['title']);
            $testS->setTypeTest($test['type_test_id']);
            $testS->setCreateDate(new \DateTime('now'));
            $testS->setUpdateDate(new \DateTime('now'));
            $testS->setCreateUser($admin);
            $testS->setUpdateUser($admin);
            $testS->setModule($this->getReference('moduleSondage'));

            $manager->persist($testS);
            $manager->flush();

            $moduleT = new ModuleTest();
            $moduleT->setModule($this->getReference('moduleSondage'));
            $moduleT->setTest($testS);
            $moduleT->setScore(1);
            $moduleT->setNumberTry(1);
            $moduleT->setChronoTest(false);
            $moduleT->setChronoQuestion(false);

            $manager->persist($moduleT);
            $manager->flush();
            $this->addReference('testSondage', $testS);
        }

    }

    public function loadPoolSondageUMG(ObjectManager $manager)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $admin = $userManager->findUserByUsername('admin');

        $poolSondages[1] = array(
            'reference'=>'FORMULAIRE-SONDAGE-UMG',
            'title'=>'Enquête de satisfaction post-formation',
            'nb_requ_questions'=>10,
        );

        foreach ($poolSondages as $pool) {
            $poolS = new Pool();
            $poolS->setTest($this->getReference('testSondage'));
            $poolS->setRef($pool['reference']);
            $poolS->setTitle($pool['title']);
            $poolS->setNbRequQuestions($pool['nb_requ_questions']);
            $poolS->setCreateDate(new \DateTime('now'));
            $poolS->setUpdateDate(new \DateTime('now'));
            $poolS->setCreateUser($admin);
            $poolS->setUpdateUser($admin);

            $manager->persist($poolS);
            $manager->flush();
            $this->addReference('poolSondage1', $poolS);
        }

    }


    public function loadQuestionSondageUMG(ObjectManager $manager)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $admin = $userManager->findUserByUsername('admin');
        
        $answer = Array();
        $questionSondages[1] = array(
            'reference'=>'FORMULAIRE-SONDAGE-UMG-Q1',
            'answer_type_id'=> $this->getReference('graduation'),
            'title'=>'Appréciation globale de la formation',
            'question'=>'Sur une échelle de 0 à 10 (0 : très mauvais et 10 : excellent), qu’avez-vous pensé de cette session de formation ?', 
            'answer'=>$answer,
        );

        $questionSondages[2] = array(
            'reference'=>'FORMULAIRE-SONDAGE-UMG-Q2',
            'answer_type_id'=> $this->getReference('text'),
            'title'=>'Appréciation globale de la formation',
            'question'=>'Commentaires :', 
            'answer'=>$answer,
        );

        $questionSondages[3] = array(
            'reference'=>'FORMULAIRE-SONDAGE-UMG-Q3',
            'answer_type_id'=> $this->getReference('graduation'),
            'title'=>'Appréciation globale de la formation',
            'question'=>'La formation était-elle en adéquation avec vos attentes ?', 
            'answer'=>$answer,
        );

        $questionSondages[4] = array(
            'reference'=>'FORMULAIRE-SONDAGE-UMG-Q4',
            'answer_type_id'=> $this->getReference('graduation'),
            'title'=>'Qualité de l’animation',
            'question'=>'Sur une échelle de 0 à 10 (0 : très mauvais et 10 : excellent), qu’avez-vous pensé de la qualité de l’animation ?', 
            'answer'=>$answer,
        );

        $questionSondages[5] = array(
            'reference'=>'FORMULAIRE-SONDAGE-UMG-Q5',
            'answer_type_id'=> $this->getReference('text'),
            'title'=>'Qualité de l’animation',
            'question'=>'Commentaires :', 
            'answer'=>$answer,
        );

        $answer6[1] = array('reference'=>'FORMULAIRE-SONDAGE-UMG-Q6-A1','content'=>'Médiocre');
        $answer6[2] = array('reference'=>'FORMULAIRE-SONDAGE-UMG-Q6-A2','content'=>'Moyenne');
        $answer6[3] = array('reference'=>'FORMULAIRE-SONDAGE-UMG-Q6-A3','content'=>'Bonne');
        $answer6[4] = array('reference'=>'FORMULAIRE-SONDAGE-UMG-Q6-A4','content'=>'Excellente');
        $questionSondages[6] = array(
            'reference'=>'FORMULAIRE-SONDAGE-UMG-Q6',
            'answer_type_id'=> $this->getReference('multiple'),
            'title'=>'Qualité des supports utilisés',
            'question'=>'Que pensez-vous de la qualité pédagogique des supports qui vous ont été remis/projetés ?', 
            'answer'=>$answer6,
        );

        $questionSondages[7] = array(
            'reference'=>'FORMULAIRE-SONDAGE-UMG-Q7',
            'answer_type_id'=> $this->getReference('text'),
            'title'=>'Qualité des supports utilisés',
            'question'=>'Support le PLUS apprécié :', 
            'answer'=>$answer,
        );

        $questionSondages[8] = array(
            'reference'=>'FORMULAIRE-SONDAGE-UMG-Q8',
            'answer_type_id'=> $this->getReference('text'),
            'title'=>'Qualité des supports utilisés',
            'question'=>'Support le MOINS apprécié :', 
            'answer'=>$answer,
        );

        $answer9[1] = array('reference'=>'FORMULAIRE-SONDAGE-UMG-Q9-A1','content'=>'Trop Lent');
        $answer9[2] = array('reference'=>'FORMULAIRE-SONDAGE-UMG-Q9-A2','content'=>'Lent');
        $answer9[3] = array('reference'=>'FORMULAIRE-SONDAGE-UMG-Q9-A3','content'=>'Adapté');
        $answer9[4] = array('reference'=>'FORMULAIRE-SONDAGE-UMG-Q9-A4','content'=>'Rapide');
        $answer9[5] = array('reference'=>'FORMULAIRE-SONDAGE-UMG-Q9-A5','content'=>'Trop Rapide');
        $questionSondages[9] = array(
            'reference'=>'FORMULAIRE-SONDAGE-UMG-Q9',
            'answer_type_id'=> $this->getReference('multiple'),
            'title'=>'Timing',
            'question'=>'Comment avez-vous trouvé le rythme de cette formation ?', 
            'answer'=>$answer9,
        );

        $questionSondages[10] = array(
            'reference'=>'FORMULAIRE-SONDAGE-UMG-Q10',
            'answer_type_id'=> $this->getReference('text'),
            'title'=>null,
            'question'=>'Commentaires :', 
            'answer'=>$answer,
        );

        foreach ($questionSondages as $question) {
            $questionS = new Question();
            $questionS->setRef($question['reference']);
            $questionS->setTitle($question['title']);
            $questionS->setAnswerType($question['answer_type_id']);
            $questionS->setRequired(true);
            $questionS->setQuestion($question['question']);
            $questionS->setPool($this->getReference('poolSondage1'));
            $questionS->setTest($this->getReference('testSondage'));
            $questionS->setCreateDate(new \DateTime('now'));
            $questionS->setUpdateDate(new \DateTime('now'));
            $questionS->setCreateUser($admin);
            $questionS->setUpdateUser($admin);

            $manager->persist($questionS);
            $manager->flush();

            foreach($question['answer'] as $answer){
                $answerS = new Answer();
                $answerS->setQuestion($questionS);
                $answerS->setRef($answer['reference']);
                $answerS->setContent($answer['content']);
                $answerS->setStatus(false);
                $answerS->setCreateDate(new \DateTime('now'));
                $answerS->setUpdateDate(new \DateTime('now'));
                $answerS->setCreateUser($admin);
                $answerS->setUpdateUser($admin);

                $manager->persist($answerS);
                $manager->flush();
            } 
        }

    }

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }
}
