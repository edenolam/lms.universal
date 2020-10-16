<?php

namespace App\Manager;

use App\Entity\FormationManagement\Module;
use App\Entity\FormationManagement\Sco;
use App\Entity\FormationManagement\Scorm;
use App\Entity\FormationManagement\ScoTracking;
use App\Entity\PlanningManagement\Session;
use App\Entity\UserManagement\User;
use App\Exception\InvalidScormArchiveException;
use App\Library\{ScormLib};
use App\Persistence\ObjectManager;
use App\Serializer\ScormSerializer;
use App\Serializer\ScoSerializer;
use App\Serializer\ScoTrackingSerializer;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * ScormManager
 *
 * @author null
 */
class ScormManager
{
    private $eventDispatcher;
    private $om;
    private $kernel;

    private $scormLib;
    private $scormSerializer;
    private $scoSerializer;
    private $scoTrackingSerializer;

    private $scoTrackingRepo;
    private $logger;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ObjectManager $om,
        KernelInterface $kernel,
        ScormLib $scormLib,
        ScormSerializer $scormSerializer,
        ScoSerializer $scoSerializer,
        ScoTrackingSerializer $scoTrackingSerializer,
        UserFormationSessionFollowManager $userFormationSessionFollowManager,
        UserModuleFollowManager $userModuleFollowManager,
        LoggerInterface $logger
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->om = $om;
        $this->kernel = $kernel;
        $this->scormLib = $scormLib;
        $this->scormSerializer = $scormSerializer;
        $this->scoSerializer = $scoSerializer;
        $this->scoTrackingSerializer = $scoTrackingSerializer;
        $this->userModuleFollowManager = $userModuleFollowManager;
        $this->userFormationSessionFollowManager = $userFormationSessionFollowManager;
        $this->scoTrackingRepo = $om->getRepository('App\Entity\FormationManagement\ScoTracking');
        $this->logger = $logger;
    }

    /**
     * create a new scorm of a module
     */
    public function createScorm(Module $module, $data)
    {
        $scorm = new Scorm();
        $newScorm = $this->scormSerializer->deserialize($data, $scorm);
        $newScorm->setModule($module);
        $this->om->persist($newScorm);
        $this->om->flush();

        return $this->scormSerializer->serialize($newScorm);
    }

    /**
     * update the scorm, but this create the double sco, bug for fixing it
     */
    public function updateScorm(Scorm $scorm, $data)
    {
        $newScorm = $this->scormSerializer->deserialize($data, $scorm);
        $this->om->persist($newScorm);
        $this->om->flush();

        return $this->scormSerializer->serialize($newScorm);
    }

    public function parseScormArchive(File $file)
    {
        $data = [];
        $contents = '';
        $zip = new \ZipArchive();

        $zip->open($file);
        $stream = $zip->getStream('imsmanifest.xml');

        while (!feof($stream)) {
            $contents .= fread($stream, 2);
        }
        $dom = new \DOMDocument();

        if (!$dom->loadXML($contents)) {
            throw new InvalidScormArchiveException('cannot_load_imsmanifest_message');
        }

        $scormVersionElements = $dom->getElementsByTagName('schemaversion');

        if (1 === $scormVersionElements->length) {
            switch ($scormVersionElements->item(0)->textContent) {
                case '1.2':
                    $data['version'] = Scorm::SCORM_12;
                    break;
                default:
                    //throw new InvalidScormArchiveException('invalid_scorm_version_message');
                    $data['version'] = Scorm::SCORM_12;
                    break;
            }
        } else {
            //throw new InvalidScormArchiveException('invalid_scorm_version_message');
            $data['version'] = Scorm::SCORM_12;
        }
        $scos = $this->scormLib->parseOrganizationsNode($dom);

        if (0 >= count($scos)) {
            throw new InvalidScormArchiveException('no_sco_in_scorm_archive_message');
        }
        $data['scos'] = array_map(function (Sco $sco) {
            return $this->scoSerializer->serialize($sco);
        }, $scos);

        return $data;
    }

    /**
     * get scoTracking
     */
    public function generateScoTracking(Session $session, Sco $sco, User $user)
    {
        $tracking = null;

        if (!is_null($user)) {
            $tracking = $this->scoTrackingRepo->findOneBy(['sco' => $sco, 'user' => $user, 'session' => $session]);
        }
        if (is_null($tracking)) {
            $tracking = $this->createScoTracking($session, $sco, $user);
        }

        return $tracking;
    }

    /**
     * create scoTracking
     */
    public function createScoTracking(Session $session, Sco $sco, User $user)
    {
        $version = $sco->getScorm()->getVersion();
        $scoTracking = new ScoTracking();
        $scoTracking->setSession($session);
        $scoTracking->setSco($sco);

        switch ($version) {
            case Scorm::SCORM_12:
                $scoTracking->setLessonStatus('not attempted');
                $scoTracking->setSuspendData('');
                $scoTracking->setEntry('ab-initio');
                $scoTracking->setLessonLocation('');
                $scoTracking->setProgression(0);
                $scoTracking->setCredit('no-credit');
                $scoTracking->setTotalTimeInt(0);
                $scoTracking->setSessionTime(0);
                $scoTracking->setLessonMode('normal');
                $scoTracking->setExitMode('');

                if (is_null($sco->getPrerequisites())) {
                    $scoTracking->setIsLocked(false);
                } else {
                    $scoTracking->setIsLocked(true);
                }
                break;
        }
        if (!empty($user)) {
            $scoTracking->setUser($user);
            $scoTracking->setLatestDate(new \DateTime());
            $this->om->persist($scoTracking);
            $this->om->flush();

            // update UserFormationSessionFollow
            // and update UserModuleFollow
            $this->userModuleFollowManager->updateUserModuleFollowScorm($session, $sco->getScorm()->getModule(), $user, $scoTracking);
            $this->userFormationSessionFollowManager->updateUserSessionsFormationScorm($session, $sco->getScorm()->getModule(), $user, $scoTracking);
        }

        return $scoTracking;
    }

    /**
     * update scoTracking
     */
    public function updateScoTracking(Session $session, Sco $sco, User $user, $mode, $data)
    {
        $tracking = $this->generateScoTracking($session, $sco, $user);
        $tracking->setLatestDate(new \DateTime());

        switch ($sco->getScorm()->getVersion()) {
            case Scorm::SCORM_12:
                $scoreRaw = isset($data['cmi.core.score.raw']) ? intval($data['cmi.core.score.raw']) : null;
                $scoreMin = isset($data['cmi.core.score.min']) ? intval($data['cmi.core.score.min']) : null;
                $scoreMax = isset($data['cmi.core.score.max']) ? intval($data['cmi.core.score.max']) : null;
                // update nbAttempts
                if (!is_null($scoreRaw)) {
                    $nbAttempts = $tracking->getNbAttempts() ? $tracking->getNbAttempts() : 0;
                    ++$nbAttempts;
                    $tracking->setNbAttempts($nbAttempts);
                }

                $lessonStatus = $data['cmi.core.lesson_status'] ?? null;
                $sessionTime = $data['cmi.core.session_time'] ?? null;
                $progression = $data['cmi.core.progression'] ?? 0;
                $sessionTimeInHundredth = $this->convertTimeInSeconde($sessionTime);
                $tracking->setDetails($data);
                $tracking->setEntry($data['cmi.core.entry']);
                $tracking->setExitMode($data['cmi.core.exit']);
                $tracking->setLessonLocation($data['cmi.core.lesson_location']);
                if (intval($progression) > intval($tracking->getProgression())) {
                    if ($progression > 100) {
                        $progression = 100;
                    }
                    $tracking->setProgression($progression);
                }
                $tracking->setSessionTime($sessionTimeInHundredth);
                $tracking->setSuspendData($data['cmi.suspend_data']);

                if ('log' === $mode) {
                    // update nbAccess
                    $nbAccess = $tracking->getNbAccess() ? $tracking->getNbAccess() : 0;
                    ++$nbAccess;
                    $tracking->setNbAccess($nbAccess);

                    // Compute total time
                    $totalTimeInHundredth = $this->convertTimeInSeconde($data['cmi.core.total_time']);
                    $totalTimeInHundredth += $sessionTimeInHundredth;
                    // Persist total time
                    $tracking->setTotalTime($totalTimeInHundredth);

                    $bestScore = $tracking->getScoreRaw();
                    $bestStatus = $tracking->getLessonStatus();

                    if (empty($bestScore) || (!is_null($scoreRaw) && $scoreRaw > $bestScore)) {
                        $tracking->setScoreRaw($scoreRaw);
                        $bestScore = $scoreRaw;
                    }

                    if ($lessonStatus !== $bestStatus && 'passed' !== $bestStatus && 'completed' !== $bestStatus) {
                        if (('not attempted' === $bestStatus && !empty($lessonStatus)) ||
                             (('browsed' === $bestStatus || 'incomplete' === $bestStatus)
                                && ('failed' === $lessonStatus || 'passed' === $lessonStatus || 'completed' === $lessonStatus)) ||
                             ('failed' === $bestStatus && ('passed' === $lessonStatus || 'completed' === $lessonStatus))
                        ) {
                            $tracking->setLessonStatus($lessonStatus);
                            $bestStatus = $lessonStatus;
                        }
                    }
                    if (empty($lessonStatus) && 'not attempted' === $bestStatus) {
                        $tracking->setLessonStatus('incomplete');
                    }

                    $data['sco'] = $sco->getUuid();
                    $data['lessonStatus'] = $lessonStatus;
                    $data['scoreMax'] = $scoreMax;
                    $tracking->setScoreMax($scoreMax);
                    $data['scoreMin'] = $scoreMin;
                    $tracking->setScoreMin($scoreMin);

                    // update UserFormationSessionFollow
                    // and update UserModuleFollow
                    $this->userModuleFollowManager->updateUserModuleFollowScorm($session, $sco->getScorm()->getModule(), $user, $tracking);
                    $this->userFormationSessionFollowManager->updateUserSessionsFormationScorm($session, $sco->getScorm()->getModule(), $user, $tracking);
                }

                break;
        }

        $this->om->persist($tracking);
        $this->om->flush();

        return $tracking;
    }

    public function unzipScormArchive(Module $module, File $file, $hashName)
    {
        $zip = new \ZipArchive();
        $zip->open($file);
        $ds = DIRECTORY_SEPARATOR;
        $destinationDir = $this->kernel->getProjectDir() . $ds . 'public' . $ds . 'scorm' . $ds . $module->getId() . $ds . $hashName;

        if (!file_exists($destinationDir)) {
            mkdir($destinationDir, 0777, true);
        }
        $zip->extractTo($destinationDir);
        $zip->close();
    }

    // for scrom
    private function convertTimeInSeconde($time)
    {
        $timeInArray = explode(':', $time);
        $timeInArraySec = explode('.', $timeInArray[2]);
        $timeInHundredth = 0;

        if (isset($timeInArraySec[1])) {
            if (1 === strlen($timeInArraySec[1])) {
                $timeInArraySec[1] .= '0';
            }
            $timeInHundredth = intval($timeInArraySec[1]);
        }
        $timeInHundredth += intval($timeInArraySec[0]); // 1s
        $timeInHundredth += intval($timeInArray[1]) * 6000; // 1m
        $timeInHundredth += intval($timeInArray[0]) * 360000; // 1h

        return $timeInHundredth; // ms
    }
}
