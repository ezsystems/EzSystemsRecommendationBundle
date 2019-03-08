<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Client;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * @method \EzSystems\RecommendationBundle\Rest\Api\Recommendation recommendation()
 * @method \EzSystems\RecommendationBundle\Rest\Api\EventTracking eventTracking()
 *
 * Interface YooChooseClientInterface
 */
interface YooChooseClientInterface
{
    public function setCustomerId(int $customerId): self;

    public function getCustomerId(): ?int;

    public function setLicenseKey(string $licenseKey): self;

    public function getLicenseKey(): ?string;

    public function setUserIdentifier(string $userIdentifier): self;

    public function getUserIdentifier(): ?string;

    public function sendRequest(string $method, UriInterface $uri, array $option = []): ?ResponseInterface;

    public function getHttpClient(): ClientInterface;
}
