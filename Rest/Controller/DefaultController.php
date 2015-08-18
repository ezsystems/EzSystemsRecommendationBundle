<?php

/**
 * This file is part of the EzSystemsRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Controller;

use eZ\Bundle\EzPublishCoreBundle\Imagine\AliasGenerator as ImageVariationService;
use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\Repository\Values\ContentType\ContentType;
use eZ\Publish\Core\REST\Server\Controller as BaseController;
use EzSystems\RecommendationBundle\Rest\Values\ContentData as ContentDataValue;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface as UrlGenerator;

/**
 * Recommendation REST custom controller.
 */
class DefaultController extends BaseController
{

    private $configResolver;

    private $imageVariationService;

    private $generator;

    public function __construct(ConfigResolverInterface $configResolver, ImageVariationService $imageVariationService, UrlGenerator $generator)
    {
        $this->configResolver = $configResolver;
        $this->imageVariationService = $imageVariationService;
        $this->generator = $generator;
    }

    /**
     * Prepares content for ContentDataValue class.
     *
     * @param $contentId
     *
     * @return ContentDataValue
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException     if the content, version with the given id and languages or content type does not exist
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the user has no access to read content and in case of un-published content: read versions
     */
    public function getContent($contentId)
    {
        $contentIds = explode(',', $contentId);
        $content = $this->prepareContent($contentIds);

        return new ContentDataValue(json_encode($content));
    }

    /**
     * Checks if fields are given, if not - returns all of them.
     *
     * @internal
     *
     * @param null        $fields
     * @param ContentType $contentType
     *
     * @return array|null
     */
    private function prepareFields($fields = null, ContentType $contentType)
    {
        if (!is_null($fields)) {
            return explode(',', $fields);
        }

        $fields = [];
        $contentFields = $contentType->getFieldDefinitions();

        foreach ($contentFields as $field) {
            $fields[] = $field->identifier;
        }

        return $fields;
    }

    /**
     * Prepare nested content array.
     *
     * @param $contentIds
     * @param int $nesting Maximum nesting level for container content.
     * @return array
     */
    private function prepareContent($contentIds)
    {

        $contentService = $this->repository->getContentService();
        $locationService = $this->repository->getLocationService();
        $contentTypeService = $this->repository->getContentTypeService();

        $requestLanguage = $this->request->get('lang');
        $requestFields = $this->request->get('fields');

        $content = [];

        foreach ($contentIds as $contentId) {
            //TODO: language issue
            try {
                $contentValue = $contentService->loadContent($contentId, [$requestLanguage]);
            } catch (NotFoundException $e) {
//                $content[$contentId] = ['error' => $e->getMessage()];
                continue;
            }

            $contentType = $contentTypeService->loadContentType($contentValue->contentInfo->contentTypeId);
            $location = $locationService->loadLocation($contentValue->contentInfo->mainLocationId);
            $user = $this->repository->getUserService()->loadUser($contentValue->contentInfo->ownerId);

            $language = (is_null($requestLanguage)) ? $contentType->mainLanguageCode : $requestLanguage;

            $content[$contentId] = [
                '_media-type' => $this->request->attributes->get('media_type'),
                'contentId' => (int)$contentId,
                'contentTypeId' => $contentType->id,
                'identifier' => $contentType->identifier,
                'language' => $language,
                'publishedDate' => $contentValue->contentInfo->publishedDate->format('c'),
                'author' => $user->getFieldValue('first_name').' '.$user->getFieldValue('lasst_name'),
                'uri' => $this->generator->generate($location, [], false),
                'mainLocation' => [
                    '_media-type' => $this->request->attributes->get('media_type'),
                    '_href' =>
                        '/api/ezp/v2/content/locations'.$location->pathString
                ],
                'locations' => [
                    '_media-type' => $this->request->attributes->get('media_type'),
                    '_href' =>
                        '/api/ezp/v2/content/objects/'.$contentId.'/locations'
                ],
                'categoryPath' => $location->pathString
            ];


            $fields = $this->prepareFields($requestFields, $contentType);

            if (!is_null($fields)) {
                foreach ($fields as $field) {
                    $fieldValue = $contentValue->getFieldValue($field, $language);

                    if (is_null($fieldValue)) {
                        continue;
                    }

                    if ($field == 'image') {
                        $fieldObj = $contentValue->getFieldsByLanguage($language);
                        $fieldValue = $this->imageVariations($fieldObj[$field], $contentValue->versionInfo, $this->request->get('image'));
                    }
                    $content[$contentId]['fields'][] = [
                        'key' => $field,
                        'value' => (string)$fieldValue,
                    ];
                }
            }
        }

        return $content;
    }

    private function imageVariations(Field $fieldValue, $versionInfo, $variation = null)
    {
        $variations = $this->configResolver->getParameter('image_variations');

        if ((null === $variation) || !in_array($variation, array_keys($variations))) {
            return $this->imageVariationService->getVariation($fieldValue, $versionInfo, 'original')->uri;
        } else {
            return $this->imageVariationService->getVariation($fieldValue, $versionInfo, $variation)->uri;
        }
    }
}
