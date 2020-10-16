<?php
namespace App\Controller\UserManagement;


use FOS\UserBundle\Model\UserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Controller\ProfileController as BaseController;

/**
 * Controller managing the user profile.
 */
class ProfileController extends BaseController
{

    /**
     * Affiche le profil
     *
     * @Route("/user/profil", name="user_profil", methods={"GET", "POST"})
     *
     * @Security("has_role('ROLE_USER')")
     */
    public function showProfilAction()
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        if ($this->get('security.authorization_checker')->isGranted('ROLE_RESPONSABLE_FORMATION')) {
            $roles = 'respoFormation';
        }elseif ($this->get('security.authorization_checker')->isGranted('ROLE_TUTEUR')) { 
            $roles='tuteur';
        }else{
            $roles = 'autre';
        }

        $tutorFollow = $em->getRepository('App\Entity\UserManagement\User')->findApprenantFollowBy($user, $roles);
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        return $this->render('UserManagement/Profile/show_content.html.twig', array(
            'user' => $user,
            'tutorFollow' => $tutorFollow
        ));
    }

    

   
}
