<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Factory;

use EzSystems\RecommendationBundle\Client\YooChooseClientInterface;
use EzSystems\RecommendationBundle\Rest\Api\AbstractApi;

/**
 * Class AbstractYooChooseApiFactory.
 */
abstract class AbstractYooChooseApiFactory
{
    abstract public function buildApi(string $name, YooChooseClientInterface $client): AbstractApi;
}
