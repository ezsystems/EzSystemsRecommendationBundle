<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace EzSystems\RecommendationBundle\Helper;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Helper class for building criteria easily.
 */
class LocationHelper
{
    protected $repository, $router;

    public function __construct( $repository, $router )
    {
        $this->repository = $repository;
        $this->router = $router;
    }

    /**
     * Transform location object into array
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     * @param string $language
     * @return array
     */
    public function mapLocationToArray( $location, $language )
    {
        $contentData = $this->repository->getContentService()->loadContentByContentInfo( $location->valueObject->contentInfo );

        return array(
            'name' => $location->valueObject->contentInfo->name,
            'url' => $this->router->generate( $location->valueObject, array(), UrlGeneratorInterface::ABSOLUTE_PATH ),
            'image' => $contentData->getFieldValue( 'image', $language )->uri,
            'intro' => $contentData->getFieldValue( 'intro', $language )->xml->textContent,
            'timestamp' => $contentData->getVersionInfo()->creationDate->getTimestamp(),
            'author' => (string) $contentData->getFieldValue( 'author', $language )
        );
    }
}
