<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Api;

use EzSystems\RecommendationBundle\Client\YooChooseClientInterface;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

/**
 * Class AbstractApi.
 */
abstract class AbstractApi
{
    /** @var \EzSystems\RecommendationBundle\Client\YooChooseClientInterface */
    protected $client;

    public function __construct(YooChooseClientInterface $client)
    {
        $this->client = $client;
    }

    /** @return string */
    abstract public function getRawEndPointUrl(): string;

    /**
     * @param array $endPointParameters
     *
     * @return \Psr\Http\Message\UriInterface
     */
    protected function buildEndPointUrl(array $endPointParameters): UriInterface
    {
        return new Uri(vsprintf($this->getRawEndPointUrl(), $endPointParameters));
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    protected function buildQueryStringFromArray(array $parameters): string
    {
        $queryString = '';

        foreach ($parameters as $parameterKey => $parameterValue) {
            if (is_array($parameterValue)) {
                $queryString .= $this->buildQueryStringFromArray($parameterValue);
            }

            if (is_string($parameterValue) || is_numeric($parameterValue)) {
                $queryString .= $parameterKey . '=' . $parameterValue;
            }

            if (next($parameters)) {
                $queryString .= '&';
            }
        }

        return $queryString;
    }

    /**
     * @param \EzSystems\RecommendationBundle\Rest\Api\ApiMetadata $metadata
     * @param array $requiredAttributes
     *
     * @return array
     */
    protected function getQueryStringParameters(ApiMetadata $metadata, array $requiredAttributes = []): array
    {
        if ($requiredAttributes) {
            return array_intersect_key($metadata->getMetadataAttributes(), array_flip($requiredAttributes));
        }

        return $metadata->getMetadataAttributes();
    }
}
