<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Field;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;

class Value
{
    /** @var \eZ\Publish\API\Repository\ContentService */
    private $contentService;

    /** @var \eZ\Publish\API\Repository\ContentTypeService */
    private $contentTypeService;

    /** @var \EzSystems\RecommendationBundle\Rest\Field\TypeValue */
    private $typeValue;

    /** @var array */
    protected $parameters;

    /** @var array */
    public $fieldDefIdentifiers;

    /**
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     * @param \eZ\Publish\API\Repository\ContentTypeService $contentTypeService
     * @param \EzSystems\RecommendationBundle\Rest\Field\TypeValue $typeValue
     * @param array $parameters
     */
    public function __construct(
        ContentService $contentService,
        ContentTypeService $contentTypeService,
        TypeValue $typeValue,
        array $parameters
    ) {
        $this->contentService = $contentService;
        $this->contentTypeService = $contentTypeService;
        $this->typeValue = $typeValue;
        $this->parameters = $parameters;
    }

    /**
     * Returns parsed field value.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     * @param string $field
     * @param string $language
     *
     * @return array
     */
    public function getFieldValue(Content $content, $field, $language)
    {
        $fieldObj = $content->getField($field, $language);
        $relatedField = $this->getRelation($content, $field, $language);
        $imageFieldIdentifier = $this->getImageFieldIdentifier($content->id, $language);

        if ($relatedField) {
            $content = $this->contentService->loadContent($relatedField);
            $field = $imageFieldIdentifier;
        }

        $value = '';
        if ($fieldObj) {
            $value = $this->getParsedFieldValue($fieldObj, $content, $language, $imageFieldIdentifier);
        }

        return array(
            'key' => $field,
            'value' => $value,
        );
    }

    /**
     * Return identifier of a field of ezimage type.
     *
     * @param mixed $contentId
     * @param string $language
     * @param bool $related
     *
     * @return string
     */
    private function getImageFieldIdentifier($contentId, $language, $related = false)
    {
        $content = $this->contentService->loadContent($contentId);
        $contentType = $this->contentTypeService->loadContentType($content->contentInfo->contentTypeId);

        $fieldDefinitions = $this->getFieldDefinitionList();
        $fieldNames = array_flip($fieldDefinitions);

        if (in_array('ezimage', $fieldDefinitions)) {
            return $fieldNames['ezimage'];
        } elseif (in_array('ezobjectrelation', $fieldDefinitions) && !$related) {
            $field = $content->getFieldValue($fieldNames['ezobjectrelation'], $language);

            if (!empty($field->destinationContentId)) {
                return $this->getImageFieldIdentifier($field->destinationContentId, $language, true);
            }
        } else {
            return $this->getConfiguredFieldIdentifier('image', $contentType);
        }
    }

    /**
     * Checks if content has image relation field, returns its ID if true.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     * @param string $field
     * @param string $language
     *
     * @return int|null
     */
    private function getRelation(Content $content, $field, $language)
    {
        $fieldDefinitions = $this->getFieldDefinitionList();
        $fieldNames = array_flip($fieldDefinitions);
        $isRelation = (in_array('ezobjectrelation', $fieldDefinitions) && $field == $fieldNames['ezobjectrelation']);

        if ($isRelation && $field == $fieldNames['ezobjectrelation']) {
            $fieldValue = $content->getFieldValue($fieldNames['ezobjectrelation'], $language);

            if (isset($fieldValue->destinationContentId)) {
                return $fieldValue->destinationContentId;
            }
        }

        return null;
    }

    /**
     * Returns field name.
     *
     * To define another field name for specific value (e. g. author) add it to parameters.yml
     *
     * For example:
     *
     *     ez_recommendation.field_identifiers:
     *         author:
     *             blog_post: authors
     *         image:
     *             blog_post: thumbnail
     *
     * @param string $fieldName
     * @param \eZ\Publish\API\Repository\Values\ContentType\ContentType $contentType
     *
     * @return string
     */
    public function getConfiguredFieldIdentifier($fieldName, ContentType $contentType)
    {
        $contentTypeName = $contentType->identifier;

        if (isset($this->parameters['fieldIdentifiers'])) {
            $fieldDefinitions = $this->parameters['fieldIdentifiers'];

            if (isset($fieldDefinitions[$fieldName]) && !empty($fieldDefinitions[$fieldName][$contentTypeName])) {
                return $fieldDefinitions[$fieldName][$contentTypeName];
            }
        }

        return $fieldName;
    }

    /**
     * Prepares an array with field type identifiers.
     *
     * @param \eZ\Publish\API\Repository\Values\ContentType\ContentType $contentType
     */
    public function setFieldDefinitionsList(ContentType $contentType)
    {
        foreach ($contentType->fieldDefinitions as $fieldDef) {
            $this->fieldDefIdentifiers[$fieldDef->identifier] = $fieldDef->fieldTypeIdentifier;
        }
    }

    /**
     * Returns field definition.
     *
     * @param string $identifier
     *
     * @return mixed
     */
    public function getFieldDefinition($identifier)
    {
        return $this->fieldDefIdentifiers[$identifier];
    }

    /**
     * Returns field definitions.
     *
     * @return mixed
     */
    public function getFieldDefinitionList()
    {
        return $this->fieldDefIdentifiers;
    }

    /**
     * Returns parsed field value.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Field $field
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     * @param string $language
     * @param string $imageFieldIdentifier
     *
     * @return mixed
     */
    public function getParsedFieldValue(Field $field, Content $content, $language, $imageFieldIdentifier)
    {
        $fieldType = $this->fieldDefIdentifiers[$field->fieldDefIdentifier];

        return $this->typeValue->$fieldType($field, $content, $language, $imageFieldIdentifier);
    }
}
