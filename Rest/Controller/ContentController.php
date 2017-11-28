<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Controller;

use eZ\Publish\Core\Base\Exceptions\UnauthorizedException;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use eZ\Publish\Core\REST\Server\Controller as BaseController;
use eZ\Publish\Core\REST\Server\Exceptions\BadRequestException;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\SearchService;
use EzSystems\RecommendationBundle\Rest\Field\Value as FieldValue;
use EzSystems\RecommendationBundle\Rest\Values\ContentData as ContentDataValue;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface as UrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use InvalidArgumentException;

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
     * Prepares content for ContentDataValue class.
     *
     * @param string $contentIdList
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \EzSystems\RecommendationBundle\Rest\Values\ContentData
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException if the content, version with the given id and languages or content type does not exist
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the user has no access to read content and in case of un-published content: read versions
     * @throws \eZ\Publish\Core\REST\Server\Exceptions\BadRequestException If incorrect $contentTypeIdList value is given
     */
    public function getContent($contentIdList, Request $request)
    {
        try {
            $contentIds = $this->getIdListFromString($contentIdList);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestException('Bad Request', 400);
        }

        $options = $this->parseParameters($request, ['lang', 'hidden']);

        $lang = $options->get('lang');

        $criteria = array(new Criterion\ContentId($contentIds));

        if (!$options->get('hidden')) {
            $criteria[] = new Criterion\Visibility(Criterion\Visibility::VISIBLE);
        }

        if ($lang) {
            $criteria[] = new Criterion\LanguageCode($lang);
        }

        $query = new Query();
        $query->query = new Criterion\LogicalAnd($criteria);

        $contentItems = $this->searchService->findContent(
            $query,
            (!empty($lang) ? array('languages' => array($lang)) : array())
        )->searchHits;

        $content = $this->prepareContent(array($contentItems), $request);

        return new ContentDataValue($content);
    }

    /**
     * Preparing array of integers based on comma separated integers in string or single integer in string.
     *
     * @param string $string list of integers separated by comma character
     *
     * @return array
     *
     * @throws InvalidArgumentException If incorrect $list value is given
     */
    protected function getIdListFromString($string)
    {
        if (filter_var($string, FILTER_VALIDATE_INT) !== false) {
            return array($string);
        }

        if (strpos($string, ',') === false) {
            throw new InvalidArgumentException('Integers in string should have a separator');
        }

        $array = explode(',', $string);

        foreach ($array as $item) {
            if (filter_var($item, FILTER_VALIDATE_INT) === false) {
                throw new InvalidArgumentException('String should be a list of Integers');
            }
        }

        return $array;
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
        $options = $this->parseParameters($request, ['lang', 'fields', 'image']);

        $content = array();

        foreach ($data as $contentTypeId => $items) {
            foreach ($items as $contentValue) {
                $contentValue = $contentValue->valueObject;
                $contentType = $this->contentTypeService->loadContentType($contentValue->contentInfo->contentTypeId);
                $location = $this->locationService->loadLocation($contentValue->contentInfo->mainLocationId);
                $language = $options->get('lang', $location->contentInfo->mainLanguageCode);
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

                $fields = $this->prepareFields($contentType, $options->get('fields'));
                if (!empty($fields)) {
                    foreach ($fields as $field) {
                        $field = $this->value->getConfiguredFieldIdentifier($field, $contentType);
                        $content[$contentTypeId][$contentValue->id]['fields'][$field] =
                            $this->value->getFieldValue($contentValue, $field, $language, $options->all());
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
        if ($fields !== null) {
            if (strpos($fields, ',') !== false) {
                return explode(',', $fields);
            }

            return array($fields);
        }

        $fields = array();
        $contentFields = $contentType->getFieldDefinitions();

        foreach ($contentFields as $field) {
            $fields[] = $field->identifier;
        }

        return $fields;
    }

    /**
     * Returns parameters from Request query specified in $allowedParameters.
     *
     * @param Request $request
     * @param array $allowedParameters
     * @return ParameterBag
     */
    protected function parseParameters(Request $request, array $allowedParameters)
    {
        $parameters = new ParameterBag();

        foreach ($allowedParameters as $parameter) {
            if ($value = $request->query->get($parameter)) {
                $parameters->set($parameter, $value);
            }
        }

        return $parameters;
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
