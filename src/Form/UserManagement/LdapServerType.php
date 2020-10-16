<?php

namespace App\Form\UserManagement;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LdapServerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('url', TextType::class, [
         'required' => true])
          ->add('bindDn', TextType::class, [
               'required' => true])
          ->add('password', PasswordType::class, [
               'required' => true])
          ->add('userBaseDn', TextType::class, [
               'required' => true])
          ->add('userObjectClassFilter', TextType::class, [
               'required' => true])
          ->add('userAttributes', TextType::class, [
               'required' => true])
          ->add('usernameAttribute', TextType::class, [
               'required' => true])
          ->add('mailAttribute', TextType::class, [
               'required' => true])
    ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
      'data_class' => "App\Entity\UserManagement\LdapServer",
    ]);
    }
}
