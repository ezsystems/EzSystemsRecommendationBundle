<?php

/**
 * This file is part of the EzSystemsRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Values;

/**
 * This class holds ContentData structure used by YooChoose recommender engine.
 */
class ContentData
{
    /** @var array */
    public $contents;

    /**
     * Constructs ContentData object.
     *
     * @param array $contents
     */
    public function __construct($contents)
    {
        $this->contents = $contents;
    }
}
