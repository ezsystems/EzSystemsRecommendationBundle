<?php
/**
 * This file is part of the EzSystemRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Response;

use EzSystems\RecommendationBundle\Rest\ValueObjectVisitor\ContentListElementGenerator;

abstract class Response implements ResponseInterface
{
    /** @var \EzSystems\RecommendationBundle\Rest\ValueObjectVisitor\ContentListElementGenerator */
    public $contentListElementGenerator;

    /**
     * @param \EzSystems\RecommendationBundle\Rest\ValueObjectVisitor\ContentListElementGenerator $contentListElementGenerator
     */
    public function __construct(ContentListElementGenerator $contentListElementGenerator)
    {
        $this->contentListElementGenerator = $contentListElementGenerator;
    }
}
