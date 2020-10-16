<?php

namespace App\Library;

use App\Entity\FormationManagement\Sco;
use App\Exception\InvalidScormArchiveException;

/**
 * ScormManager
 *
 * @author Free
 */
class ScormLib
{
    /**
     * Looks for the organization to use.
     *
     * @param \DOMDocument $dom
     *
     * @return array of Sco
     *
     * @throws InvalidScormArchiveException If a default organization
     *                                      is defined and not found
     */
    public function parseOrganizationsNode(\DOMDocument $dom)
    {
        $organizationsList = $dom->getElementsByTagName('organizations');
        $resources = $dom->getElementsByTagName('resource');

        if ($organizationsList->length > 0) {
            $organizations = $organizationsList->item(0);
            $organization = $organizations->firstChild;

            if (!is_null($organizations->attributes)
                && !is_null($organizations->attributes->getNamedItem('default'))) {
                $defaultOrganization = $organizations->attributes->getNamedItem('default')->nodeValue;
            } else {
                $defaultOrganization = null;
            }
            // No default organization is defined
            if (is_null($defaultOrganization)) {
                while (!is_null($organization)
                    && 'organization' !== $organization->nodeName) {
                    $organization = $organization->nextSibling;
                }

                if (is_null($organization)) {
                    return $this->parseResourceNodes($resources);
                }
            }
            // A default organization is defined
            // Look for it
            else {
                while (!is_null($organization)
                    && ('organization' !== $organization->nodeName
                        || is_null($organization->attributes->getNamedItem('identifier'))
                        || $organization->attributes->getNamedItem('identifier')->nodeValue !== $defaultOrganization)) {
                    $organization = $organization->nextSibling;
                }

                if (is_null($organization)) {
                    throw new InvalidScormArchiveException('default_organization_not_found_message');
                }
            }

            return $this->parseItemNodes($organization, $resources);
        } else {
            throw new InvalidScormArchiveException('no_organization_found_message');
        }
    }

    /**
     * Creates defined structure of SCOs.
     *
     * @param \DOMNode $source
     * @param \DOMNodeList $resources
     * @param Sco $parentSco
     *
     * @return array of Sco
     *
     * @throws InvalidScormArchiveException
     */
    private function parseItemNodes(\DOMNode $source, \DOMNodeList $resources, Sco $parentSco = null)
    {
        $item = $source->firstChild;
        $scos = [];

        while (!is_null($item)) {
            if ('item' === $item->nodeName) {
                $sco = new Sco();
                $scos[] = $sco;
                $sco->setScoParent($parentSco);
                $this->findAttrParams($sco, $item, $resources);
                $this->findNodeParams($sco, $item->firstChild);

                if ($sco->isBlock()) {
                    $sco->setScoChildren($this->parseItemNodes($item, $resources, $sco));
                }
            }
            $item = $item->nextSibling;
        }

        return $scos;
    }

    private function parseResourceNodes(\DOMNodeList $resources)
    {
        $scos = [];

        foreach ($resources as $resource) {
            if (!is_null($resource->attributes)) {
                $scormType = $resource->attributes->getNamedItemNS(
                    $resource->lookupNamespaceUri('adlcp'),
                    'scormType'
                );

                if (!is_null($scormType) && 'sco' === $scormType->nodeValue) {
                    $identifier = $resource->attributes->getNamedItem('identifier');
                    $href = $resource->attributes->getNamedItem('href');

                    if (is_null($identifier)) {
                        throw new InvalidScormArchiveException('sco_with_no_identifier_message');
                    }
                    if (is_null($href)) {
                        throw new InvalidScormArchiveException('sco_resource_without_href_message');
                    }
                    $sco = new Sco();
                    $sco->setBlock(false);
                    $sco->setVisible(true);
                    $sco->setIdentifier($identifier->nodeValue);
                    $sco->setTitle($identifier->nodeValue);
                    $sco->setEntryUrl($href->nodeValue);
                    $scos[] = $sco;
                }
            }
        }

        return $scos;
    }

    /**
     * Initializes parameters of the SCO defined in attributes of the node.
     * It also look for the associated resource if it is a SCO and not a block.
     *
     * @param Sco $sco
     * @param \DOMNode $item
     * @param \DOMNodeList $resources
     *
     * @throws InvalidScormArchiveException
     */
    private function findAttrParams(Sco $sco, \DOMNode $item, \DOMNodeList $resources)
    {
        $identifier = $item->attributes->getNamedItem('identifier');
        $isVisible = $item->attributes->getNamedItem('isvisible');
        $identifierRef = $item->attributes->getNamedItem('identifierref');
        $parameters = $item->attributes->getNamedItem('parameters');

        // throws an Exception if identifier is undefined
        if (is_null($identifier)) {
            throw new InvalidScormArchiveException('sco_with_no_identifier_message');
        }
        $sco->setIdentifier($identifier->nodeValue);

        // visible is true by default
        if (!is_null($isVisible) && 'false' === $isVisible) {
            $sco->setVisible(false);
        } else {
            $sco->setVisible(true);
        }

        // set parameters for SCO entry resource
        if (!is_null($parameters)) {
            $sco->setParameters($parameters->nodeValue);
        }

        // check if item is a block or a SCO. A block doesn't define identifierref
        if (is_null($identifierRef)) {
            $sco->setBlock(true);
        } else {
            $sco->setBlock(false);
            // retrieve entry URL
            $sco->setEntryUrl($this->findEntryUrl($identifierRef->nodeValue, $resources));
        }
    }

    /**
     * Initializes parameters of the SCO defined in children nodes.
     *
     * @param Sco $sco
     * @param \DOMNode $item
     */
    private function findNodeParams(Sco $sco, \DOMNode $item)
    {
        while (!is_null($item)) {
            switch ($item->nodeName) {
                case 'title':
                    $sco->setTitle($item->nodeValue);
                    break;
                case 'adlcp:masteryscore':
                    $sco->setScoreToPassInt($item->nodeValue);
                    break;
                case 'adlcp:maxtimeallowed':
                case 'imsss:attemptAbsoluteDurationLimit':
                    $sco->setMaxTimeAllowed($item->nodeValue);
                    break;
                case 'adlcp:timelimitaction':
                case 'adlcp:timeLimitAction':
                    $action = strtolower($item->nodeValue);

                    if ('exit,message' === $action
                        || 'exit,no message' === $action
                        || 'continue,message' === $action
                        || 'continue,no message' === $action) {
                        $sco->setTimeLimitAction($action);
                    }
                    break;
                case 'adlcp:datafromlms':
                case 'adlcp:dataFromLMS':
                    $sco->setLaunchData($item->nodeValue);
                    break;
                case 'adlcp:prerequisites':
                    $sco->setPrerequisites($item->nodeValue);
                    break;
                case 'imsss:minNormalizedMeasure':
                    $sco->setScoreToPassDecimal($item->nodeValue);
                    break;
                case 'adlcp:completionThreshold':
                    if ($item->nodeValue) {
                        $sco->setCompletionThreshold($item->nodeValue);
                    } else {
                        if ($item->getAttribute('minProgressMeasure')) {
                            $sco->setCompletionThreshold($item->getAttribute('minProgressMeasure'));
                        }
                    }
                    break;
                case 'imsss:sequencing':
                    $sequencing_data = [];
                    foreach ($item->childNodes as $sequencing) {
                        if ($sequencing->nodeName == 'imsss:controlmode') {
                            if ($sequencing->attributes->getNamedItem('choice')) {
                                $sequencing_data['choice'] = $sequencing->attributes->getNamedItem('choice') == 'true' ? 1 : 0;
                            }
                            if ($sequencing->attributes->getNamedItem('choiceexit')) {
                                $sequencing_data['choiceexit'] = $sequencing->attributes->getNamedItem('choiceexit') == 'true' ? 1 : 0;
                            }
                            if ($sequencing->attributes->getNamedItem('flow')) {
                                $sequencing_data['flow'] = $sequencing->attributes->getNamedItem('flow') == 'true' ? 1 : 0;
                            }
                            if ($sequencing->attributes->getNamedItem('forwardonly')) {
                                $sequencing_data['forwardonly'] = $sequencing->attributes->getNamedItem('forwardonly') == 'true' ? 1 : 0;
                            }
                            if ($sequencing->attributes->getNamedItem('usecurrentattemptobjectinfo')) {
                                $sequencing_data['usecurrentattemptobjectinfo'] = $sequencing->attributes->getNamedItem('usecurrentattemptobjectinfo') == 'true' ? 1 : 0;
                            }
                            if ($sequencing->attributes->getNamedItem('usecurrentattemptprogressinfo')) {
                                $sequencing_data['usecurrentattemptprogressinfo'] = $sequencing->attributes->getNamedItem('usecurrentattemptprogressinfo') == 'true' ? 1 : 0;
                            }
                        }
                        if ($sequencing->nodeName == 'imsss:deliverycontrols') {
                            if ($sequencing->attributes->getNamedItem('tracked')) {
                                $sequencing_data['tracked'] = $sequencing->attributes->getNamedItem('tracked') == 'true' ? 1 : 0;
                            }
                            if ($sequencing->attributes->getNamedItem('completionsetbycontent')) {
                                $sequencing_data['completionsetbycontent'] = $sequencing->attributes->getNamedItem('completionsetbycontent') == 'true' ? 1 : 0;
                            }
                            if ($sequencing->attributes->getNamedItem('objectivesetbycontent')) {
                                $sequencing_data['objectivesetbycontent'] = $sequencing->attributes->getNamedItem('objectivesetbycontent') == 'true' ? 1 : 0;
                            }
                        }
                        if ($sequencing->nodeName == 'adlseq:constrainedchoiceconsiderations') {
                            if ($sequencing->attributes->getNamedItem('constrainchoice')) {
                                $sequencing_data['constrainchoice'] = $sequencing->attributes->getNamedItem('constrainchoice') == 'true' ? 1 : 0;
                            }
                            if ($sequencing->attributes->getNamedItem('preventactivation')) {
                                $sequencing_data['preventactivation'] = $sequencing->attributes->getNamedItem('preventactivation') == 'true' ? 1 : 0;
                            }
                        }
                        if ($sequencing->nodeName == 'imsss:objectives') {
                            $objectives = [];
                            foreach ($sequencing->childNodes as $objective) {
                                $objectivedata = new \stdClass();
                                $objectivedata->primaryobj = 0;
                                switch ($objective->nodeName) {
                                    case 'imsss:primaryObjective':
                                        $objectivedata->primaryobj = 1;
                                        // no break
                                    case 'imsss:objective':
                                        $objectivedata->satisfiedbymeasure = 0;
                                        if ($objective->attributes->getNamedItem('satisfiedbymeasure')) {
                                            $objectivedata->satisfiedbymeasure =
                                                $objective->attributes->getNamedItem('satisfiedbymeasure') == 'true' ? 1 : 0;
                                        }
                                        $objectivedata->objectiveid = '';
                                        if ($objective->attributes->getNamedItem('objectiveid')) {
                                            $objectivedata->objectiveid = $objective->attributes->getNamedItem('objectiveid');
                                        }
                                        break;
                                }
                                array_push($objectives, $objectivedata);
                            }
                        }
                    }
                    if (isset($objectives)) {
                        $sco->setObjectives($objectives);
                    }
                    break;
            }
            $item = $item->nextSibling;
        }
    }

    /**
     * Searches for the resource with the given id and retrieve URL to its content.
     *
     * @param string $identifierref id of the resource associated to the SCO
     * @param \DOMNodeList $resources
     *
     * @return string URL to the resource associated to the SCO
     *
     * @throws InvalidScormArchiveException
     */
    public function findEntryUrl($identifierref, \DOMNodeList $resources)
    {
        foreach ($resources as $resource) {
            $identifier = $resource->attributes->getNamedItem('identifier');

            if (!is_null($identifier)) {
                $identifierValue = $identifier->nodeValue;

                if ($identifierValue === $identifierref) {
                    $href = $resource->attributes->getNamedItem('href');

                    if (is_null($href)) {
                        throw new InvalidScormArchiveException('sco_resource_without_href_message');
                    }

                    return $href->nodeValue;
                }
            }
        }
        throw new InvalidScormArchiveException('sco_without_resource_message');
    }
}
