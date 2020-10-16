<?php

namespace App\Controller\ResultManagement;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Html2Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AttestationController extends Controller
{
    /**
     * USER attestation génération
     * @Route("/home/attestation/3-{userFormationId}", name="user_formation_download", methods="GET")
     *
     * @Security("has_role('ROLE_USER')")
     */
    public function generetePdfAttestation(Request $request, $userFormationId)
    {
        $em = $this->getDoctrine()->getManager();
        $userFormation = $em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findOneBy(['id' => $userFormationId,]);
                    
        if (isset($userFormation) && $userFormation->getAttestation() != null && $userFormation->getUser() == $this->getUser()) {
            try {
                $html2pdf = new Html2Pdf('P', 'A4', 'fr');
                $html2pdf->pdf->SetDisplayMode('fullpage');
                ob_start();
                $content = ob_get_clean();
                $chemin = $this->get('kernel')->getRootDir() . '/..';
                $content = $this->container->get('templating')->render('ResultManagement/attestation.html.twig', [
                  'chemin' => $chemin,
                  'userFormation' => $userFormation,
                  'userAttestation' => $userFormation->getAttestation()]);
                   
                $attestation = $userFormation->getAttestation();
                if ($this->getUser() == $userFormation->getUser()) {
                    $newDownload = $attestation->getOwnDownload() + 1;
                    $attestation->setOwnDownload($newDownload);
                    $em->persist($attestation);
                    $em->flush();
                } else {
                    $newDownload = $attestation->getManagerDownload() + 1;
                    $attestation->setManagerDownload($newDownload);
                    $em->persist($attestation);
                    $em->flush();
                }
                $html2pdf->pdf->SetTitle('EuroAcademy');
                $html2pdf->writeHTML($content);
                $html2pdf->output('Attestation_' . $userFormation->getFormation()->getTitle() . '_' . date('Y_m_d_H_i_s') . '.pdf');
            } catch (Html2PdfException $e) {
                $html2pdf->clean();
                $formatter = new ExceptionFormatter($e);
                echo $formatter->getHtmlMessage();
            }
        }
       // $user = $this->get('security.token_storage')->getToken()->getUser();
        $userSession = $em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findOneBy([
                'user' => $this->getUser(),
                'session' => $userFormation->getSession()
                ]);
        

        return $this->redirect($this->generateUrl('user_suivis_formation', [
                'slug' => $userSession->getFormation()->getSlug(),
                'slugSession' => $userSession->getSession()->getSlug(),
              ]));
    }
}
