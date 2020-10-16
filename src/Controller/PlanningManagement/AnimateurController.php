<?php

namespace App\Controller\PlanningManagement;

use App\Entity\PlanningManagement\Animateur;
use App\Form\PlanningManagement\AnimateurType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AnimateurController extends AbstractController
{
    /**
     * @Route( "/admin/planningManagement/animateur/list", name="admin_animateur_list")
     * @param Request $request
     * @return
     */
    public function index(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $animateurs = $em->getRepository(Animateur::class)->findAll();
        return $this->render("PlanningManagement/Session/edit_presentiel.html.twig", [
            'animateurs' => $animateurs
        ]);

    }

    /**
     * @Route( "/admin/planningManagement/animateur/create", name="admin_animateur_create")
     * @param Request $request
     * @return Response
     */
    public function new(Request $request)
    {
        $animateur = new Animateur();
        $em = $this->getDoctrine()->getManager();
        $form = $this->createForm(AnimateurType::class, $animateur);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $animateur = $form->getData();
            $em->persist($animateur);
            $em->flush();
            $this->addFlash('success', 'Animateur ajouté avec succes');

            return $this->redirectToRoute('admin_session_edit_presentiel');
        }
        return $this->render('PlanningManagement/Animateur/new.html.twig', [
            'form' => $form->createView()
        ]);
    }


}
