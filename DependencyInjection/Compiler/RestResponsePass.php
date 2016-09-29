<?php
/**
 * This file is part of the EzSystemRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RestResponsePass implements CompilerPassInterface
{
    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $visitor = $container->getDefinition('ez_recommendation.value_object_visitor.content_data');
        $responseRenderers = [];

        foreach ($container->findTaggedServiceIds('ez_recommendation.rest.response_type') as $id => $tags) {
            $responseRenderers[$tags[0]['type']] = new Reference($id);
        }

        $visitor->addMethodCall('setResponseRendereres', [$responseRenderers]);
    }
}
