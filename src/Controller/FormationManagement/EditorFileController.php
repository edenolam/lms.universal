<?php

namespace App\Controller\FormationManagement;

use App\Entity\FormationManagement\EditorFile;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route as router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class EditorFileController extends Controller
{
    /**
     * CKEditor images
     * @router("/admin/formationManagement/page/editor/images", name="page_editor_images")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function editorImageAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $editorFiles = $em->getRepository('App\Entity\FormationManagement\EditorFile')->findAll();

        return $this->render('FormationManagement/EditorFile/list.html.twig', [
      'editorFiles' => $editorFiles
    ]);
    }

    /**
     * CKEditor images
     * @router("/admin/formationManagement/page/editor/images/upload", name="page_editor_upload")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function editorImageUploadAction(Request $request)
    {
        $editorFile = new EditorFile();
        $em = $this->getDoctrine()->getManager();

        $editorFile->setFile($request->files->get('upload'));

        $editorFile->setCreateDate();
        $editorFile->setUpdateDate();
        $editorFile->setRevision(1);
        $editorFile->setIsValid(1);

        $em->persist($editorFile);
        $em->flush();

        $response = new JsonResponse();
        $response->setData([
      'uploaded' => 1,
      'fileName' => $editorFile->getUri(),
      'url' => '/uploads/files/' . $editorFile->getUri()
    ]);

        return $response;
    }
}
