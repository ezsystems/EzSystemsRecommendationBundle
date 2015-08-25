<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributd with this source code.
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

    public function needCommit()
    {
        return $this->legacySearchEngine->needCommit();
    }

    public function needRemoveWithUpdate()
    {
        return $this->legacySearchEngine->needRemoveWithUpdate();
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

    public function search($searchText, $params = array(), $searchTypes = array())
    {
        return $this->legacySearchEngine->search($searchText, $params, $searchTypes);
    }

    public function supportedSearchTypes()
    {
        return $this->legacySearchEngine->supportedSearchTypes();
    }

    public function commit()
    {
        $this->legacySearchEngine->commit();
    }

    public function addNodeAssignment($mainNodeID, $objectID, $nodeAssignmentIDList, $isMoved = false)
    {
        $this->recommendationClient->updateContent($objectID);
        if (method_exists($this->legacySearchEngine, 'addNodeAssignment')) {
            $this->legacySearchEngine->addNodeAssignment($mainNodeID, $objectID, $nodeAssignmentIDList, $isMoved);
        }
    }
}
