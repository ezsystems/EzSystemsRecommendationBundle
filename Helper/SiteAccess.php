<?php
/**
 * This file is part of the EzSystemRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Helper;

use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Symfony\SiteAccess as CurrentSiteAccess;

class SiteAccess
{
    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    private $configResolver;

    /** @var \eZ\Publish\Core\MVC\Symfony\SiteAccess */
    private $siteAccess;

    /** @var array $siteAccessConfig */
    private $siteAccessConfig;

    public function __construct(
        ConfigResolverInterface $configResolver,
        CurrentSiteAccess $siteAccess,
        $siteAccessConfig
    ) {
        $this->configResolver = $configResolver;
        $this->siteAccess = $siteAccess;
        $this->siteAccessConfig = $siteAccessConfig;
    }

    /**
     * Returns list of rootLocations from siteAccess list.
     *
     * @param array $siteAccesses
     *
     * @return array
     */
    public function getRootLocationsBySiteaccesses(array $siteAccesses)
    {
        $rootLocations = [];

        foreach ($siteAccesses as $siteAccess) {
            $rootLocationId = $this->configResolver->getParameter('content.tree_root.location_id', null, $siteAccess);

            $rootLocations[$rootLocationId] = $rootLocationId;
        }

        return array_keys($rootLocations);
    }

    /**
     * @param null|int $mandatorId
     *
     * @return array
     *
     * @throws NotFoundException
     */
    public function getSiteaccessesByMandatorId($mandatorId = 0)
    {
        if (0 == $mandatorId) {
            return array($this->siteAccess->name);
        }

        $siteaccesses = array();

        foreach ($this->siteAccessConfig as $name => $config) {
            if (isset($config['yoochoose']['customer_id']) && $config['yoochoose']['customer_id'] == $mandatorId) {
                $siteaccesses[$name] = $name;
            }
        }

        if (empty($siteaccesses)) {
            throw new NotFoundException('No config for mandator found', $mandatorId);
        }

        return $siteaccesses;
    }

    /**
     * Returns main languages from siteAccess list.
     *
     * @param $siteaccesses
     *
     * @return array
     */
    public function getMainLanguagesBySiteaccesses($siteaccesses)
    {
        $languages = array();

        foreach ($siteaccesses as $siteAccess) {
            $languageList = $this->configResolver->getParameter('languages', '', $siteAccess);
            $mainLanguage = reset($languageList);
            $languages[$mainLanguage] = $mainLanguage;
        }

        return array_keys($languages);
    }
}
