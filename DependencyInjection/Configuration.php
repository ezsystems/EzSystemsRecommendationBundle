<?php

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
            ->arrayNode('recommender')
                ->children()
                    ->scalarNode('api_endpoint')
                        ->info('YooChoose recommender end-point URI address')
                        ->example('http://event.yoochoose.net')
                        ->isRequired()
                    ->end()
                    ->scalarNode('consume_timeout')
                        ->info('Describes when `consume` event should be submitted after page loading (in seconds)')
                        ->example('20')
                        ->isRequired()
                    ->end()
                    ->arrayNode('included_content_types')
                        ->prototype('array')
                        ->info('Content types on which tracking code will be shown')
                        ->isRequired()
                        ->children()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('tracking')
                ->children()
                    ->scalarNode('api_endpoint')
                        ->info('YooChoose tracking end-point URI address')
                        ->example('http://event.yoochoose.net')
                        ->isRequired()
                    ->end()
                    ->scalarNode('script_url')
                        ->info('YooChoose tracking script address')
                        ->example('cdn.yoochoose.net/yct.js')
                        ->isRequired()
                    ->end()
                ->end()
            ->end()
            ->scalarNode('server_uri')
                ->info("HTTP base URI of the eZ Publish server")
                ->example("http://site.com")
                ->isRequired()
            ->end()
            ->scalarNode('api_endpoint')
                ->info('YooChoose end-point URI address')
                ->example('https://admin.yoochoose.net')
                ->isRequired()
            ->end();

        return $treeBuilder;
    }
}
