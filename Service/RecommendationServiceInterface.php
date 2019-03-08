<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Service;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Interface RecommendationServiceInterface.
 */
interface RecommendationServiceInterface
{
    public function getRecommendations(ParameterBag $parameterBag): ?ResponseInterface;

    public function sendDeliveryFeedback(string $outputContentType): void;

    public function getRecommendationItems(array $recommendationItems): array;
}
