<?php
/**
 * This file is part of the EzSystemRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Content;

use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Values\ContentType\ContentType as ApiContentType;
use eZ\Publish\API\Repository\Values\Content\Content as ApiContent;
use eZ\Publish\Core\Base\Exceptions\UnauthorizedException;
use EzSystems\RecommendationBundle\Rest\Field\Value;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Routing\RouterInterface;

class Content
{
    /** @var \eZ\Publish\API\Repository\ContentService */
    protected $contentService;

    /** @var \eZ\Publish\API\Repository\ContentTypeService */
    protected $contentTypeService;

    /** @var \eZ\Publish\API\Repository\LocationService */
    protected $locationService;

    /** @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface */
    protected $generator;

    /** @var \EzSystems\RecommendationBundle\Rest\Field\Value */
    protected $value;

    /** @var int $defaultAuthorId */
    private $defaultAuthorId;

    /**
     * @param ContentService $contentService
     * @param ContentTypeService $contentTypeService
     * @param LocationService $locationService
     * @param RouterInterface $routingGenerator
     * @param Value $value
     * @param int $defaultAuthorId
     */
    public function __construct(
        ContentService $contentService,
        ContentTypeService $contentTypeService,
        LocationService $locationService,
        RouterInterface $routingGenerator,
        Value $value,
        $defaultAuthorId
    ) {
        $this->contentService = $contentService;
        $this->contentTypeService = $contentTypeService;
        $this->locationService = $locationService;
        $this->generator = $routingGenerator;
        $this->value = $value;
        $this->defaultAuthorId = $defaultAuthorId;
    }

    /**
     * Prepare content array.
     *
     * @param array $data
     * @param ParameterBag $options
     * @param OutputInterface|null $output
     *
     * @return array
     */
    public function prepareContent($data, ParameterBag $options, OutputInterface $output = null)
    {
        if ($output === null) {
            $output = new NullOutput();
        }

        $content = array();

        foreach ($data as $contentTypeId => $items) {
            $progress = new ProgressBar($output, count($items));
            $progress->start();

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

                $progress->advance();
            }

            $progress->finish();
            $output->writeln('');
        }

        return $content;
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

    /**
     * Checks if fields are given, if not - returns all of them.
     *
     * @param \eZ\Publish\API\Repository\Values\ContentType\ContentType $contentType
     * @param string $fields
     *
     * @return array|null
     */
    private function prepareFields(ApiContentType $contentType, $fields = null)
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
}
