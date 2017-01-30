<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\DependencyInjection;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ConfigurationProcessor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class EzSystemsRecommendationExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('slots.yml');
        $loader->load('default_settings.yml');

        if (isset($config['api_endpoint'])) {
            $container->setParameter('ez_recommendation.api_endpoint', $config['api_endpoint']);
        }
        if (isset($config['recommender']['api_endpoint'])) {
            $container->setParameter('ez_recommendation.recommender.api_endpoint', $config['recommender']['api_endpoint']);
        }
        if (isset($config['recommender']['consume_timeout'])) {
            $container->setParameter('ez_recommendation.recommender.consume_timeout', $config['recommender']['consume_timeout']);
        }
        if (isset($config['tracking']['script_url'])) {
            $container->setParameter('ez_recommendation.tracking.script_url', $config['tracking']['script_url']);
        }
        if (isset($config['tracking']['api_endpoint'])) {
            $container->setParameter('ez_recommendation.tracking.api_endpoint', $config['tracking']['api_endpoint']);
        }
        if (isset($config['system'])) {
            $container->setParameter('ez_recommendation.siteaccess_config', $config['system']);
        }

        $processor = new ConfigurationProcessor($container, 'ez_recommendation');
        $processor->mapConfig($config, new ConfigurationMapper());
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'ez_recommendation';
    }
}
