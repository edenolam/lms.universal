<?php

namespace App\Serializer;

use App\Entity\FormationManagement\Sco;
use App\Traits\SerializerTrait;

/**
 * ScoSerializer
 *
 * @author Free
 */
class ScoSerializer
{
    use SerializerTrait;

    /**
     * @param Sco $sco
     *
     * @return array
     */
    public function serialize(Sco $sco)
    {
        $scorm = $sco->getScorm();
        $parent = $sco->getScoParent();

        return [
            'id' => $sco->getUuid(),
            'scorm' => !empty($scorm) ? ['id' => $scorm->getUuid()] : null,
            'data' => [
                'entryUrl' => $sco->getEntryUrl(),
                'identifier' => $sco->getIdentifier(),
                'title' => $sco->getTitle(),
                'visible' => $sco->isVisible(),
                'parameters' => $sco->getParameters(),
                'launchData' => $sco->getLaunchData(),
                'maxTimeAllowed' => $sco->getMaxTimeAllowed(),
                'timeLimitAction' => $sco->getTimeLimitAction(),
                'block' => $sco->isBlock(),
                'scoreToPassInt' => $sco->getScoreToPassInt(),
                'scoreToPassDecimal' => $sco->getScoreToPassDecimal(),
                'scoreToPass' => !empty($scorm) ? $sco->getScoreToPass() : null,
                'completionThreshold' => $sco->getCompletionThreshold(),
                'prerequisites' => $sco->getPrerequisites(),
            ],
            'parent' => !empty($parent) ? ['id' => $parent->getUuid()] : null,
            'children' => array_map(function (Sco $scoChild) {
                return $this->serialize($scoChild);
            }, is_array($sco->getScoChildren()) ? $sco->getScoChildren() : $sco->getScoChildren()->toArray()),
        ];
    }
}
