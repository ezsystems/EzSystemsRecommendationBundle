<?php

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
