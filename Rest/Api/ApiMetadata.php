<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Api;

/**
 * Class ApiMetadata.
 */
abstract class ApiMetadata
{
    /** @return array */
    abstract public function getMetadataAttributes(): array;

    /**
     * @param object $instance
     * @param array $parameters
     */
    protected function setAttributes(object $instance, array $parameters): void
    {
        foreach ($parameters as $parameterKey => $parameterValue) {
            if (property_exists($instance, $parameterKey)) {
                $instance->$parameterKey = $parameterValue;
            }
        }
    }

    /**
     * @param array $attributes
     * @param string $queryStringKey
     *
     * @return array
     */
    protected function getAdditionalAttributesToQueryString(array $attributes, string $queryStringKey): array
    {
        $extractedAttributes = [];

        foreach ($attributes as $attribute) {
            $extractedAttributes[] = [$queryStringKey => $attribute];
        }

        return $extractedAttributes;
    }
}
