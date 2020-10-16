<?php

namespace App\Serializer\FormationManagement;

use App\Entity\FormationManagement\Course;

/**
 * CourseSerializer
 *
 * @author null
 */
class CourseSerializer
{
    private $pageSerializer;

    public function __construct(PageSerializer $pageSerializer)
    {
        $this->pageSerializer = $pageSerializer;
    }

    /**
     * @param Course $course
     *
     * @return array
     */
    public function serialize(Course $course)
    {
        return [
                'id' => $course->getId(),
                'slug' => $course->getSlug(),
                'title' => $course->getTitle(),
                'description' => $course->getDescription(),
                'reference' => $course->getReference(),
                'revision' => $course->getRevision(),
                'isValid' => $course->getIsValid(), //IsValidTrait,
                'isDeleted' => $course->getIsDeleted(), //IsDeletedTrait;
                'createDate' => $course->getCreateDate()->format('d/m/Y H:i'),
                'updateDate' => $course->getUpdateDate()->format('d/m/Y H:i')
            ];
        // OneToMany pages
        // OneToMany moduleCourses
    }

    public function deserialize(array $data)
    {
        $course = new Course();
        $course->setReference($data['reference']);
        $course->setTitle($data['title']);
        $course->setDescription($data['description']);
        $course->setRevision(0);
        $course->setIsValid($data['isValid']);
        $course->setIsDeleted($data['isDeleted']);

        return $course;
    }
}
