<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Service;

use eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use EzSystems\RecommendationBundle\Helper\SessionHelper;
use EzSystems\RecommendationBundle\Helper\UserHelper;
use EzSystems\RecommendationBundle\Client\YooChooseClientInterface;
use EzSystems\RecommendationBundle\Helper\ContentHelper;
use EzSystems\RecommendationBundle\Rest\Model\RecommendationItem;
use EzSystems\RecommendationBundle\Rest\Values\RecommendationMetadata;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class RecommendationService.
 */
class RecommendationService implements RecommendationServiceInterface
{
    private const YC_SESSION_KEY = 'yc-session-id';
    private const LOCALE_REQUEST_KEY = '_locale';
    private const DEFAULT_LOCALE = 'eng-GB';

    /** @var \EzSystems\RecommendationBundle\Client\YooChooseClientInterface */
    private $client;

    /** @var \EzSystems\RecommendationBundle\Helper\ContentHelper */
    private $contentHelper;

    /** @var \EzSystems\RecommendationBundle\Helper\UserHelper */
    private $userHelper;

    /** @var \EzSystems\RecommendationBundle\Helper\SessionHelper */
    private $sessionHelper;

    /** @var \eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface */
    private $localeConverter;

    /** @var int */
    private $customerId;

    /** @var string */
    private $licenseKey;

    public function __construct(
        YooChooseClientInterface $client,
        ContentHelper $contentHelper,
        UserHelper $userHelper,
        SessionHelper $sessionHelper,
        LocaleConverterInterface $localeConverter,
        int $customerId,
        string $licenseKey
    ) {
        $this->contentHelper = $contentHelper;
        $this->client = $client;
        $this->customerId = $customerId;
        $this->licenseKey = $licenseKey;
        $this->userHelper = $userHelper;
        $this->sessionHelper = $sessionHelper;
        $this->localeConverter = $localeConverter;

        $this->setClientConfig();
    }

    /**
     * @param \Symfony\Component\HttpFoundation\ParameterBag $parameterBag
     *
     * @return \Psr\Http\Message\ResponseInterface|null
     */
    public function getRecommendations(ParameterBag $parameterBag): ?ResponseInterface
    {
        $recommendationMetadata = new RecommendationMetadata(
            $this->getRecommendationMetadataParameters($parameterBag)
        );

        return $this->client
            ->recommendation()
            ->getRecommendations($recommendationMetadata);
    }

    /**
     * @param string $outputContentType
     */
    public function sendDeliveryFeedback(string $outputContentType): void
    {
        $this->client
            ->eventTracking()
            ->sendNotificationPing($outputContentType);
    }

    /**
     * @param array $recommendationItems
     *
     * @return array
     */
    public function getRecommendationItems(array $recommendationItems): array
    {
        $recommendationCollection = [];

        $recommendationItemPrototype = new RecommendationItem();

        foreach ($recommendationItems as $recommendationItem) {
            $newRecommendationItem = clone $recommendationItemPrototype;

            if ($recommendationItem['links']) {
                $newRecommendationItem->clickRecommended = $recommendationItem['links']['clickRecommended'];
                $newRecommendationItem->rendered = $recommendationItem['links']['rendered'];
            }

            if ($recommendationItem['attributes']) {
                foreach ($recommendationItem['attributes'] as $attribute) {
                    if ($attribute['values']) {
                        $decodedHtmlString = html_entity_decode(strip_tags($attribute['values'][0]));
                        $newRecommendationItem->{$attribute['key']} = str_replace(['<![CDATA[', ']]>'], '', $decodedHtmlString);
                    }
                }
            }

            $newRecommendationItem->itemId = $recommendationItem['itemId'];
            $newRecommendationItem->itemType = $recommendationItem['itemType'];
            $newRecommendationItem->relevance = $recommendationItem['relevance'];

            $recommendationCollection[] = $newRecommendationItem;
        }

        unset($recommendationItemPrototype);

        return $recommendationCollection;
    }

    /** Sets basic client options */
    private function setClientConfig(): void
    {
        $this->client
            ->setUserIdentifier($this->getUserIdentifier())
            ->setCustomerId($this->customerId)
            ->setLicenseKey($this->licenseKey);
    }

    /** @return mixed|object|string */
    private function getUserIdentifier()
    {
        $userIdentifier = $this->userHelper->getCurrentUser();

        if (!$userIdentifier) {
            $userIdentifier = $this->sessionHelper->getAnonymousSessionId(self::YC_SESSION_KEY);
        }

        return $userIdentifier;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\ParameterBag $parameterBag
     *
     * @return array
     */
    private function getRecommendationMetadataParameters(ParameterBag $parameterBag): array
    {
        $contextItems = $parameterBag->get(RecommendationMetadata::CONTEXT_ITEMS, '');

        return [
            RecommendationMetadata::SCENARIO => $parameterBag->get(RecommendationMetadata::SCENARIO, ''),
            RecommendationMetadata::LIMIT => $parameterBag->get(RecommendationMetadata::LIMIT, 3),
            RecommendationMetadata::CONTEXT_ITEMS => $contextItems,
            RecommendationMetadata::CONTENT_TYPE => $this->contentHelper->getContentTypeId($this->contentHelper->getContentIdentifier($contextItems)),
            RecommendationMetadata::OUTPUT_TYPE_ID => $this->contentHelper->getContentTypeId($parameterBag->get(RecommendationMetadata::OUTPUT_TYPE_ID, '')),
            RecommendationMetadata::CATEGORY_PATH => $this->contentHelper->getLocationPathString($contextItems),
            RecommendationMetadata::LANGUAGE => $parameterBag->get($this->localeConverter->convertToEz(self::LOCALE_REQUEST_KEY), self::DEFAULT_LOCALE),
            RecommendationMetadata::ATTRIBUTES => $parameterBag->get(RecommendationMetadata::ATTRIBUTES, []),
            RecommendationMetadata::FILTERS => $parameterBag->get(RecommendationMetadata::FILTERS, []),
        ];
    }
}
