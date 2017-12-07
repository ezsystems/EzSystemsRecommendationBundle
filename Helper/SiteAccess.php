<?php
/**
 * This file is part of the EzSystemRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Helper;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Symfony\SiteAccess as CurrentSiteAccess;

class SiteAccess
{
    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    private $configResolver;

    /** @var \eZ\Publish\Core\MVC\Symfony\SiteAccess */
    private $siteAccess;

    public function __construct(
        ConfigResolverInterface $configResolver,
        CurrentSiteAccess $siteAccess
    ) {
        $this->configResolver = $configResolver;
        $this->siteAccess = $siteAccess;
    }

    /**
     * Returns rootLocation by siteAccess name or by default siteAccess.
     *
     * @param string|null $siteAccessName
     *
     * @return int
     */
    public function getRootLocationBySiteAccessName($siteAccessName = null)
    {
        return $this->configResolver->getParameter(
            'content.tree_root.location_id',
            null,
            $siteAccessName ?: $this->siteAccess->name
        );
    }
}
