<?php

namespace App\Controller\UserManagement;

use App\Entity\UserManagement\Group;
use App\Entity\UserManagement\LdapServer;
use App\Entity\UserManagement\User;
use FOS\UserBundle\Model\GroupManagerInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * Class LdapController
 *
 * @Route("/admin/ldap")
 */
class LdapController extends AbstractController
{
    /**
     * ldap server config view
     *
     * @Route("/view", name="admin_ldap_view", methods={"GET"})
     *
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function view(Request $request, TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();

        $ldap_server = $em->getRepository("App\Entity\UserManagement\LdapServer")->findOneBy(['laboratory' => $this->getUser()->getLaboratory()->getLdapServer()]);

        if (!$ldap_server) {
            return $this->redirect($this->generateUrl('admin_ldap_add'));
        }

        return $this->render('UserManagement/Ldap/view.html.twig', [
            'ldap' => $ldap_server,
            'entity' => 'ldap',
        ]);
    }

    /**
     * Affiche le formulaire d'ajout d'un ldap (List Of Values) du projet.
     *
     * @Route("/add", name="admin_ldap_add", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function add(Request $request, LoggerInterface $logger, TranslatorInterface $translator, SessionInterface $sfSession)
    {
        $em = $this->getDoctrine()->getManager();

        $ldap_server = new LdapServer(Uuid::uuid5(Uuid::NAMESPACE_DNS, (new \DateTime())->format('Y-m-d H:i:s')));

        $form = $this->createForm("App\Form\UserManagement\LdapServerType", $ldap_server);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $ldap_server->setLaboratory($this->getUser()->getLaboratory());
                $ldap_server->setCreateUser($this->getUser());
                $ldap_server->setUpdateUser($this->getUser());

                try {
                    $em->persist($ldap_server);
                    $em->flush();

                    $sfSession->getFlashBag()->add('success', $translator->trans('ldap_created'));

                    return $this->redirect($this->generateUrl('admin_ldap_view'));
                } catch (\Exception $exception) {
                    $sfSession->getFlashBag()->add('error', $exception->getMessage());
                    $logger->addError($exception->getMessage());
                }
            }
        }

        return $this->render('UserManagement/Ldap/add.html.twig', [
                'form' => $form->createView(),
                'entity' => 'ldap',
        ]);
    }

    /**
     * Affiche le formulaire modification d'un ldap (List Of Values) du projet.
     *
     * @Route("/edit", name="admin_ldap_edit", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function edit(Request $request, LoggerInterface $logger, TranslatorInterface $translator, SessionInterface $sfSession)
    {
        $em = $this->getDoctrine()->getManager();

        $ldap_server = $em->getRepository("App\Entity\UserManagement\LdapServer")->findOneBy(['laboratory' => $this->getUser()->getLaboratory()->getLdapServer()]);

        if (!$ldap_server) {
            return $this->redirect($this->generateUrl('admin_ldap_add'));
        }

        $form = $this->createForm("App\Form\UserManagement\LdapServerType", $ldap_server);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $ldap_server->setUpdateUser($this->getUser());

                try {
                    $em->persist($ldap_server);
                    $em->flush();

                    $sfSession->getFlashBag()->add('success', $translator->trans('ldap_updated'));

                    return $this->redirect($this->generateUrl('admin_ldap_view'));
                } catch (\Exception $exception) {
                    $sfSession->getFlashBag()->add('error', $exception->getMessage());
                    $logger->addError($exception->getMessage());
                }
            }
        }

        return $this->render('UserManagement/Ldap/edit.html.twig', [
                'form' => $form->createView(),
                'entity' => 'ldap',
        ]);
    }

    /**
     * add users from ldap server
     *
     * @Route("/import", name="admin_ldap_import", methods={"GET"})
     *
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function import(Request $request, UserManagerInterface $userManager, GroupManagerInterface $groupManager, TranslatorInterface $translator, LoggerInterface $logger, SessionInterface $sfSession)
    {
        $em = $this->getDoctrine()->getManager();

        $ldap_server = $em->getRepository("App\Entity\UserManagement\LdapServer")->findOneBy(['laboratory' => $this->getUser()->getLaboratory()->getLdapServer()]);

        if (!$ldap_server) {
            return $this->redirect($this->generateUrl('admin_ldap_add'));
        }

        $output = [];

        try {
            // connection exception
	    $ldap = Ldap::create('ext_ldap', ['connection_string' => $ldap_server->getUrl()]);
	    //var_dump( $ldap_server->getUrl(),$ldap_server->getBindDn(), $ldap_server->getPassword());		
            $ldap->bind($ldap_server->getBindDn(), $ldap_server->getPassword());
            // query exception
            $query = $ldap->query($ldap_server->getUserBaseDn(), $ldap_server->getUserObjectClassFilter());
            $results = $query->execute()->toArray();
            //var_dump($results);
	    //exit;
            $i = 0;
	    foreach ($results as $entry) {
                // code à review
                $new_user = $userManager->createUser();
                $new_user->setLaboratory($this->getUser()->getLaboratory());
                $new_user->setEnabled(true);
                $new_user->addRole(User::ROLE_USER);
                $new_user->addGroup($groupManager->findGroupBy(['id' => Group::BASIC_GROUP]));
                $new_user->setUserDn($entry->getDn());
                $new_user->setLdapUser(1);

                $uid = $entry->getAttribute($ldap_server->getUsernameAttribute());
                $givenName = $entry->getAttribute('givenName');
                $cn = $entry->getAttribute('cn');
                $sn = $entry->getAttribute('sn');
                $email = $entry->getAttribute($ldap_server->getMailAttribute());
                // echo $ldap_server->getMailAttribute();
                // echo "<pre>";var_dump($email); exit();
                if (!empty($givenName[0])) {
                    $new_user->setFirstname($givenName[0]);
                    $output[$i][0] = $givenName[0];
                }
                if (!empty($sn[0])) {
                    $new_user->setLastname($sn[0]);
                    $output[$i][1] = $sn[0];
                }
                if (!empty($email[0])) {
                    $new_user->setEmail($email[0]);
                    $output[$i][2] = $email[0];
                }
                $new_user->setUsername($uid[0]);
                $output[$i][3] = $uid[0];

                // generate identifier only once, here a 6 characters length code
                $password = substr(md5(uniqid(rand(), true)), 0, 6);
                $new_user->setPlainPassword($password);
		
                if ($userManager->findUserByUsernameOrEmail($uid[0]) || $userManager->findUserByUsernameOrEmail($email[0])) {
                    $output[$i][4] = $translator->trans('existed');
                }

                if (empty($output[$i][4]) && $uid[0] != "" && $givenName[0] != "" && $sn[0] != "" && $email[0] != "") {
                    $userManager->updateUser($new_user);
                    $output[$i][4] = $translator->trans('added');
		}
		 
                $i++;
            }

            $sfSession->getFlashBag()->add('success', $translator->trans('registration.flash.user_created', [], 'FOSUserBundle'));
        } catch (ConnectionException $e) {
            throw new BadCredentialsException('Could not connect to server');
	} catch (\Exception $exception) {
	    echo "<br>".$exception->getMessage();
            $sfSession->getFlashBag()->add('error', $exception->getMessage());
            $logger->err($exception->getMessage());
	}
	//echo "<br>Sortie";
	//exit;
        return $this->render('UserManagement/Ldap/import.html.twig', [
            'output' => $output,
            'results' => $results,
            'ldap_server' => $ldap_server,
            'entity' => 'ldap',
        ]);
    }
}
