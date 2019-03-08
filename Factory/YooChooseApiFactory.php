<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Factory;

use EzSystems\RecommendationBundle\Client\YooChooseClientInterface;
use EzSystems\RecommendationBundle\Exception\BadApiCallException;
use EzSystems\RecommendationBundle\Exception\InvalidArgumentException;
use EzSystems\RecommendationBundle\Rest\Api\AbstractApi;
use EzSystems\RecommendationBundle\Rest\Api\AllowedApi;

/**
 * Class YooChooseApiFactory.
 */
class YooChooseApiFactory extends AbstractYooChooseApiFactory
{
    /** @var array */
    private $allowedApi;

    public function __construct(AllowedApi $allowedApi)
    {
        $this->allowedApi = $allowedApi->getAllowedApi();
    }

    /**
     * @param string $name
     * @param \EzSystems\RecommendationBundle\Client\YooChooseClientInterface $client
     *
     * @return \EzSystems\RecommendationBundle\Rest\Api\Recommendation
     *
     * @throws \Exception
     */
    public function buildApi(string $name, YooChooseClientInterface $client): AbstractApi
    {
        if (!array_key_exists($name, $this->allowedApi)) {
            throw new InvalidArgumentException(sprintf('Given api key: %s is not found in allowedApi array', $name));
        }

        $api = $this->allowedApi[$name];

        if (!class_exists($api)) {
            throw new BadApiCallException($api);
        }

        return new $api($client);
    }
}
