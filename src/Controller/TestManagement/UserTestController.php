<?php

namespace App\Controller\TestManagement;

use App\Entity\PlanningManagement\Session;
use App\Entity\TestManagement\Test;
use App\Entity\UserManagement\User;
use App\Manager\AuditManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

/**
 * @Route("/admin/TestManagement/test")
 */
class UserTestController extends AbstractController
{
    /**
     * Show form to edit saved formationPath
     *
     * @Route("/numberTry/{user}/{test}/{session}", name="admin_testManagement_numberTry", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_GESTION_BILANS')")
     */
    public function updateNumberTry(Request $request, User $user, Test $test, Session $session, AuditManager $auditManager, SessionInterface $sfSession, TranslatorInterface $translator, LoggerInterface $logger)
    {
        $em = $this->getDoctrine()->getManager();

        $userTest = $em->getRepository('App\Entity\UserFrontManagement\UserTest')->findByTestLastUser($user->getId(), $test->getId(), $session->getId());
        //$logger->addError($userTest->getTest()->getTitle());   
        $data = json_decode($request->getContent(), true);
        if ($userTest &&  $data['numberTry'] &&  $data['numberTryDescription'] ) {
            $nbTry = (int) $data['numberTry'];
            if($nbTry >= $userTest->getTentative()){
                $userTest->setNumberTry((int) $data['numberTry']);
                $userTest->setNumberTryDescription($data['numberTryDescription']);
                $em->persist($userTest);
                try {
                    $em->flush();
                    return new JsonResponse([
                        'success' => 1]);
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
            } else{
                return new JsonResponse([
                    'success' => null]);
            }
        }else{
            return new JsonResponse([
                'success' => 0]);
        }
        return new JsonResponse([
            'success' => 0]);
    }
}
