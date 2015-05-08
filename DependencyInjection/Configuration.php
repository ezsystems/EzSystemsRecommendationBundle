<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace EzSystems\RecommendationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\Configuration as SiteAccessConfiguration;

class Configuration extends SiteAccessConfiguration
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ez_recommendation');

        $systemNode = $this->generateScopeBaseNode($rootNode);
        $systemNode
            ->arrayNode('yoochoose')
                ->children()
                    ->scalarNode('customer_id')
                        ->info("YooChoose customer ID")
                        ->example("12345")
                        ->isRequired()
                    ->end()
                    ->scalarNode('license_key')
                        ->info("YooChoose license key")
                        ->example("1234-5678-9012-3456-7890")
                        ->isRequired()
                    ->end()
                ->end()
            ->end()
            ->scalarNode('server_uri')
                ->info("HTTP base URI of the eZ Publish server")
                ->example("http://site.com")
                ->isRequired()
            ->end();

        return $treeBuilder;
    }
}
