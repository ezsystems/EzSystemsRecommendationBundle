<?php

namespace EzSystems\RecommendationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\Configuration as SiteAccessConfiguration;

class Configuration extends SiteAccessConfiguration
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root( 'ez_recommendation' );

        $systemNode = $this->generateScopeBaseNode( $rootNode );
        $systemNode
            ->arrayNode( 'yoochoose' )
                ->children()
                    ->scalarNode( 'customer_id' )->isRequired()->end()
                    ->scalarNode( 'license_key' )->isRequired()->end()
                ->end()
            ->end()
            ->scalarNode( 'server_uri' )->isRequired()->end();

        return $treeBuilder;
    }
}
