<?php

namespace App\Controller\LovManagement;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;


/**
 * LovController
 */
class LovController extends AbstractController
{
    /**
     * Affiche la liste dune LOV (List Of Values) du projet.
     * @Route("/admin/lovManagement/list/{entity}", name="admin_lovManagement_list", methods={"GET","HEAD"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function list($entity = null)
    {
        if ($entity == null) {
            $entity = 'AnswerType';
        }
        $em = $this->getDoctrine()->getManager();
        $listValue = $em->getRepository("App\Entity\LovManagement\\" . $entity)->findAll();

        return $this->render('LovManagement/lov_list.html.twig', [
                'lov' => $listValue,
                'entity' => $entity,
        ]);
    }

    /**
     * Affiche le formulaire modification d'une LOV (List Of Values) du projet.
     * @Route("/admin/lovManagement/edit/{entity}/{id}", name="admin_lovManagement_edit", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function edit(Request $request, $entity, $id, SessionInterface $sfSession, TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();
        $value = $em->getRepository("App\Entity\LovManagement\\" . $entity)->findOneBy(['id' => $id]);
        $form = $this->createForm("App\Form\LovManagement\LovType", $value);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $user = $this->getUser();
                $value->setUpdateUser($user);
                $revision = $value->getRevision() + 1;
                $value->setRevision($revision);
                $value->setUpdateDate(new \Datetime());

                try {
                    $em->persist($value);
                    $em->flush();

                    $sfSession->getFlashBag()->add('success', $translator->trans('lov.flash.updated'));

                    return $this->redirect($this->generateUrl('admin_lovManagement_list', [
                        'entity' => $entity,
                    ]));
                } catch (\Doctrine\DBAL\DBALException $exception) {
                    $sfSession->getFlashBag()->add('error', $exception->getMessage());
                    //$this->get('logger')->err($exception->getMessage());
                }
            }
        }
        $listValue = $em->getRepository("App\Entity\LovManagement\\" . $entity)->findAll();

        return $this->render('LovManagement/lov_edit.html.twig', [
                'value' => $value,
                'form' => $form->createView(),
                'entity' => $entity,
                'lov' => $listValue
        ]);
    }

    /**
     * Affiche le formulaire d'ajout d'une LOV (List Of Values) du projet.
     * @Route("/admin/lovManagement/add/{entity}", name="admin_lovManagement_add", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function add(Request $request, $entity, SessionInterface $sfSession, TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();
        $namespaceEntity = $em->getRepository("App\Entity\LovManagement\\" . $entity)->getClassName();
        $value = new $namespaceEntity();
        $form = $this->createForm("App\Form\LovManagement\LovType", $value);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $user = $this->getUser();
                $value->setCreateUser($user);
                $value->setUpdateUser($user);
                $value->setRevision(0);
                $value->setCreateDate(new \Datetime());
                $value->setUpdateDate(new \Datetime());
                try {
                    $em->persist($value);
                    $em->flush();
                    $sfSession->getFlashBag()->add('success', $translator->trans('lov.flash.created'));

                    return $this->redirect($this->generateUrl('admin_lovManagement_list', [
                            'entity' => $entity,
                        ]));
                } catch (\Doctrine\DBAL\DBALException $exception) {
                    $sfSession->getFlashBag()->add('error', $exception->getMessage());
                }
            }
        }

        $listValue = $em->getRepository("App\Entity\LovManagement\\" . $entity)->findAll();

        return $this->render('LovManagement/lov_add.html.twig', [
                'value' => $value,
                'form' => $form->createView(),
                'entity' => $entity,
                'lov' => $listValue
        ]);
    }

    /**
     * Desactive logiquement une valeur de LOV
     * @Route("/admin/lovManagement/disable/{entity}/{id}", name="admin_lovManagement_disable", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function disable($entity, $id, SessionInterface $sfSession, TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();
        $valueOnList = $em->getRepository("App\Entity\LovManagement\\" . $entity)->findOneBy(['id' => $id]);

        $user = $this->getUser();
        $valueOnList->setUpdateUser($user);
        $valueOnList->setUpdateDate(new \Datetime());

        if ($valueOnList->getIsValid()) {
            $valueOnList->setIsValid(false);
        } else {
            $valueOnList->setIsValid(true);
        }

        try {
            $em->persist($valueOnList);
            $em->flush();
            $sfSession->getFlashBag()->add('success', $translator->trans('lov.flash.created'));
        } catch (\Doctrine\DBAL\DBALException $exception) {
            $sfSession->getFlashBag()->add('error', $exception->getMessage());
        }

        return $this->redirect($this->generateUrl('admin_lovManagement_list', [
            'entity' => $entity,
        ]));
    }

    /**
     * Retourner la bouton enabled and disabled
     * Attention à ajouter dans la fonction "getCountUse" des repositories de chaque lov les endroits ou cette lov peut être utilisée
     * @Route("/admin/lovManagement/onoff/{entity}/{id}", name="admin_lovManagement_onoff", methods={"GET","POST"})
     * @Security("is_granted('ROLE_ADMIN')")
     */
    public function onOff($id, $entity)
    {
        $em = $this->getDoctrine()->getManager();
        $lov = $em->getRepository("App\Entity\LovManagement\\" . $entity)->findOneBy(['id' => $id]);
        $isValid = $lov->getIsValid();

        $nbUsed = $em->getRepository("App\Entity\LovManagement\\" . $entity)->getCountUse($lov);

        return $this->render('LovManagement/lov_onoff.html.twig', [
                'nbUsed' => $nbUsed,
                'id' => $id,
                'isValid' => $isValid,
                'entity' => $entity,
        ]);
    }
}
