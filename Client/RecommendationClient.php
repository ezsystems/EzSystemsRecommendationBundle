<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
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
}
