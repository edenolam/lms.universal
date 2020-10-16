<?php

namespace App\Controller\ResultManagement;

use App\Entity\UserFrontManagement\UserModuleFollow;
use App\Entity\UserFrontManagement\UserModuleFollowFile;
use App\Manager\AuditManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/bilan/userModuleFollow")
 */
class UserModuleFollowFileController extends AbstractController
{
    /**
     * Show list of userModuleFollow file with userModuleFollow id
     * @Route("/list/{userModuleFollow}", name="results_user_module_follow_file_list", methods={"GET","POST"})
     *
     * @Security("has_role('ROLE_GESTION_BILANS')")
     */
    public function list(Request $request, UserModuleFollow $userModuleFollow, TranslatorInterface $translator, SessionInterface $session)
    {
        $em = $this->getDoctrine()->getManager();

        $userModuleFollowFile = new UserModuleFollowFile();

        $form = $this->createForm('App\Form\UserFrontManagement\UserModuleFollowFileType', $userModuleFollowFile);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userModuleFollowFile->setUserModuleFollow($userModuleFollow);
            $em->persist($userModuleFollowFile);
            $em->flush();

            $session->getFlashBag()->add('succès', $translator->trans('ajouté'));

            return $this->redirectToRoute('results_user_module_follow_file_list', [
                'userModuleFollow' => $userModuleFollow->getId(),
            ]);
        }

        return $this->render('ResultManagement/UserModuleFollowFile/list.html.twig', [
            'form' => $form->createView(),
            'user_module_follow' => $userModuleFollow
        ]);
    }

    /**
     * @Route("/update/{userModuleFollowFile}/{action}", name="results_user_module_follow_file_update", methods={"POST"})
     *
     * @Security("is_granted('ROLE_GESTION_BILANS')")
     */
    public function update(Request $request, UserModuleFollowFile $userModuleFollowFile, string $action, TranslatorInterface $translator, SessionInterface $session): JsonResponse
    {
        $entityManager = $this->getDoctrine()->getManager();

        try {
            if ($userModuleFollowFile->getIsValid()) {
                $userModuleFollowFile->setIsValid(false);
            } else {
                $userModuleFollowFile->setIsValid(true);
            }

            $entityManager->persist($userModuleFollowFile);
            $entityManager->flush();

            return new JsonResponse([
                'message' => 'Attachement associées sont mise à jour',
                'success' => 1
            ]);
        } catch (\Doctrine\DBAL\DBALException $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
                'success' => 0
            ]);
        } catch (\Exception $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
                'success' => 0
            ]);
        }

        return new JsonResponse([
            'message' => 'results_user_module_follow_file_update',
            'success' => 1
        ]);
    }

    /**
     * @Route("/edit_flash/{userModuleFollowFile}", name="results_user_module_follow_file_edit_flash", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_BILANS')")
     */
    public function editFlash(Request $request, UserModuleFollowFile $userModuleFollowFile, AuditManager $auditManager, SessionInterface $sfSession)
    {
        $em = $this->getDoctrine()->getManager();

        $output = [];

        $output['id'] = $request->get('pk');
        $output['name'] = $request->get('name');
        $output['value'] = $request->get('value');

        switch ($request->get('name')) {
            case 'name':
                $userModuleFollowFile->setName($request->get('value'));
                break;
            case 'isDownload':
                $userModuleFollowFile->setIsDownload($request->get('value'));
                break;
            default:
                break;
        }
        $em->persist($userModuleFollowFile);
        $em->flush($userModuleFollowFile);

        return new JsonResponse($output, 200);
    }
}
