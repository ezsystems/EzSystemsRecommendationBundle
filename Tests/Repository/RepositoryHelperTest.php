<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Tests\Repository;

use EzSystems\RecommendationBundle\Repository\RepositoryHelper;
use PHPUnit_Framework_TestCase;
use eZ\Publish\Core\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\LocationList;
use eZ\Publish\Core\Repository\Values\Content\Content;
use eZ\Publish\Core\Repository\Values\Content\Location;
use eZ\Publish\Core\Repository\Values\Content\VersionInfo;

class RepositoryHelperTest extends PHPUnit_Framework_TestCase
{
    const CONTENT_TYPE_ID = 1;

    const CONTENT_ID = 31415;

    const LOCATION_ID = 5;

    /**
     * Test for the loadContent() method.
     */
    public function testLoadContent()
    {
        $repositoryServiceMock = $this->getRepositoryServiceMock();
        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock
            ->expects($this->once())
            ->method('sudo')
            ->will($this->returnValue(new Content([
                'versionInfo' => new VersionInfo([
                    'contentInfo' => new ContentInfo([
                        'id' => self::CONTENT_ID,
                        'contentTypeId' => self::CONTENT_TYPE_ID,
                    ]),
                ]),
                'internalFields' => [],
            ])));

        /* Use Case */
        $repositoryHelper = new RepositoryHelper(
            $repositoryServiceMock,
            $permissionResolverMock
        );

        $result = $repositoryHelper->loadContent(self::CONTENT_ID);

        $this->assertTrue($result instanceof Content);
        $this->assertEquals(self::CONTENT_ID, $result->contentInfo->id);
    }

    /**
     * Test for the loadVersionInfo() method.
     */
    public function testLoadVersionInfo()
    {
        $repositoryServiceMock = $this->getRepositoryServiceMock();
        $permissionResolverMock = $this->getPermissionResolverMock();

        $contentInfo = new ContentInfo([
            'id' => self::CONTENT_ID,
            'contentTypeId' => self::CONTENT_TYPE_ID,
        ]);

        $permissionResolverMock
            ->expects($this->once())
            ->method('sudo')
            ->will($this->returnValue(new VersionInfo(['contentInfo' => $contentInfo])));

        /* Use Case */
        $repositoryHelper = new RepositoryHelper(
            $repositoryServiceMock,
            $permissionResolverMock
        );

        $result = $repositoryHelper->loadVersionInfo($contentInfo);

        $this->assertTrue($result instanceof VersionInfo);
        $this->assertEquals(self::CONTENT_ID, $result->contentInfo->id);
    }

    /**
     * Test for the loadLocation() method.
     */
    public function testLoadLocation()
    {
        $repositoryServiceMock = $this->getRepositoryServiceMock();
        $permissionResolverMock = $this->getPermissionResolverMock();

        $contentInfo = new ContentInfo([
            'id' => self::CONTENT_ID,
            'contentTypeId' => self::CONTENT_TYPE_ID,
        ]);

        $permissionResolverMock
            ->expects($this->once())
            ->method('sudo')
            ->will($this->returnValue(new Location([
                'id' => self::LOCATION_ID,
                'path' => ['1', '5'],
                'contentInfo' => $contentInfo,
            ])));

        /* Use Case */
        $repositoryHelper = new RepositoryHelper(
            $repositoryServiceMock,
            $permissionResolverMock
        );

        $result = $repositoryHelper->loadLocation(self::LOCATION_ID);

        $this->assertTrue($result instanceof Location);
        $this->assertEquals(self::CONTENT_ID, $result->contentInfo->id);
        $this->assertEquals(self::LOCATION_ID, $result->id);
    }

    /**
     * Test for the loadLocationChildren() method.
     */
    public function testLoadLocationChildren()
    {
        $repositoryServiceMock = $this->getRepositoryServiceMock();
        $permissionResolverMock = $this->getPermissionResolverMock();

        $contentInfo = new ContentInfo([
            'id' => self::CONTENT_ID,
            'contentTypeId' => self::CONTENT_TYPE_ID,
        ]);

        $location = new Location([
            'id' => self::LOCATION_ID,
            'path' => ['1', '5'],
            'contentInfo' => $contentInfo,
        ]);

        $permissionResolverMock
            ->expects($this->once())
            ->method('sudo')
            ->will($this->returnValue(new LocationList([
                'totalCount' => 2,
                'locations' => [
                    new Location([
                        'id' => 20,
                        'path' => ['1', '5', '20'],
                        'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID + 1]),
                    ]),
                    new Location([
                        'id' => 30,
                        'path' => ['1', '5', '30'],
                        'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID + 2]),
                    ]),
                ],
            ])));

        /* Use Case */
        $repositoryHelper = new RepositoryHelper(
            $repositoryServiceMock,
            $permissionResolverMock
        );

        $result = $repositoryHelper->loadLocationChildren($location);

        $this->assertTrue($result instanceof LocationList);
        $this->assertEquals(2, $result->totalCount);
    }

    /**
     * Test for the loadLocations() method.
     */
    public function testLoadLocations()
    {
        $repositoryServiceMock = $this->getRepositoryServiceMock();
        $permissionResolverMock = $this->getPermissionResolverMock();

        $contentInfo = new ContentInfo([
            'id' => self::CONTENT_ID,
            'contentTypeId' => self::CONTENT_TYPE_ID,
        ]);

        $permissionResolverMock
            ->expects($this->once())
            ->method('sudo')
            ->will($this->returnValue([
                    new Location([
                        'id' => 20,
                        'path' => ['1', '5', '20'],
                        'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID + 1]),
                    ]),
                    new Location([
                        'id' => 30,
                        'path' => ['1', '5', '30'],
                        'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID + 2]),
                    ]),
                ]));

        /* Use Case */
        $repositoryHelper = new RepositoryHelper(
            $repositoryServiceMock,
            $permissionResolverMock
        );

        $result = $repositoryHelper->loadLocations($contentInfo);

        $this->assertInternalType('array', $result);
        $this->assertEquals(20, $result[0]->id);
    }

    /**
     * Test for the loadContentType() method.
     */
    public function testLoadContentType()
    {
        $repositoryServiceMock = $this->getRepositoryServiceMock();
        $permissionResolverMock = $this->getPermissionResolverMock();

        $permissionResolverMock
            ->expects($this->once())
            ->method('sudo')
            ->will($this->returnValue(new ContentType(['fieldDefinitions' => [], 'identifier' => self::CONTENT_TYPE_ID])));

        /* Use Case */
        $repositoryHelper = new RepositoryHelper(
            $repositoryServiceMock,
            $permissionResolverMock
        );

        $result = $repositoryHelper->loadContentType(self::CONTENT_TYPE_ID);

        $this->assertTrue($result instanceof ContentType);
        $this->assertEquals(self::CONTENT_TYPE_ID, $result->identifier);
    }

    /**
     * Returns Repository mock object.
     *
     * @return \eZ\Publish\Core\SignalSlot\Repository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRepositoryServiceMock()
    {
        $repositoryServiceMock = $this
            ->getMockBuilder('\eZ\Publish\Core\SignalSlot\Repository')
            ->disableOriginalConstructor()
            ->getMock();

        return $repositoryServiceMock;
    }

    /**
     * Returns PermissionResolver mock object.
     *
     * @return \eZ\Publish\API\Repository\PermissionResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPermissionResolverMock()
    {
        $repositoryServiceMock = $this
            ->getMockBuilder('\eZ\Publish\Core\Repository\Permission\PermissionResolver')
            ->disableOriginalConstructor()
            ->getMock();

        return $repositoryServiceMock;
    }
}
