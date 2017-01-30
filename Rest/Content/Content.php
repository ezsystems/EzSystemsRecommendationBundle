<?php
/**
 * This file is part of the EzSystemRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Content;

use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use eZ\Publish\Core\Base\Exceptions\UnauthorizedException;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\ContentType\ContentType as ApiContentType;
use eZ\Publish\API\Repository\Values\Content\Content as ApiContent;
use EzSystems\RecommendationBundle\Rest\Field\Value as FieldValue;
use EzSystems\RecommendationBundle\Rest\Values\ContentData as ContentDataValue;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface as UrlGenerator;
use Symfony\Component\Routing\RequestContext;

class Content
{
    /** @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface */
    protected $generator;

    /** @var \eZ\Publish\Core\Repository\ContentService */
    protected $contentService;

    /** @var \eZ\Publish\Core\Repository\LocationService */
    protected $locationService;

    /** @var \eZ\Publish\Core\Repository\ContentTypeService */
    protected $contentTypeService;

    /** @var \eZ\Publish\Core\Repository\SearchService */
    protected $searchService;

    /** @var \EzSystems\RecommendationBundle\Rest\Field\Value */
    protected $value;

    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    protected $configResolver;

    /** @var \Symfony\Component\DependencyInjection\ContainerInterface */
    protected $container;

    /** @var \Symfony\Component\Routing\RequestContext */
    protected $requestContext;

    /** @var \eZ\Publish\Core\MVC\Symfony\SiteAccess */
    protected $siteAccess;

    /** @var int */
    protected $customerId;

    /** @var string */
    protected $licenseKey;

    /** @var int $defaultAuthorId */
    protected $defaultAuthorId;

    /** @var array $siteAccessConfig */
    protected $siteAccessConfig;

    /**
     * @param \Symfony\Component\Routing\Generator\UrlGeneratorInterface $generator
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \eZ\Publish\API\Repository\ContentTypeService $contentTypeService
     * @param \eZ\Publish\API\Repository\SearchService $searchService
     * @param \EzSystems\RecommendationBundle\Rest\Field\Value $value
     * @param int $defaultAuthorId
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param \Symfony\Component\Routing\RequestContext $requestContext
     */
    public function __construct(
        UrlGenerator $generator,
        ContentService $contentService,
        LocationService $locationService,
        ContentTypeService $contentTypeService,
        SearchService $searchService,
        FieldValue $value,
        $defaultAuthorId,
        ConfigResolverInterface $configResolver,
        ContainerInterface $container,
        RequestContext $requestContext
    ) {
        $this->generator = $generator;
        $this->contentService = $contentService;
        $this->locationService = $locationService;
        $this->contentTypeService = $contentTypeService;
        $this->searchService = $searchService;
        $this->value = $value;
        $this->defaultAuthorId = $defaultAuthorId;
        $this->configResolver = $configResolver;
        $this->container = $container;
        $this->requestContext = $requestContext;
    }

    /**
     * @param \eZ\Publish\Core\MVC\Symfony\SiteAccess $siteAccess
     */
    public function setSiteAccess(SiteAccess $siteAccess)
    {
        $this->siteAccess = $siteAccess;
    }

    /**
     * @param null|int $mandatorId
     *
     * @return string
     *
     * @throws NotFoundException
     */
    public function getSiteAccess($mandatorId = 0)
    {
        if (0 == $mandatorId) {
            return $this->siteAccess->name;
        }
        foreach ($this->siteAccessConfig as $name => $config) {
            if (isset($config['yoochoose']['customer_id']) && $config['yoochoose']['customer_id'] == $mandatorId) {
                return $name;
            }
        }

        throw new NotFoundException('No config for mandator found', $mandatorId);
    }

    public function setSiteAccessConfig($config)
    {
        $this->siteAccessConfig = $config;
    }

    /**
     * @param int $value
     */
    public function setCustomerId($value)
    {
        $this->customerId = $value;
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @param string $value
     */
    public function setLicenseKey($value)
    {
        $this->licenseKey = $value;
    }

    /**
     * @return string
     */
    public function getLicenseKey()
    {
        return $this->licenseKey;
    }

    /**
     * Prepares content for ContentDataValue class.
     *
     * @param array $options
     *
     * @return \EzSystems\RecommendationBundle\Rest\Values\ContentData
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException if the content, version with the given id and languages or content type does not exist
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the user has no access to read content and in case of un-published content: read versions
     */
    public function get(array $options)
    {
        $contentIds = $options['contentIdList'];
        $criteria = array(new Criterion\ContentId($contentIds));

        if (!$options['hidden']) {
            $criteria[] = new Criterion\Visibility(Criterion\Visibility::VISIBLE);
        }

        if ($lang = $options['lang']) {
            $criteria[] = new Criterion\LanguageCode($lang);
        }

        $query = new Query();
        $query->query = new Criterion\LogicalAnd($criteria);

        $contentItems = $this->searchService->findContent($query)->searchHits;
        $content = $this->prepareContentTypes(array($contentItems), $options);

        return new ContentDataValue($content, array(
            'responseType' => 'http',
            'documentRoot' => $options['documentRoot'],
            'host' => $options['schemeAndHttpHost'],
            'customerId' => $this->customerId,
        ));
    }

    /**
     * Prepare content array.
     *
     * @param array $data
     * @param array $options
     *
     * @return array
     */
    protected function prepareContentTypes($data, $options)
    {
        $contentItems = array();

        foreach ($data as $contentTypeId => $items) {
            $contentItems[$contentTypeId] = $this->prepareContent($items, $options);
        }

        return $contentItems;
    }

    /**
     * Prepare content array.
     *
     * @param array $data
     * @param array $options
     *
     * @return array
     */
    protected function prepareContent($data, $options)
    {
        $content = array();
        foreach ($data as $contentValue) {
            $contentValue = $contentValue->valueObject;
            $contentType = $this->contentTypeService->loadContentType($contentValue->contentInfo->contentTypeId);
            $location = $this->locationService->loadLocation($contentValue->contentInfo->mainLocationId);
            $language = (null === $options['lang']) ? $location->contentInfo->mainLanguageCode : $options['lang'];
            $this->value->setFieldDefinitionsList($contentType);

            $content[$contentValue->id] = array(
                'contentId' => $contentValue->id,
                'contentTypeId' => $contentType->id,
                'identifier' => $contentType->identifier,
                'language' => $language,
                'publishedDate' => $contentValue->contentInfo->publishedDate->format('c'),
                'author' => $this->getAuthor($contentValue, $contentType),
                'uri' => $this->generator->generate($location, array(), false),
                'mainLocation' => array(
                    'href' => '/api/ezp/v2/content/locations' . $location->pathString,
                ),
                'locations' => array(
                    'href' => '/api/ezp/v2/content/objects/' . $contentValue->id . '/locations',
                ),
                'categoryPath' => $location->pathString,
                'fields' => array(),
            );

            $fields = $this->prepareFields($contentType, $options['requestedFields']);
            if (!empty($fields)) {
                foreach ($fields as $field) {
                    $field = $this->value->getConfiguredFieldIdentifier($field, $contentType);

                    $content[$contentValue->id]['fields'][$field] = $this->value->getFieldValue($contentValue, $field, $language, $options);
                }
            }
        }

        return $content;
    }

    /**
     * Checks if fields are given, if not - returns all of them.
     *
     * @param \eZ\Publish\API\Repository\Values\ContentType\ContentType $contentType
     * @param string $fields
     *
     * @return array|null
     */
    protected function prepareFields(ApiContentType $contentType, $fields = null)
    {
        if (null !== $fields) {
            return explode(',', $fields);
        }

        $fields = array();
        $contentFields = $contentType->getFieldDefinitions();

        foreach ($contentFields as $field) {
            $fields[] = $field->identifier;
        }

        return $fields;
    }

    /**
     * Returns author of the content.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Content $contentValue
     * @param \eZ\Publish\API\Repository\Values\ContentType\ContentType $contentType
     *
     * @return string
     */
    private function getAuthor(ApiContent $contentValue, ApiContentType $contentType)
    {
        $author = $contentValue->getFieldValue(
            $this->value->getConfiguredFieldIdentifier('author', $contentType)
        );

        if (null === $author) {
            try {
                $ownerId = empty($contentValue->contentInfo->ownerId) ? $this->defaultAuthorId : $contentValue->contentInfo->ownerId;
                $userContentInfo = $this->contentService->loadContentInfo($ownerId);
                $author = $userContentInfo->name;
            } catch (UnauthorizedException $e) {
                $author = '';
            }
        }

        return (string) $author;
    }
}
