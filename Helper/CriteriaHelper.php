<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace EzSystems\RecommendationBundle\Helper;

use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;

/**
 * Helper class for building criteria easily.
 */
class CriteriaHelper
{
    /**
     * Generates an criterion based on contentType identifiers.
     *
     * @param array $contentIds collection of content ID's to be included
     * @return LocationQuery
     */
    public function generateContentTypeCriterion( $contentIds )
    {
        $criterion = new Criterion\LogicalAnd( array(
            new Criterion\Visibility( Criterion\Visibility::VISIBLE ),
            new Criterion\ContentId( $contentIds ),
            new Criterion\ContentTypeIdentifier( array( 'article', 'blog_post' ) )
        ));

        $contentQuery = new LocationQuery();
        $contentQuery->criterion = $criterion;
        $contentQuery->sortClauses = array(
            new SortClause\ContentName()
        );

        return $contentQuery;
    }

}
