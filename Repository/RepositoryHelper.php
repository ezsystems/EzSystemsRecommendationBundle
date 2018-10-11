<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Repository;

use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Repository;

/**
 * EzPublish Repository Helper.
 *
 * @internal
 */
class RepositoryHelper
{
    /** @var \eZ\Publish\API\Repository\Repository */
    private $repository;

    /** @var \eZ\Publish\API\Repository\PermissionResolver */
    private $permissionResolver;

    /**
     * @param \eZ\Publish\Core\SignalSlot\Repository $repository
     * @param \eZ\Publish\API\Repository\PermissionResolver $permissionResolver
     */
    public function __construct(
        Repository $repository,
        PermissionResolver $permissionResolver
    ) {
        $this->repository = $repository;
        $this->permissionResolver = $permissionResolver;
    }

    /**
     * Loads content in a version of the given content object (omitting permission check).
     *
     * @param mixed $contentId
     * @param array|null $languages
     * @param int|null $versionNo
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Content
     */
    public function loadContent($contentId, array $languages = null, $versionNo = null)
    {
        return $this->permissionResolver->sudo(
            function (Repository $repository) use ($contentId, $languages, $versionNo) {
                return $repository->getContentService()->loadContent($contentId, $languages, $versionNo);
            },
            $this->repository
        );
    }

    /**
     * Loads a version info of the given content object (omitting permission check).
     *
     * @param ContentInfo $contentInfo
     * @param int|null $versionNo
     *
     * @return \eZ\Publish\API\Repository\Values\Content\VersionInfo
     */
    public function loadVersionInfo(ContentInfo $contentInfo, $versionNo = null)
    {
        return $this->permissionResolver->sudo(
            function (Repository $repository) use ($contentInfo, $versionNo) {
                return $repository->getContentService()->loadVersionInfo($content->contentInfo, $versionNo);
            },
            $this->repository
        );
    }

    /**
     * Loads a location object from its $locationId (omitting permission check).
     *
     * @param mixed $locationId
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Location
     */
    public function loadLocation($locationId)
    {
        return $this->permissionResolver->sudo(
            function (Repository $repository) use ($locationId) {
                return $repository->getLocationService()->loadLocation($locationId);
            },
            $this->repository
        );
    }

    /**
     * Loads children which are readable by the current user of a location object (omitting permission check).
     *
     * @param Location $location
     *
     * @return \eZ\Publish\API\Repository\Values\Content\LocationList
     */
    public function loadLocationChildren(Location $location)
    {
        return $this->permissionResolver->sudo(
            function (Repository $repository) use ($location) {
                return $repository->getLocationService()->loadLocationChildren($location);
            },
            $this->repository
        );
    }

    /**
     * Loads the locations for the given content object (omitting permission check).
     *
     * @param ContentInfo $contentInfo
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Location[]
     */
    public function loadLocations(ContentInfo $contentInfo)
    {
        return $this->permissionResolver->sudo(
            function (Repository $repository) use ($contentInfo) {
                return $repository->getLocationService()->loadLocations($content->contentInfo);
            },
            $this->repository
        );
    }

    /**
     * Get a Content Type object by id (omitting permission check).
     *
     * @param mixed $contentTypeId
     *
     * @return \eZ\Publish\API\Repository\Values\ContentType\ContentType
     */
    public function loadContentType($contentTypeId)
    {
        return $this->permissionResolver->sudo(
            function (Repository $repository) use ($contentTypeId) {
                return $repository->getContentTypeService()->loadContentType($contentTypeId);
            },
            $this->repository
        );
    }
}
