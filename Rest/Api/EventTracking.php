<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Api;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class TrackingApi.
 */
class EventTracking extends AbstractApi
{
    const API_NAME = 'eventTracking';

    /** @return string */
    public function getRawEndPointUrl(): string
    {
        return 'https://event.yoochoose.net/api/%d/rendered/%s/%d/';
    }

    /**
     * @param string $outputContentTypeId
     */
    public function sendNotificationPing(string $outputContentTypeId): void
    {
        $endPointUrl = $this->buildEndPointUrl([
                $this->client->getCustomerId(),
                $this->client->getUserIdentifier(),
                $outputContentTypeId,
        ]);

        $this->client->sendRequest(Request::METHOD_GET, $endPointUrl);
    }
}
