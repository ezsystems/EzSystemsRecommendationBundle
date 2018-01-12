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
    const DEFAULT_SITEACCESS_NAME = 'default';

    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    private $configResolver;

    /** @var \eZ\Publish\Core\MVC\Symfony\SiteAccess */
    private $siteAccess;

    /** @var array */
    private $siteAccessConfig;

    /** @var string */
    private $defaultSiteAccessName;

    public function __construct(
        ConfigResolverInterface $configResolver,
        CurrentSiteAccess $siteAccess,
        $siteAccessConfig,
        $defaultSiteAccessName
    ) {
        $this->configResolver = $configResolver;
        $this->siteAccess = $siteAccess;
        $this->siteAccessConfig = $siteAccessConfig;
        $this->defaultSiteAccessName = $defaultSiteAccessName;
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

                if ($name === self::DEFAULT_SITEACCESS_NAME && $this->defaultSiteAccessName !== self::DEFAULT_SITEACCESS_NAME) {
                    // default siteAccess name is changed and configuration should be adjusted
                    $siteAccesses[$this->defaultSiteAccessName] = $this->defaultSiteAccessName;
                }
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
     * Returns Recommendation Service credentials based on current siteAccess or mandatorId.
     *
     * @param null|int $mandatorId
     * @param null|string $siteAccess
     *
     * @return array
     */
    public function getRecommendationServiceCredentials($mandatorId = null, $siteAccess = null)
    {
        if ($mandatorId) {
            $siteAccesses = $this->getSiteAccessesByMandatorId($mandatorId);
            $siteAccess = end($siteAccesses);
        } elseif ($siteAccess == null) {
            $siteAccess = $this->siteAccess->name;
        }

        $customerId = $this->configResolver->getParameter('yoochoose.customer_id', 'ez_recommendation', $siteAccess);
        $licenceKey = $this->configResolver->getParameter('yoochoose.license_key', 'ez_recommendation', $siteAccess);

        return [$customerId, $licenceKey];
    }

    /**
     * Returns main languages from siteAccess list.
     *
     * @param array $siteAccesses
     *
     * @return array
     */
    private function getMainLanguagesBySiteAccesses($siteAccesses)
    {
        $languages = array();

        foreach ($siteAccesses as $siteAccess) {
            $languageList = $this->configResolver->getParameter('languages', '', $siteAccess);
            $mainLanguage = reset($languageList);

            if ($mainLanguage) {
                $languages[$mainLanguage] = $mainLanguage;
            }
        }

        return array_keys($languages);
    }
}
