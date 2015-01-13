<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributd with this source code.
 */
namespace EzSystems\RecommendationBundle\eZ\Publish\LegacySearch;

use eZ\Publish\Core\MVC\Legacy\Event\PostBuildKernelEvent;
use eZ\Publish\Core\MVC\Legacy\LegacyEvents;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use ezpSearchEngine;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigurationMapper implements EventSubscriberInterface
{
    /** @var \ezpSearchEngine */
    private $recommendationLegacySearch;

    /** @var \eZ\Publish\Core\MVC\Symfony\SiteAccess */
    private $siteaccess;

    public function __construct(ezpSearchEngine $recommendationLegacySearch, Siteaccess $siteaccess)
    {
        $this->recommendationLegacySearch = $recommendationLegacySearch;
        $this->siteaccess = $siteaccess;
    }

    public static function getSubscribedEvents()
    {
        return array(
            LegacyEvents::POST_BUILD_LEGACY_KERNEL => array( "onBuildKernel", 0 ),
        );
    }

    public function onBuildKernel(PostBuildKernelEvent $event)
    {
        $GLOBALS["eZSearchPlugin_" . $this->siteaccess->name] = $this->recommendationLegacySearch;
    }
}
