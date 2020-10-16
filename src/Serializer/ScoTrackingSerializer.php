<?php

namespace App\Serializer;

use App\Entity\FormationManagement\ScoTracking;
use App\Traits\SerializerTrait;

/**
 * ScoTrackingSerializer
 *
 * @author Free
 */
class ScoTrackingSerializer
{
    use SerializerTrait;

    /** @var ScoSerializer */
    private $scoSerializer;
    /** @var UserSerializer */
    private $userSerializer;

    /**
     * ScoTrackingSerializer constructor.
     *
     *
     * @param ScoSerializer $scoSerializer
     * @param UserSerializer $userSerializer
     */
    public function __construct(ScoSerializer $scoSerializer, UserSerializer $userSerializer)
    {
        $this->scoSerializer = $scoSerializer;
        $this->userSerializer = $userSerializer;
    }

    /**
     * @param ScoTracking $scoTracking
     *
     * @return array
     */
    public function serialize(ScoTracking $scoTracking)
    {
        $sco = $scoTracking->getSco();
        $user = $scoTracking->getUser();

        return [
            'id' => $scoTracking->getUuid(),
            'sco' => empty($sco) ? null : $this->scoSerializer->serialize($sco),
            'user' => empty($user) ? null : $this->userSerializer->serialize($user),
            'scoreRaw' => $scoTracking->getScoreRaw(),
            'scoreMin' => $scoTracking->getScoreMin(),
            'scoreMax' => $scoTracking->getScoreMax(),
            'scoreScaled' => $scoTracking->getScoreScaled(),
            'lessonStatus' => $scoTracking->getLessonStatus(),
            'completionStatus' => $scoTracking->getCompletionStatus(),
            'sessionTime' => $scoTracking->getSessionTime(),
            'totalTime' => $scoTracking->getFormattedTotalTime(),
            'totalTimeInt' => $scoTracking->getTotalTimeInt(),
            'totalTimeString' => $scoTracking->getTotalTimeString(),
            'entry' => $scoTracking->getEntry(),
            'suspendData' => $scoTracking->getSuspendData(),
            'credit' => $scoTracking->getCredit(),
            'exitMode' => $scoTracking->getExitMode(),
            'lessonLocation' => $scoTracking->getLessonLocation(),
            'lessonMode' => $scoTracking->getLessonMode(),
            'isLocked' => $scoTracking->getIsLocked(),
            'details' => $scoTracking->getDetails(),
            'latestDate' => $scoTracking->getLatestDate() ? $scoTracking->getLatestDate()->format('Y-m-d\TH:i:s') : null,
        ];
    }
}
