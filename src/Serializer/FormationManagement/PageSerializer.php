<?php

namespace App\Serializer\FormationManagement;

use App\Entity\FormationManagement\Course;
use App\Entity\FormationManagement\Download;
use App\Entity\FormationManagement\Knowledge;
use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\Page;
use App\Entity\LovManagement\PageType;
use App\Persistence\ObjectManager;
use App\Repository\FormationManagement\DownloadRepository;
use App\Repository\FormationManagement\KnowledgeRepository;
use App\Repository\LovManagement\PageTypeRepository;
use Symfony\Component\Filesystem\Filesystem;

/**
 * PageSerializer
 *
 * @author null
 */
class PageSerializer
{
    private $downloadRepository;
    private $pageTypeRepository;
    private $knowledgeRepository;
    private $om;

    public function __construct(DownloadRepository $downloadRepository, PageTypeRepository $pageTypeRepository, KnowledgeRepository $knowledgeRepository, ObjectManager $om)
    {
        $this->pageTypeRepository = $pageTypeRepository;
        $this->downloadRepository = $downloadRepository;
        $this->knowledgeRepository = $knowledgeRepository;
        $this->om = $om;
    }

    /**
     * @param Page $page
     *
     * @return array
     */
    public function serialize(Page $page)
    {
        // use RevisionTrait, DateTrait, FileTrait, AudioFileTrait, IsValidTrait, IsDeletedTrait;
        return [
            'id' => $page->getId(),
            'slug' => $page->getSlug(),
            'reference' => $page->getReference(),
            'title' => $page->getTitle(),
            'inSummary' => $page->getInSummary(),
            'subTitle' => $page->getSubTitle(),
            'sort' => $page->getSort(),
            'description' => $page->getDescription(),
            'contentCode' => $page->getContentCode(),
            'jsCode' => $page->getJsCode(),
            'textualContent' => $page->getTextualContent(),
            'authorLastName' => $page->getAuthorLastName(),
            'authorFirstName' => $page->getAuthorFirstName(),
            'rightFields' => $page->getRightFields(),
            'leftFields' => $page->getLeftFields(),
            'pageType' => $this->pageTypeSerialize($page->getPageType()), // ManyToOne
            // 'pageReferences' => array_map(function (PageReference $pageReference) {
            //     return $this->pageReferenceSerializer->serialize($pageReference); // OneToMany
            // }, $page->getPageReferences()),
            'knowledges' => $this->knowledgesSerialize($page), // ManyToMany knowledges
            'downloads' => $this->downloadsSerialize($page), // ManyToMany downloads
            'revision' => $page->getRevision(), // RevisionTrait
            'isValid' => $page->getIsValid(), // IsValidTrait
            'isDeleted' => $page->getIsDeleted(), //IsDeletedTrait;
            'uri' => $page->getUri(), // FileTrait
            'uploadAt' => $page->getUploadAt(), // FileTrait
            'uriAudio' => $page->getUriAudio(), // AudioFileTrait
            'uploadAtAudio' => $page->getUploadAtAudio(), // AudioFileTrait
            'createDate' => $page->getCreateDate()->format('Y-m-d\TH:i:s'), // DateTrait
            'updateDate' => $page->getUpdateDate()->format('Y-m-d\TH:i:s') // DateTrait
        ];
        // OneToMany pageReferences
        // ManyToOne pageType
        // ManyToMany knowledges
        // ManyToMany downloads
    }

    public function deserialize(array $data, Course $course, Module $module)
    {
        $page = new Page();
        $page->setCourse($course);
        $page->setReference($data['reference']);
        $page->setTitle($data['title']);
        $page->setInSummary($data['inSummary']);
        $page->setSubTitle($data['subTitle']);
        $page->setSort($data['sort']);
        $page->setDescription($data['description']);
        $page->setContentCode($data['contentCode']);
        $page->setJsCode($data['jsCode']);
        $page->setTextualContent($data['textualContent']);
        $page->setAuthorLastName($data['authorLastName']);
        $page->setAuthorFirstName($data['authorFirstName']);
        // ManyToOne pageType
        if (isset($data['pageType']) && isset($data['pageType']['id'])) {
            $pageType = $this->pageTypeRepository->findOneBy([
                'id' => $data['pageType']['id']
            ]);

            if (!empty($pageType)) {
                $page->setPageType($pageType);
            }
        }
        // ManyToMany knowledges
        if (isset($data['knowledges'])) {
            foreach ($data['knowledges'] as $_knowledge) {
                $knowledge = $this->knowledgeRepository->findOneBy([
                    'parent' => $_knowledge['id']
                ]);

                if (!empty($knowledge)) {
                    $page->addKnowledge($knowledge);
                } else {
                    $parent = $this->knowledgeRepository->findOneBy([
                        'id' => $_knowledge['id']
                    ]);
                    $knowledge = new Knowledge();
                    $knowledge->setParent($parent);
                    $knowledge->setTitle($_knowledge['title']);
                    $knowledge->setIsValid($_knowledge['isValid']);
                    $knowledge->setModule($module);
                    $this->om->startFlushSuite();
                    $this->om->persist($knowledge);
                    $this->om->endFlushSuite();
                    $page->addKnowledge($knowledge);
                }
            }
        }
        // ManyToMany downloads
        if (isset($data['downloads'])) {
            foreach ($data['downloads'] as $_download) {
                $download = $this->downloadRepository->findOneBy([
                    'parent' => $_download['id']
                ]);

                if (!empty($download)) {
                    $page->addDownload($download);
                } else {
                    $parent = $this->downloadRepository->findOneBy([
                        'id' => $_download['id']
                    ]);
                    $download = new Download();
                    $download->setParent($parent);
                    $download->setTitle($_download['title']);
                    $download->setIsDownload($_download['isDownload']);
                    $download->setIsValid($_download['isValid']);
                    if ($_download['uri']) {
                        $filesystem = new Filesystem();
                        if ($filesystem->exists('uploads/files/' . $_download['uri'])) {
                            $filesystem->copy('uploads/files/' . $_download['uri'], 'uploads/files/copie_' . $_download['uri'], true);
                            $download->setUri('copie_' . $_download['uri']);
                            $download->setUploadAt(new \DateTime());
                        }
                    }
                    $download->setModule($module);
                    $this->om->startFlushSuite();
                    $this->om->persist($download);
                    $this->om->endFlushSuite();
                    $page->addDownload($download);
                }
            }
        }
        $page->setRightFields($data['rightFields']);
        $page->setLeftFields($data['leftFields']);
        $page->setIsValid($data['isValid']);
        $page->setIsDeleted($data['isDeleted']);
        if ($data['uri']) {
            $filesystem = new Filesystem();
            if ($filesystem->exists('uploads/files/' . $data['uri'])) {
                $filesystem->copy('uploads/files/' . $data['uri'], 'uploads/files/copie_' . $data['uri'], true);
                $page->setUri('copie_' . $data['uri']);
                $page->setUploadAt(new \DateTime());
            }
        }
        if ($data['uriAudio']) {
            $filesystem = new Filesystem();
            if ($filesystem->exists('uploads/files/' . $data['uriAudio'])) {
                $filesystem->copy('uploads/files/' . $data['uriAudio'], 'uploads/files/copie_' . $data['uriAudio'], true);
                $page->setUriAudio('copie_' . $data['uriAudio']);
                $page->setUploadAtAudio(new \DateTime());
            }
        }

        return $page;
    }

    private function downloadsSerialize(Page $page)
    {
        $downloads = [];
        foreach ($page->getDownloads() as $download) {
            array_push($downloads, [
            'id' => $download->getId(),
            'slug' => $download->getSlug(),
            'isDownload' => $download->getIsDownload(),
            'revision' => $download->getRevision(),  // RevisionTrait
            'title' => $download->getTitle(),
            'isValid' => $download->getIsValid(), // IsValidTrait
            'uri' => $download->getUri(), // FileTrait
            'uploadAt' => $download->getUploadAt(), // FileTrait
            'createDate' => $download->getCreateDate()->format('Y-m-d\TH:i:s'), // DateTrait
            'updateDate' => $download->getUpdateDate()->format('Y-m-d\TH:i:s') // DateTrait
        ]);
        }

        return $downloads;
    }

    private function knowledgesSerialize(Page $page)
    {
        $knowledges = [];
        foreach ($page->getKnowledges() as $knowledge) {
            array_push($knowledges, [
            'id' => $knowledge->getId(),
            'slug' => $knowledge->getSlug(),
            'revision' => $knowledge->getRevision(),  // RevisionTrait
            'title' => $knowledge->getTitle(),
            'isValid' => $page->getIsValid(), // IsValidTrait
        ]);
        }

        return $knowledges;
    }

    private function pageTypeSerialize(PageType $pageType)
    {
        return [
            'id' => $pageType->getId(),
            'title' => $pageType->getTitle(),
        ];
    }
}
