<?php
/**
 * This file is part of the EzSystemRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributd with this source code.
 */

namespace EzSystems\RecommendationBundle;

use EzSystems\RecommendationBundle\DependencyInjection\EzSystemsRecommendationExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EzSystemsRecommendationBundle extends Bundle
{
    /** @var EzSystemsRecommendationExtension */
    protected $extension;

    public function getContainerExtension()
    {
        if (!isset($this->extension)) {
            $this->extension = new EzSystemsRecommendationExtension();
        }

        return $this->extension;
    }
}
