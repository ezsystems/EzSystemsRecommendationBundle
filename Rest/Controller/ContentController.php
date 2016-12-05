<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Controller;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use eZ\Publish\Core\REST\Server\Controller as BaseController;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\SearchService;
use EzSystems\RecommendationBundle\Rest\Exception\UnsupportedResponseTypeException;
use EzSystems\RecommendationBundle\Rest\Field\Value as FieldValue;
use EzSystems\RecommendationBundle\Rest\Values\ContentData as ContentDataValue;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface as UrlGenerator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Recommendation REST Content controller.
 */
class ContentController extends BaseController
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

    /** @var \eZ\Publish\Core\MVC\Symfony\SiteAccess */
    protected $siteAccess;

    /** @var int */
    protected $customerId;

    /** @var string */
    protected $licenseKey;

    /** @var int $defaultAuthorId */
    protected $defaultAuthorId;

    /**
     * @param \Symfony\Component\Routing\Generator\UrlGeneratorInterface $generator
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \eZ\Publish\API\Repository\ContentTypeService $contentTypeService
     * @param \eZ\Publish\API\Repository\SearchService $searchService
     * @param \EzSystems\RecommendationBundle\Rest\Field\Value $value
     * @param int $defaultAuthorId
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     */
    public function __construct(
        UrlGenerator $generator,
        ContentService $contentService,
        LocationService $locationService,
        ContentTypeService $contentTypeService,
        SearchService $searchService,
        FieldValue $value,
        $defaultAuthorId,
        ConfigResolverInterface $configResolver
    ) {
        $this->generator = $generator;
        $this->contentService = $contentService;
        $this->locationService = $locationService;
        $this->contentTypeService = $contentTypeService;
        $this->searchService = $searchService;
        $this->value = $value;
        $this->defaultAuthorId = $defaultAuthorId;
        $this->configResolver = $configResolver;
    }

    /**
     * @param \eZ\Publish\Core\MVC\Symfony\SiteAccess $siteAccess
     */
    public function setSiteAccess(SiteAccess $siteAccess)
    {
        $this->siteAccess = $siteAccess;
    }

    /**
     * @param int $value
     */
    public function setCustomerId($value)
    {
        $this->customerId = $value;
    }

    /**
     * @param string $value
     */
    public function setLicenseKey($value)
    {
        $this->licenseKey = $value;
    }

    /**
     * Prepares content for ContentDataValue class.
     *
     * @param string $contentIdList
     * @param string $responseType
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \EzSystems\RecommendationBundle\Rest\Values\ContentData
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException if the content, version with the given id and languages or content type does not exist
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the user has no access to read content and in case of un-published content: read versions
     * @throws \EzSystems\RecommendationBundle\Rest\Exception\UnsupportedResponseTypeException
     */
    public function getContent($contentIdList, $responseType, Request $request)
    {
        if ($responseType != 'http') {
            throw new UnsupportedResponseTypeException('Only http response is available for single content');
        }
        $contentIds = explode(',', $contentIdList);
        $lang = $request->get('lang');

        $criteria = array(new Criterion\ContentId($contentIds));

        if (!$request->get('hidden')) {
            $criteria[] = new Criterion\Visibility(Criterion\Visibility::VISIBLE);
        }

        if ($lang) {
            $criteria[] = new Criterion\LanguageCode($lang);
        }

        $query = new Query();
        $query->query = new Criterion\LogicalAnd($criteria);

        $contentItems = $this->searchService->findContent($query)->searchHits;

        $content = $this->prepareContent($contentItems, $request);

        return new ContentDataValue($content, [
            'responseType' => 'http',
            'documentRoot' => $request->server->get('DOCUMENT_ROOT'),
            'host' => $request->getSchemeAndHttpHost(),
            'customerId' => $this->customerId,
        ]);
    }

    /**
     * Prepare content array.
     *
     * @param array $data
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array
     */
    protected function prepareContent($data, Request $request)
    {
        $requestLanguage = $request->get('lang');
        $requestedFields = $request->get('fields');

        $content = array();
        foreach ($data as $contentTypeId => $items) {
            foreach ($items as $contentValue) {
                $contentValue = $contentValue->valueObject;
                $contentType = $this->contentTypeService->loadContentType($contentValue->contentInfo->contentTypeId);
                $location = $this->locationService->loadLocation($contentValue->contentInfo->mainLocationId);
                $language = (null === $requestLanguage) ? $location->contentInfo->mainLanguageCode : $requestLanguage;
                $this->value->setFieldDefinitionsList($contentType);

                $content[$contentTypeId][$contentValue->id] = array(
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

                $fields = $this->prepareFields($contentType, $requestedFields);
                if (!empty($fields)) {
                    foreach ($fields as $field) {
                        $field = $this->value->getConfiguredFieldIdentifier($field, $contentType);
                        $content[$contentTypeId][$contentValue->id]['fields'][] = $this->value->getFieldValue($contentValue, $field, $language);
                    }
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
    protected function prepareFields(ContentType $contentType, $fields = null)
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
    private function getAuthor(Content $contentValue, ContentType $contentType)
    {
        $author = $contentValue->getFieldValue(
            $this->value->getConfiguredFieldIdentifier('author', $contentType)
        );

        if (null === $author) {
            $ownerId = empty($contentValue->contentInfo->ownerId) ? $this->defaultAuthorId : $contentValue->contentInfo->ownerId;
            $userContentInfo = $this->contentService->loadContentInfo($ownerId);
            $author = $userContentInfo->name;
        }

        return (string) $author;
    }
}
