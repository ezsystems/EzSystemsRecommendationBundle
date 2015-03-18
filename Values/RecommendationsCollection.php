<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace EzSystems\RecommendationBundle\Values;

use \EzSystems\RecommendationBundle\Values;

class RecommendationsCollection implements YooChooseRecommendationsCollection
{
    protected $collection;

    /**
     * Add YooChoose recommendation to collection
     *
     * @param YooChooseRecommendation $recommendation
     */
    public function add( \EzSystems\RecommendationBundle\Values\YooChooseRecommendation $recommendation )
    {
        $this->collection[ $recommendation->getItemId() ] = $recommendation;
    }

    /**
     * Get YooChoose recommendation by ID
     *
     * @param int $itemId
     * @throws OutOfRangeException if recommendation ID is out of index
     * @return \EzSystems\RecommendationBundle\Values\YooChooseRecommendation
     */
    public function get( $itemId )
    {
        if ( array_key_exists( $itemId, $this->collection ) )
            return $this->collection[ $itemId ];
        else
            throw new OutOfRangeException();
    }

    /**
     * Get array of YooChoose recommendation ID's
     *
     * @return array
     */
    public function getKeys()
    {
        return array_map(
            function( $item )
            {
                return $item->getItemId();
            },
            $this->collection
        );
    }

    /**
     * Count YooChoose recommendations
     *
     * @return int
     */
    public function count()
    {
        return count( $this->collection );
    }

    /**
     * Check if collection is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return count( $this->collection ) > 0 ? false : true;
    }
}
