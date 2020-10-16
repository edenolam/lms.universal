<?php

namespace App\Controller\FormationManagement;

use App\Entity\FormationManagement\Lexique;
use App\Form\FormationManagement\LexiqueType;
use App\Manager\AuditManager;
use App\Repository\FormationManagement\LexiqueRepository;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/formationManagement/lexique")
 */
class LexiqueController extends AbstractController
{
    /**
     * @Route("/", name="admin_lexique_index", methods={"GET"})
     *
     * @Security("is_granted('ROLE_GESTION_MODULES')")
     */
    public function index(LexiqueRepository $lexiqueRepository): Response
    {
        return $this->render('FormationManagement/Lexique/index.html.twig', [
            'lexiques' => $lexiqueRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="admin_lexique_new", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_MODULES')")
     */
    public function new(Request $request, SessionInterface $sfSession, AuditManager $auditManager): Response
    {
        $lexique = new Lexique(Uuid::uuid5(Uuid::NAMESPACE_DNS, (new \DateTime())->format('Y-m-d H:i:s')));
        $form = $this->createForm(LexiqueType::class, $lexique);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $entityManager->persist($lexique);

            $entityManager->flush();

            $auditManager->generateAudit(null, $lexique, 'add', $this->getUser());

            $sfSession->getFlashBag()->add('success', 'added');

            return $this->redirectToRoute('user_lexique');
        }

        return $this->render('UserFrontManagement/lexique.html.twig', [
            'lexiques' => $lexiqueRepository->findAll(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="admin_lexique_show", methods={"GET"})
     *
     * @Security("is_granted('ROLE_GESTION_MODULES')")
     */
    public function show(Lexique $lexique): Response
    {
        return $this->render('FormationManagement/Lexique/show.html.twig', [
            'lexique' => $lexique,
        ]);
    }

    /**
     * @Route("/edit/{id}", name="admin_lexique_edit", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_MODULES')")
     */
    public function edit(Request $request, $id, SessionInterface $sfSession, AuditManager $auditManager): Response
    {
        $em = $this->getDoctrine()->getManager();

        $lexique = $em->getRepository('App\Entity\FormationManagement\Lexique')->findOneBy(['id' => $id]);

        $old_entity = clone $lexique;

        $form = $this->createForm(LexiqueType::class, $lexique);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($lexique);
            $em->flush();

            $auditManager->generateAudit($old_entity, $lexique, 'edit', $this->getUser());

            $sfSession->getFlashBag()->add('success', 'updated');

            return $this->redirectToRoute('user_lexique');
        }

        return $this->render('FormationManagement/Lexique/edit.html.twig', [
            'lexique' => $lexique,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="admin_lexique_delete", methods={"DELETE"})
     *
     * @Security("is_granted('ROLE_GESTION_MODULES')")
     */
    public function delete(Request $request, Lexique $lexique, SessionInterface $sfSession, AuditManager $auditManager): Response
    {
        $old_entity = clone $lexique;

        if ($this->isCsrfTokenValid('delete' . $lexique->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($lexique);
            $entityManager->flush();

            $auditManager->generateAudit($old_entity, null, 'delete', $this->getUser());

            $sfSession->getFlashBag()->add('success', 'deleted');
        }

        return $this->redirectToRoute('user_lexique');
    }
}
