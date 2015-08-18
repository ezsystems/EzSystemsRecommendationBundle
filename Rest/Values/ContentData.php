<?php

/**
 * This file is part of the EzSystemsRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Values;
use \eZ\Publish\Core\Repository\ContentService;

/**
 * This class holds ContentData structure used by YooChoose recommender engine.
 */
class ContentData
{
    /** @var mixed */
    public $content;

    /**
     * Constructs ContentData object.
     *
     * @param mixed $content
     * @param string $language
     */
    public function __construct($content)
    {
        $this->content = $content;
    }
}
