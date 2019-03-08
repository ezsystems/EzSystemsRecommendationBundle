<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Api;

/**
 * Class AllowedApi.
 */
class AllowedApi
{
    /** @return array */
    public function getAllowedApi(): array
    {
        return [
            Recommendation::API_NAME => Recommendation::class,
            EventTracking::API_NAME => EventTracking::class,
        ];
    }
}
