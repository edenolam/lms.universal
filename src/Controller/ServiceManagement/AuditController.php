<?php

namespace App\Controller\ServiceManagement;

use App\Entity\ServiceManagement\Audit;
use App\Repository\ServiceManagement\AuditRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller principal LOVBundle.
 */
class AuditController extends Controller
{
    /**
     * Affiche les liste d'audit.
     *
     * @Route("/admin/ServiceManagement/audit/list/{entity}", name="admin_audit_list", methods={"GET","HEAD"})
     * @Security("has_role('ROLE_AUDIT')")
     */
    public function index($entity = null, AuditRepository $auditRepository)
    {
        if ($entity == null) {
            $entity = 'Session';
        }

        $audits = $auditRepository->getFilterByEntity($entity);
        $content = array();
        foreach ($audits as $audit) {
            //var_dump($audit->getId());
            $content[$audit->getId()]['curent'] = json_decode($audit->getCurentValue(), true);
            $content[$audit->getId()]['old'] = json_decode($audit->getOldValue(), true);
        }
        //var_dump($content[14]); 
        return $this->render('ServiceManagement/Audit/list.html.twig', [
            'entity' => $entity,
            'audits' => $audits,
            'content' => $content,
        ]);
    }


    /**
     * Affiche l'historique des mise en ligne/concpetion de module
     *
     * @Route("/admin/ServiceManagement/audit/versioningModule/{entity}", name="admin_audit_versioning_module", methods={"GET","HEAD"})
     * @Security("has_role('ROLE_AUDIT')")
     */
    public function versioningModule($entity = null)
    {
        $em = $this->getDoctrine()->getManager();
        $audits = $em->getRepository('App\Entity\FormationManagement\VersioningModule')->findAll();

        return $this->render('ServiceManagement/Audit/versioningModule.html.twig', [
            'entity' => $entity,
            'audits' => $audits,
        ]);
    }

    /**
     * export les liste d'audit.
     * @Route("/admin/ServiceManagement/audit/export/{entity}", name="admin_serviceManagement_audit_export", methods={"GET","HEAD"})
     *  @Security("has_role('ROLE_AUDIT')")
     */
    public function exportAction($entity = 'User')
    {
        $em = $this->getDoctrine()->getManager();
        $audits = $em->getRepository("App\Entity\ServiceManagement\Audit")->findBy(['entityName' => $entity]);

        $content = array();

        foreach ($audits as $audit) {
            //var_dump($audit->getId());
            $content[$audit->getId()]['curent'] = json_decode($audit->getCurentValue(), true);
            $content[$audit->getId()]['old'] = json_decode($audit->getOldValue(), true);
        }

        
        $response = new Response();

        $content = $this->container->get('templating')->render('ServiceManagement/Audit/excel.html.twig', ['audits' => $audits, 'content' => $content]);

        $today = new \Datetime();

        return new Response($content, 200, [
            'Content-Type' => 'application/force-download',
            'Content-Disposition' => 'attachment; filename="export_Audit_' . $entity . '_' . $today->format('Y-m-d H:i:s') . '.xls"'
        ]);
    }

     /**
     * export les liste d'audit.
     * @Route("/admin/ServiceManagement/audit/exportVersioning", name="admin_serviceManagement_audit_export_versioning_module", methods={"GET","HEAD"})
     *  @Security("has_role('ROLE_AUDIT')")
     */
    public function exportVersioningModule()
    {
        $em = $this->getDoctrine()->getManager();
        $audits = $em->getRepository('App\Entity\FormationManagement\VersioningModule')->findAll();
        
        $response = new Response();

        $content = $this->container->get('templating')->render('ServiceManagement/Audit/excelVersioningModule.html.twig', ['audits' => $audits]);

        $today = new \Datetime();

        return new Response($content, 200, [
            'Content-Type' => 'application/force-download',
            'Content-Disposition' => 'attachment; filename="export_Audit_version_module_' . $today->format('Y-m-d H:i:s') . '.xls"'
        ]);
    }
}
