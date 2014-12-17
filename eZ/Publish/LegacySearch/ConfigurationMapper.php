<?php
/**
 * This file is part of the eZ Publish Kernel package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributd with this source code.
 */
namespace EzSystems\RecommendationBundle\eZ\Publish\LegacySearch;

use eZ\Publish\Core\MVC\Legacy\Event\PostBuildKernelEvent;
use eZ\Publish\Core\MVC\Legacy\LegacyEvents;
use ezpSearchEngine;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigurationMapper implements EventSubscriberInterface
{
    /** @var \ezpSearchEngine */
    private $recommendationLegacySearch;

    public function __construct(ezpSearchEngine $recommendationLegacySearch)
    {
        $this->recommendationLegacySearch = $recommendationLegacySearch;
    }

    public static function getSubscribedEvents()
    {
        return array(
            LegacyEvents::POST_BUILD_LEGACY_KERNEL => array( "onBuildKernel", 0 ),
        );
    }

    public function onBuildKernel(PostBuildKernelEvent $event)
    {
        $siteaccess = 'ezdemo_site_admin';
        $GLOBALS["eZSearchPlugin_$siteaccess"] = $this->recommendationLegacySearch;
    }
}
