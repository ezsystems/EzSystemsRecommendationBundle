<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Api;

use EzSystems\RecommendationBundle\Rest\Values\RecommendationMetadata;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Recommendation.
 */
class Recommendation extends AbstractApi
{
    const API_NAME = 'recommendation';

    /**
     * Returns YooChoose recommender end-point address.
     *
     * @return string
     */
    public function getRawEndPointUrl(): string
    {
        return 'https://reco.yoochoose.net/api/v2/%d/%s/%s';
    }

    /**
     * @param \EzSystems\RecommendationBundle\Rest\Values\RecommendationMetadata $recommendationMetadata
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getRecommendations(RecommendationMetadata $recommendationMetadata): ?ResponseInterface
    {
        $endPointUrl = $this->buildEndPointUrl([
                $this->client->getCustomerId(),
                $this->client->getUserIdentifier(),
                $recommendationMetadata->scenario,
        ]);

        $queryStringArray = $this->getQueryStringParameters($recommendationMetadata);

        return $this->client->sendRequest(Request::METHOD_GET, $endPointUrl, [
            'query' => $this->buildQueryStringFromArray($queryStringArray),
        ]);
    }
}
