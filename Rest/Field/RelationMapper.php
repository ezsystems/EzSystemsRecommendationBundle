<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Field;

class RelationMapper
{
    /** @var array $fieldMapping */
    protected $fieldMappings;

    /**
     * @param array $fieldMapping
     */
    public function __construct(array $fieldMappings)
    {
        $this->fieldMappings = $fieldMappings;
    }

    /**
     * Get related mapping for specified content and field.
     *
     * @param string $contentTypeIdentifier
     * @param string $fieldIdentifier
     *
     * @return mixed Returns mathing mapping array or false if no matching mapping found
     */
    public function getMapping($contentTypeIdentifier, $fieldIdentifier)
    {
        $key = $contentTypeIdentifier . '.' . $fieldIdentifier;

        if (!isset($this->fieldMappings[$key])) {
            return false;
        }

        $identifier = explode('.', $this->fieldMappings[$key]);

        return array(
            'content' => $identifier[0],
            'field' => $identifier[1],
        );
    }
}
