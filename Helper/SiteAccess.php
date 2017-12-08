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
use LogicException;

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

    /**
     * Returns list of rootLocations from siteAccess list.
     *
     * @param array $siteAccesses
     *
     * @return array
     */
    public function getRootLocationsBySiteAccesses(array $siteAccesses)
    {
        $rootLocations = [];

        foreach ($siteAccesses as $siteAccess) {
            $rootLocationId = $this->getRootLocationBySiteAccessName($siteAccess);

            $rootLocations[$rootLocationId] = $rootLocationId;
        }

        return array_keys($rootLocations);
    }

    /**
     * Returns languages based on mandatorId or siteaccess.
     *
     * @param null|int $mandatorId
     * @param null|string $siteAccess
     *
     * @return array
     */
    public function getLanguages($mandatorId = null, $siteAccess = null)
    {
        if ($mandatorId) {
            $languages = $this->getMainLanguagesBySiteAccesses(
                $this->getSiteAccessesByMandatorId($mandatorId)
            );
        } elseif ($siteAccess) {
            $languages = $this->configResolver->getParameter('languages', '', $siteAccess);
        } else {
            $languages = $this->configResolver->getParameter('languages');
        }

        if (empty($languages)) {
            throw new LogicException(sprintf('No languages found using SiteAccess or mandatorId'));
        }

        return $languages;
    }

    /**
     * @param null|int $mandatorId
     *
     * @return array
     *
     * @throws NotFoundException
     */
    public function getSiteAccessesByMandatorId($mandatorId = null)
    {
        if ($mandatorId === null) {
            return array($this->siteAccess->name);
        }

        $siteAccesses = array();

        foreach ($this->siteAccessConfig as $name => $config) {
            if (isset($config['yoochoose']['customer_id']) && $config['yoochoose']['customer_id'] == $mandatorId) {
                $siteAccesses[$name] = $name;
            }
        }

        if (empty($siteAccesses)) {
            throw new NotFoundException('configuration for eZ Recommendation', "mandatorId: {$mandatorId}");
        }

        return $siteAccesses;
    }

    /**
     * Returns siteAccesses based on mandatorId, requested siteAccess or default SiteAccess.
     *
     * @param null|int $mandatorId
     * @param null|string $siteAccess
     *
     * @return array
     */
    public function getSiteAccesses($mandatorId = null, $siteAccess = null)
    {
        if ($mandatorId) {
            $siteAccesses = $this->getSiteaccessesByMandatorId($mandatorId);
        } elseif ($siteAccess) {
            $siteAccesses = array($siteAccess);
        } else {
            $siteAccesses = array($this->siteAccess->name);
        }

        return $siteAccesses;
    }

    /**
     * Returns Recommendation Service credentials based on current siteAccess.
     *
     * @return array
     */
    public function getRecommendationServiceCredentials()
    {
        $customerId = $this->configResolver->getParameter('yoochoose.customer_id', 'ez_recommendation', $this->siteAccess->name);
        $licenceKey = $this->configResolver->getParameter('yoochoose.license_key', 'ez_recommendation', $this->siteAccess->name);

        return [$customerId, $licenceKey];
    }

    /**
     * Returns main languages from siteAccess list.
     *
     * @param string $siteAccesses
     *
     * @return array
     */
    private function getMainLanguagesBySiteAccesses($siteAccesses)
    {
        $languages = array();

        foreach ($siteAccesses as $siteAccess) {
            $languageList = $this->configResolver->getParameter('languages', '', $siteAccess);
            $mainLanguage = reset($languageList);
            $languages[$mainLanguage] = $mainLanguage;
        }

        return array_keys($languages);
    }
}
