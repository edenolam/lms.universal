<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends Controller
{
    /**
     * Fonction index
     *
     * @Route("/", name="home", methods="GET")
     */
    public function index()
    {
        if ($this->get('security.authorization_checker')->isGranted('ROLE_APPRENANT')) {
            return $this->redirect($this->generateUrl('home_dashboard'));
        } elseif ($this->get('security.authorization_checker')->isGranted('ROLE_TUTEUR')) {
            return $this->redirect($this->generateUrl('dashboard_tuteur'));
        } elseif ($this->get('security.authorization_checker')->isGranted('ROLE_CONCEPTEUR')) {
            return $this->redirect($this->generateUrl('sondage_view'));
        } elseif ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('admin_user_index'));
        } elseif ($this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            return $this->redirect($this->generateUrl('home_dashboard'));
        } else {
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }

    
    }
}
