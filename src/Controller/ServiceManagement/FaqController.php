<?php

namespace App\Controller\ServiceManagement;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller principal LOVBundle.
 */
class FaqController extends Controller
{
    /**
     * Affiche les FAQ.
     *
     * @Route("/faq", name="lms_faq", methods={"GET","HEAD"})
     * @Security("has_role('ROLE_USER')")
     */
    public function faq()
    {
        return $this->render('ServiceManagement/faq.html.twig');
    }
}
