<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace EzSystems\RecommendationBundle\Client;

/**
 * Interface allows to fetch recommendations from YooChoose
 *
 * @package EzSystems\RecommendationBundle\Client
 */
interface RecommendationRequestClient
{
    /**
     * Returns $limit recommendations for a $contentId and a $userId based on a $scenarioId
     *
     * @param int $userId
     * @param int $scenarioId
     * @param int $contentId
     * @param int $limit
     * @return \EzSystems\RecommendationBundle\Values\RecommendationsCollection
     */
    public function getRecommendations($userId, $scenarioId, $contentId, $limit);
}
