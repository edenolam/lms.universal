<?php

namespace App\Controller\PlanningManagement;

use App\Entity\PlanningManagement\Signature;
use App\Form\PlanningManagement\SignatureType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SignatureController extends AbstractController
{
    /**
     * @Route("/planning/management/signature", name="planning_management_signature")
     */
    public function index()
    {
        $em = $this->getDoctrine()->getManager();
        $signatures = $em->getRepository(Signature::class)->findAll();
        return $this->render('PlanningManagement/Signature/list.html.twig', [
            'signatures' => $signatures
        ]);
    }

    /**
     * @Route("admin/planningManagement/signature/create", name="admin_signature_create")
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function new(Request $request)
    {
        $form = $this->createForm(SignatureType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $data = $form->getData();
            $signature = new Signature();
            $signature->setCode($data['code']);
            $em->persist($signature);
            $em->flush();
            $this->addFlash('success', 'signature ajouté avec succes');

            return $this->redirectToRoute('home_dashboard');
        }
        return $this->render('PlanningManagement/Signature/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

}
