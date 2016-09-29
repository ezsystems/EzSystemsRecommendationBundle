<?php
/**
 * This file is part of the EzSystemRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Response;

use eZ\Publish\Core\REST\Common\Output\Generator;

class HttpResponse extends Response
{
    /**
     * {@inheritdoc}
     */
    public function render(Generator $generator, $data)
    {
        return $this->contentListElementGenerator->generateElement($generator, $data->contents);
    }
}
