<?php

namespace App\Controller\ResultManagement;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Html2Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class CertificationController extends Controller
{
    /**
     * USER certificat génération
     * @Route("/home/certification/EuroAcademy-{userModuleId}", name="user_certificat_download", methods="GET")
     *
     * @Security("has_role('ROLE_USER')")
     */
    public function generetePdfCertificatAction(Request $request, $userModuleId, TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();
        $userModule = $em->getRepository('App\Entity\UserFrontManagement\UserModuleFollow')->findOneBy([
                    'id' => $userModuleId]);
        
        if (isset($userModule) && $userModule->getCertificat() != null && $userModule->getSuccess() == true && $userModule->getUser() == $this->getUser()) {
            
            try {
                $html2pdf = new Html2Pdf('L', 'A4', 'fr');
                $html2pdf->pdf->SetDisplayMode('fullpage');
                ob_start();
                $content = ob_get_clean();
                $chemin = $this->get('kernel')->getRootDir() . '/..';
                //var_dump($chemin);
                //exit();
                
                $content = $this->container->get('templating')->render('ResultManagement/certificat.html.twig', [
                  'chemin' => $chemin,
                  'userModule' => $userModule,
                  'userCertificat' => $userModule->getCertificat()]);
                $certificat = $userModule->getCertificat();
               
                if ($this->getUser() == $userModule->getUser()) {
                    $newDownload = $certificat->getOwnDownload() + 1;
                    $certificat->setOwnDownload($newDownload);
                    $em->persist($certificat);
                    $em->flush();
                } else {
                    $newDownload = $certificat->getManagerDownload() + 1;
                    $certificat->setManagerDownload($newDownload);
                    $em->persist($certificat);
                    $em->flush();
                }
                $html2pdf->pdf->SetTitle('EuroAcademy');
                $html2pdf->writeHTML($content);
                $html2pdf->output('Certificat_' . $userModule->getModule()->getTitle() . '_' . date('Y_m_d_H_i_s') . '.pdf');
            } catch (Html2PdfException $e) {
                $html2pdf->clean();
                $formatter = new ExceptionFormatter($e);
                echo $formatter->getHtmlMessage();
            }
        }
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $userSession = $em->getRepository('App\Entity\UserFrontManagement\UserFormationSessionFollow')->findOneBy([
                'user' => $user,
                'session' => $userModule->getSession()
                ]);
        return $this->redirect($this->generateUrl('user_suivis_formation', [
                'slug' => $userSession->getSlug(),
                'slugSession' => $userSession->getSession()->getSlug(),
              ]));
    }
}
