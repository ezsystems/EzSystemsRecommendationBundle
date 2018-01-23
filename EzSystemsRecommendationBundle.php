<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle;

use EzSystems\RecommendationBundle\DependencyInjection\EzSystemsRecommendationExtension;
use EzSystems\RecommendationBundle\DependencyInjection\Compiler\RestResponsePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EzSystemsRecommendationBundle extends Bundle
{
    /** @var \EzSystems\RecommendationBundle\DependencyInjection\EzSystemsRecommendationExtension */
    protected $extension;

    /**
     * @return \EzSystems\RecommendationBundle\DependencyInjection\EzSystemsRecommendationExtension
     */
    public function getContainerExtension()
    {
        if (!isset($this->extension)) {
            $this->extension = new EzSystemsRecommendationExtension();
        }

        return $this->extension;
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RestResponsePass());
    }
}
