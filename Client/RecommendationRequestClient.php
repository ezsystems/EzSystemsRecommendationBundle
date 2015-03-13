<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributd with this source code.
 */
namespace EzSystems\RecommendationBundle\Client;

interface RecommendationRequestClient
{
    /**
     * @param int $userId
     * @param int $scenarioId
     * @param int $locationId
     * @param int $limit
     * @return string|null JSON response
     */
    public function getRecommendations( $userId, $scenarioId, $locationId, $limit );
}
