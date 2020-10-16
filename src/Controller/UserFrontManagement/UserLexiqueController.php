<?php

namespace App\Controller\UserFrontManagement;

use App\Entity\FormationManagement\Lexique;
use App\Form\FormationManagement\LexiqueType;
use App\Repository\FormationManagement\LexiqueRepository;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user")
 */
class UserLexiqueController extends AbstractController
{
    /**
     * SHOW LEXIQUE BY USER & MODULES
     *
     * @Route("/lexique", name="user_lexique", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function lexique(Request $request, LexiqueRepository $lexiqueRepository): Response
    {
        $lexique = new Lexique(Uuid::uuid5(Uuid::NAMESPACE_DNS, (new \DateTime())->format('Y-m-d H:i:s')));
        $form = $this->createForm(LexiqueType::class, $lexique);

        return $this->render('UserFrontManagement/lexique.html.twig', [
            'lexiques' => $lexiqueRepository->findBy(['isValid' => 1], ['title' => 'ASC']),
            'form' => $form->createView(),
        ]);
    }
}
