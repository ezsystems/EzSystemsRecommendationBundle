<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace EzSystems\RecommendationBundle\Values;

/**
* Interface allows to store YooChoose recommendations as a collection
*
* @package EzSystems\RecommendationBundle\Values
*/
interface YooChooseRecommendationsCollection
{
    /**
     * Add YooChoose recommendation to collection
     *
     * @param YooChooseRecommendation $recommendation
     */
    public function add( \EzSystems\RecommendationBundle\Values\YooChooseRecommendation $recommendation );

    /**
     * Get YooChoose recommendation by ID
     *
     * @param int $itemId
     * @return \EzSystems\RecommendationBundle\Values\YooChooseRecommendation
     */
    public function get( $itemId );

    /**
     * Get array of YooChoose recommendation ID's
     *
     * @return array
     */
    public function getKeys();

    /**
     * Count YooChoose recommendations
     *
     * @return int
     */
    public function count();

    /**
     * Check if collection is empty
     *
     * @return bool
     */
    public function isEmpty();
}
