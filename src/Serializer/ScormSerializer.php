<?php

namespace App\Serializer;

use App\Entity\FormationManagement\Sco;
use App\Entity\FormationManagement\Scorm;
use App\Traits\SerializerTrait;
use Doctrine\ORM\EntityManagerInterface;

/**
 * ScormSerializer
 *
 * @author Free
 */
class ScormSerializer
{
    use SerializerTrait;

    /** @var EntityManager */
    private $em;
    /** @var ScoSerializer */
    private $scoSerializer;

    private $scoRepo;

    /**
     * ScormSerializer constructor.
     *
     * @param EntityManagerInterface $em
     * @param ScoSerializer $scoSerializer
     */
    public function __construct(EntityManagerInterface $em, ScoSerializer $scoSerializer)
    {
        $this->em = $em;
        $this->scoSerializer = $scoSerializer;
        $this->scoRepo = $em->getRepository('App\Entity\FormationManagement\Sco');
    }

    /**
     * @param Scorm $scorm
     *
     * @return array
     */
    public function serialize(Scorm $scorm)
    {
        return [
            'id' => $scorm->getUuid(),
            'version' => $scorm->getVersion(),
            'hashName' => $scorm->getHashName(),
            'ratio' => $scorm->getRatio(),
            'scos' => $this->serializeScos($scorm),
        ];
    }

    /**
     * @param array $data
     * @param Scorm $scorm
     *
     * @return Scorm
     */
    public function deserialize($data, Scorm $scorm)
    {
        $this->sipe('hashName', 'setHashName', $data, $scorm);
        $this->sipe('version', 'setVersion', $data, $scorm);
        $this->sipe('ratio', 'setRatio', $data, $scorm);

        if (isset($data['scos'])) {
            $this->deserializeScos($data['scos'], $scorm, null);
        }

        return $scorm;
    }

    private function serializeScos(Scorm $scorm)
    {
        return array_map(function (Sco $sco) {
            return $this->scoSerializer->serialize($sco);
        }, $scorm->getRootScos());
    }

    private function deserializeScos($data, Scorm $scorm, Sco $parent = null)
    {
        foreach ($data as $scoData) {
            $sco = $this->scoRepo->findOneBy(['uuid' => $scoData['id']]);

            if (empty($sco)) {
                $sco = $this->deserializeSco($scoData, new Sco(), $scorm, $parent);
                $sco->setScorm($scorm);
                $sco->setScoParent($parent);
                $this->em->persist($sco);
            }
        }
    }

    private function deserializeSco($data, Sco $sco, Scorm $scorm, Sco $parent = null)
    {
        $this->sipe('id', 'setUuid', $data, $sco);
        $this->sipe('data.entryUrl', 'setEntryUrl', $data, $sco);
        $this->sipe('data.identifier', 'setIdentifier', $data, $sco);
        $this->sipe('data.title', 'setTitle', $data, $sco);
        $this->sipe('data.visible', 'setVisible', $data, $sco);
        $this->sipe('data.parameters', 'setParameters', $data, $sco);
        $this->sipe('data.launchData', 'setLaunchData', $data, $sco);
        $this->sipe('data.maxTimeAllowed', 'setMaxTimeAllowed', $data, $sco);
        $this->sipe('data.timeLimitAction', 'setTimeLimitAction', $data, $sco);
        $this->sipe('data.block', 'setBlock', $data, $sco);
        $this->sipe('data.scoreToPassInt', 'setScoreToPassInt', $data, $sco);
        $this->sipe('data.scoreToPassDecimal', 'setScoreToPassDecimal', $data, $sco);
        $this->sipe('data.completionThreshold', 'setCompletionThreshold', $data, $sco);
        $this->sipe('data.prerequisites', 'setPrerequisites', $data, $sco);

        if (isset($data['children']) && 0 < count($data['children'])) {
            $this->deserializeScos($data['children'], $scorm, $sco);
        }

        return $sco;
    }
}
