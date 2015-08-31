<?php

/**
 * This file is part of the EzSystemsRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Controller;

use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\Core\REST\Server\Controller as BaseController;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\SearchService;
use EzSystems\RecommendationBundle\Rest\Field\Value as FieldValue;
use EzSystems\RecommendationBundle\Rest\Values\ContentData as ContentDataValue;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface as UrlGenerator;

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

    /**
     * @param \Symfony\Component\Routing\Generator\UrlGeneratorInterface $generator
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \eZ\Publish\API\Repository\ContentTypeService $contentTypeService
     * @param \eZ\Publish\API\Repository\SearchService $searchService
     * @param \EzSystems\RecommendationBundle\Rest\Field\Value $value
     */
    public function __construct(
        UrlGenerator $generator,
        ContentService $contentService,
        LocationService $locationService,
        ContentTypeService $contentTypeService,
        SearchService $searchService,
        FieldValue $value
    ) {
        $this->generator = $generator;
        $this->contentService = $contentService;
        $this->locationService = $locationService;
        $this->contentTypeService = $contentTypeService;
        $this->searchService = $searchService;
        $this->value = $value;
    }

    /**
     * Prepares content for ContentDataValue class.
     *
     * @param string $contentIdList
     *
     * @return \EzSystems\RecommendationBundle\Rest\Values\ContentData
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundExceptionif the content, version with the given id and languages or content type does not exist
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the user has no access to read content and in case of un-published content: read versions
     */
    public function getContent($contentIdList)
    {
        $contentIds = explode(',', $contentIdList);
        $content = $this->prepareContent($contentIds);

        return new ContentDataValue($content);
    }

    /**
     * Prepare content array.
     *
     * @param array $contentIds
     *
     * @return array
     */
    protected function prepareContent($contentIds)
    {
        $requestLanguage = $this->request->get('lang');
        $requestedFields = $this->request->get('fields');

        $content = array();

        foreach ($contentIds as $contentId) {
            $contentValue = $this->contentService->loadContent(
                $contentId,
                (null === $requestLanguage) ? null : array($requestLanguage)
            );
            $contentType = $this->contentTypeService->loadContentType($contentValue->contentInfo->contentTypeId);
            $location = $this->locationService->loadLocation($contentValue->contentInfo->mainLocationId);
            $language = (null === $requestLanguage) ? $contentType->mainLanguageCode : $requestLanguage;
            $this->value->setFieldDefinitionsList($contentType);

            $content[$contentId] = array(
                'contentId' => $contentId,
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
                    'href' => '/api/ezp/v2/content/objects/' . $contentId . '/locations',
                ),
                'categoryPath' => $location->pathString,
                'fields' => array(),
            );

            $fields = $this->prepareFields($contentType, $requestedFields);
            if (!empty($fields)) {
                foreach ($fields as $field) {
                    $field = $this->value->getConfiguredFieldIdentifier($field, $contentType);
                    $content[$contentId]['fields'][] = $this->value->getFieldValue($contentValue, $field, $language);
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
            $userContentInfo = $this->contentService->loadContentInfo($contentValue->contentInfo->ownerId);
            $author = $userContentInfo->name;
        }

        return (string) $author;
    }
}
