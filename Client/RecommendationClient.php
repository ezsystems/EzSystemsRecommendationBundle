<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributd with this source code.
 */
namespace EzSystems\RecommendationBundle\Client;

interface RecommendationClient
{
    /**
     * @param mixed $contentId
     * @throws \EzSystems\RecommendationBundle\Client\RecommendationClientException If an error occurs with the client
     * @return void
     */
    public function updateContent($contentId);

    /**
     * @param mixed $contentId
     * @throws \EzSystems\RecommendationBundle\Client\RecommendationClientException If an error occurs with the client
     * @return void
     */
    public function deleteContent($contentId);

    /**
     * @param int $userId YooChoose user ID
     * @param int $scenarioId YooChoose scenario ID
     * @param int $locationId location ID
     * @param int $limit maximum limit of fetched recommendations
     * @return json|null
     */
    public function getRecommendations( $userId, $scenarioId, $locationId, $limit );
}
