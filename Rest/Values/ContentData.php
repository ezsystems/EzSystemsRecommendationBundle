<?php
/**
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

    /** @var array */
    public $options;

    /**
     * Constructs ContentData object.
     *
     * @param array $contents
     * @param array $options
     */
    public function __construct(array $contents, array $options = [])
    {
        $this->contents = $contents;
        $this->options = $options;
    }
}
