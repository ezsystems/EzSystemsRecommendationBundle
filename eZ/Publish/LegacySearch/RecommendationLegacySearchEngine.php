<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\eZ\Publish\LegacySearch;

use ezpSearchEngine;
use EzSystems\RecommendationBundle\Client\RecommendationClient;

/**
 * Recommendation legacy search plugin proxy.
 *
 * Uses the search plugin API to index Recommendation content, and Proxies all calls to the real legacy search engine.
 */
class RecommendationLegacySearchEngine implements ezpSearchEngine
{
    /** @var \ezpSearchEngine */
    protected $legacySearchEngine;

    /** @var \EzSystems\RecommendationBundle\Client\RecommendationClient */
    protected $recommendationClient;

    public function __construct(RecommendationClient $recommendationClient, ezpSearchEngine $legacySearchEngine)
    {
        $this->recommendationClient = $recommendationClient;
        $this->legacySearchEngine = $legacySearchEngine;
    }

    public function addObject($contentObject, $commit = true)
    {
        $this->recommendationClient->updateContent($contentObject->attribute('id'));

        return $this->legacySearchEngine->addObject($contentObject, $commit);
    }

    public function removeObject($contentObject, $commit = null)
    {
        $this->recommendationClient->deleteContent($contentObject->attribute('id'));

        return $this->legacySearchEngine->removeObject($contentObject, $commit);
    }

    public function removeObjectById($contentObjectId, $commit = null)
    {
        $this->recommendationClient->deleteContent($contentObjectId);

        return $this->legacySearchEngine->removeObjectById($contentObjectId, $commit);
    }

    public function addNodeAssignment($mainNodeID, $objectID, $nodeAssignmentIDList, $isMoved = false)
    {
        $this->recommendationClient->updateContent($objectID);
        if (method_exists($this->legacySearchEngine, 'addNodeAssignment')) {
            $this->legacySearchEngine->addNodeAssignment($mainNodeID, $objectID, $nodeAssignmentIDList, $isMoved);
        }
    }

    /**
     *  Proxy all other calls to the real legacy search engine.
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->legacySearchEngine, $name)) {
            return call_user_func_array(
                array($this->legacySearchEngine, $name),
                $arguments
            );
        }
    }
}
