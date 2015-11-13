<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\DependencyInjection;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\HookableConfigurationMapperInterface;

class ConfigurationMapper implements HookableConfigurationMapperInterface
{
    /**
     * {@inheritdoc}
     */
    public function mapConfig(array &$scopeSettings, $currentScope, ContextualizerInterface $contextualizer)
    {
        // Common settings
        if (isset($scopeSettings['server_uri'])) {
            $contextualizer->setContextualParameter('server_uri', $currentScope, $scopeSettings['server_uri']);
        }

        if (isset($scopeSettings['yoochoose']['customer_id'])) {
            $contextualizer->setContextualParameter('yoochoose.customer_id', $currentScope, $scopeSettings['yoochoose']['customer_id']);
        }
        if (isset($scopeSettings['yoochoose']['license_key'])) {
            $contextualizer->setContextualParameter('yoochoose.license_key', $currentScope, $scopeSettings['yoochoose']['license_key']);
        }

        if (isset($scopeSettings['recommender']['included_content_types'])) {
            $contextualizer->setContextualParameter('recommender.included_content_types', $currentScope, $scopeSettings['recommender']['included_content_types']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function preMap(array $config, ContextualizerInterface $contextualizer)
    {
        // Nothing to do here.
    }

    /**
     * {@inheritdoc}
     */
    public function postMap(array $config, ContextualizerInterface $contextualizer)
    {
        // Nothing to do here.
    }
}
