<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace EzSystems\RecommendationBundle\Values;

/**
 * Interface allows to store YooChoose recommendation
 *
 * @package EzSystems\RecommendationBundle\Values
 */
interface YooChooseRecommendation
{
    /**
     * Returns recommendation ID as a string
     *
     * @return string
     */
    public function __toString();

    /**
     * Returns recommendation reason
     *
     * @return string
     */
    public function getReason();

    /**
     * Returns recommendation relevance
     *
     * @return int
     */
    public function getRelevance();

    /**
     * Returns recommendation type
     *
     * @return int
     */
    public function getItemType();

    /**
     * Returns recommendation ID
     *
     * @return int
     */
    public function getItemId();
}
