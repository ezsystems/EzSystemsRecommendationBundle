<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace EzSystems\RecommendationBundle\Values;

use \EzSystems\RecommendationBundle\Values;

class Recommendation implements YooChooseRecommendation
{
    protected $reason;

    protected $relevance;

    protected $itemType;

    protected $itemId;

    public function __construct($itemId, $itemType, $relevance, $reason)
    {
        $this->reason = $reason;
        $this->relevance = $relevance;
        $this->itemType = $itemType;
        $this->itemId = $itemId;
    }

    /**
     * Returns recommendation ID as a string
     *
     * @return string
     */
    public function __toString()
    {
        return strval($this->itemId);
    }

    /**
     * Returns recommendation reason
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Returns recommendation relevance
     *
     * @return int
     */
    public function getRelevance()
    {
        return $this->relevance;
    }

    /**
     * Returns recommendation type
     *
     * @return int
     */
    public function getItemType()
    {
        return $this->itemType;
    }

    /**
     * Returns recommendation ID
     *
     * @return int
     */
    public function getItemId()
    {
        return $this->itemId;
    }
}
