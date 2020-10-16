<?php

namespace App\Controller\UserFrontManagement;

use App\Entity\UserFrontManagement\{UserTest};
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
// Include the BinaryFileResponse and the ResponseHeaderBag
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
// Include the requires classes of Phpword
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/user")
 */
class UserNoteController extends AbstractController
{
    /**
     * user profile edit
     *
     * @Route("/notes/list", name="user_notes_list",methods={"GET", "POST"}))
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function list(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $userPageNotes = $em->getRepository('App\Entity\UserFrontManagement\UserPageFollow')->findNotesByUser($user);

        $formationPaths = [];
        $modules = [];
        $courses = [];
        $listeNote = [];

        foreach ($userPageNotes as $userNote) {
            $sessionNote = $userNote->getSession();
            $moduleNote = $userNote->getModule();
            if (!array_key_exists($sessionNote->getId(), $listeNote)) {
                $listeNote[$sessionNote->getId()] = ['session' => $sessionNote, 'data' => []];
                if (!array_key_exists($moduleNote->getId(), $listeNote[$sessionNote->getId()]['data'])) {
                    $listeNote[$sessionNote->getId()]['data'][$moduleNote->getId()] = ['module' => $moduleNote, 'data' => []];
                }
            } else {
                if (!array_key_exists($moduleNote->getId(), $listeNote[$sessionNote->getId()]['data'])) {
                    $listeNote[$sessionNote->getId()]['data'][$moduleNote->getId()] = ['module' => $moduleNote, 'data' => []];
                }
            }
            $listeNote[$sessionNote->getId()]['data'][$moduleNote->getId()]['data'][] = $userNote;
        }

        foreach ($userPageNotes as $userPage) {
            if (!in_array($userPage->getSession()->getFormationPath(), $formationPaths)) {
                array_push($formationPaths, $userPage->getSession()->getFormationPath());
            }
            if (!in_array($userPage->getModule(), $modules)) {
                array_push($modules, $userPage->getModule());
            }
            if (!in_array($userPage->getCourse(), $courses)) {
                array_push($courses, $userPage->getCourse());
            }
        }

        return $this->render('UserFrontManagement/notes.html.twig', [
            'listeNote' => $listeNote,
            'notes' => $userPageNotes,
            'formationPaths' => $formationPaths,
            'modules' => $modules,
            'courses' => $courses,
            'formationSlug' =>null,
            'moduleSlug' =>null,
        ]);
    }

    /**
     * Methode export des notes
     *
     * @Route("/notes/export/{slugModule}/{slugSession}", name="export_word_note", methods={"POST","GET"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function export_note(Request $request, $slugModule, $slugSession, TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $module = $em->getRepository('App\Entity\FormationManagement\Module')->findOneBy(['slug' => $slugModule]);
        $session = $em->getRepository('App\Entity\PlanningManagement\Session')->findOneBy(['slug' => $slugSession]);
        $userPageNote = $em->getRepository('App\Entity\UserFrontManagement\UserPageFollow')->findByNote($user, $session, $module);

        // creation du document
        $phpWord = new PhpWord();

        $multilevelStyle = 'multilevel';
        $phpWord->addNumberingStyle(
            $multilevelStyle,
            [
                'type' => 'multilevel',
                'levels' => [
                    ['format' => 'decimal', 'text' => '%1.', 'left' => 360, 'hanging' => 360, 'tabPos' => 360],
                    ['format' => 'upperLetter', 'text' => '%2.', 'left' => 720, 'hanging' => 360, 'tabPos' => 720],
                ],
            ]
        );

        // creation d'une ligne ligne (section)
        $section = $phpWord->addSection();

        //remplissage des lignes avec du contenu
        $header = $section->addHeader();
        $header->addText($translator->trans('userFrontManagement.export.formation') . ' ' . $session->getFormationPath()->getTitle(), ['size' => 14, 'color' => 'black', 'bold' => true]);
        $header->addText($translator->trans('userFrontManagement.export.module') . ' ' . $module->getTitle(), ['size' => 12, 'color' => 'black', 'bold' => true]);

        $section->addTextBreak(2);

        // tableau de  titres des chapitres pour les ordonnancer
        $chapTitle = [];
        foreach ($userPageNote as $userNote) {
            // verifie si chapitre à déja été parcouru
            if (!in_array($userNote->getPage()->getCourse()->getTitle(), $chapTitle)) {
                //sinon affiche le chapitre et ses pages
                $section->addListItem($translator->trans('userFrontManagement.export.chapitre') . ' ' . $userNote->getPage()->getCourse()->getTitle(), 0, null, $multilevelStyle);
                // ce foreach permet de regrouper toutes les notes des pages au sein d'un chapitre pour eiter la repetition des chapitres
                foreach ($userPageNote as $userNotes) {
                    if ($userNote->getPage()->getCourse()->getTitle() == $userNotes->getPage()->getCourse()->getTitle()) {
                        $section->addListItem($translator->trans('userFrontManagement.export.page') . ' ' . $userNotes->getPage()->getTitle(), 1, null, $multilevelStyle);
                        $section->addListItem($userNotes->getNote());
                        $section->addTextBreak(1);
                        // rempli le tableau avec les chapitres déjà parcourus
                        array_push($chapTitle, $userNotes->getPage()->getCourse()->getTitle());
                    }
                }
            }
        }

        // enregistrement du document sous word2007
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');

        $today = new \DateTime();

        // creation d'un fichier temporaire
        $fileName = 'export_note' . $today->format('Y-m-d H:i:s') . '.docx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);

        //ecriture du fichier dans un chemin temporel
        $objWriter->save($temp_file);

        // transfert du fichier tempaire commme pièce jointe
        $response = new BinaryFileResponse($temp_file);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName);

        return $response;
    }

    /**
     * FILTER NOTES WITH AJAX REQUEST
     *
     * @Route("/notes/list/filtre/{formationSlug}/{moduleSlug}/{courseSlug}", name="user_notes_filter",methods={"GET", "POST"}))
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function filter(Request $request, $formationSlug = null, $moduleSlug = null, $courseSlug = null)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $userPageNotes = $em->getRepository('App\Entity\UserFrontManagement\UserPageFollow')->filterNotes($user, $formationSlug, $moduleSlug, $courseSlug);

        $formationPaths = [];
        $modules = [];
        $courses = [];
        $listeNote = [];

        foreach ($userPageNotes as $userNote) {
            $sessionNote = $userNote->getSession();
            $moduleNote = $userNote->getModule();
            if (!array_key_exists($sessionNote->getId(), $listeNote)) {
                $listeNote[$sessionNote->getId()] = ['session' => $sessionNote, 'data' => []];
                if (!array_key_exists($moduleNote->getId(), $listeNote[$sessionNote->getId()]['data'])) {
                    $listeNote[$sessionNote->getId()]['data'][$moduleNote->getId()] = ['module' => $moduleNote, 'data' => []];
                }
            } else {
                if (!array_key_exists($moduleNote->getId(), $listeNote[$sessionNote->getId()]['data'])) {
                    $listeNote[$sessionNote->getId()]['data'][$moduleNote->getId()] = ['module' => $moduleNote, 'data' => []];
                }
            }
            $listeNote[$sessionNote->getId()]['data'][$moduleNote->getId()]['data'][] = $userNote;
        }

        foreach ($userPageNotes as $userPage) {
            if (!in_array($userPage->getSession()->getFormationPath(), $formationPaths)) {
                array_push($formationPaths, $userPage->getSession()->getFormationPath());
            }
            if (!in_array($userPage->getModule(), $modules)) {
                array_push($modules, $userPage->getModule());
            }
            if (!in_array($userPage->getCourse(), $courses)) {
                array_push($courses, $userPage->getCourse());
            }
        }

        return $this->render('UserFrontManagement/notes.html.twig', [
            'listeNote' => $listeNote,
            'notes' => $userPageNotes,
            'formationPaths' => $formationPaths,
            'modules' => $modules,
            'courses' => $courses,
            'formationSlug' =>$formationSlug,
            'moduleSlug' =>$moduleSlug,
        ]);

        
    }

    /**
     * Fonction choisit un test ou une page
     *
     * @Route("/note/add", name="user_note_add", methods={"GET","POST"})
     *
     * @Security("is_granted('ROLE_USER')")
     */
    public function createNote(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $data = json_decode($request->getContent(), true);

        $user = $this->getUser();

        $page = $em->getRepository('App\Entity\FormationManagement\Page')->findOneBy([
            'slug' => $data['pageSlug']
        ]);

        $userPage = $em->getRepository('App\Entity\UserFrontManagement\UserPageFollow')->findOneBy([
            'user' => $user,
            'page' => $page
        ]);

        if ($userPage) {
            if (strlen($data['content']) == 0) {
                $null = null;
                $userPage->setNote($null);
            } else {
                $userPage->setNote($data['content']);
            }
            $em->persist($userPage);

            $em->flush();
        }

        return new Response('OK');
    }
}
