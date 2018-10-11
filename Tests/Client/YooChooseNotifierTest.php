<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Tests\Client;

use PHPUnit_Framework_TestCase;
use GuzzleHttp\Psr7\Response;
use eZ\Publish\Core\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\LocationList;
use eZ\Publish\Core\Repository\Values\Content\Content;
use eZ\Publish\Core\Repository\Values\Content\Location;
use eZ\Publish\Core\Repository\Values\Content\VersionInfo;
use EzSystems\RecommendationBundle\Client\YooChooseNotifier;
use Psr\Log\NullLogger;

class YooChooseNotifierTest extends PHPUnit_Framework_TestCase
{
    const CUSTOMER_ID = '12345';

    const LICENSE_KEY = '1234-5678-9012-3456-7890';

    const SERVER_URI = 'http://example.com';

    const API_ENDPOINT = 'http://yoochoose.example.com';

    const CONTENT_TYPE_ID = 1;

    const CONTENT_ID = 31415;

    const LOCATION_ID = 5;

    /**
     * Test for the updateContent() method.
     */
    public function testUpdateContent()
    {
        $guzzleClientMock = $this->getGuzzleClientMock();
        $this->setGuzzleExpectationsFor('UPDATE', $guzzleClientMock);

        $repositoryHelperMock = $this->getMockBuilder('EzSystems\RecommendationBundle\Repository\RepositoryHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $repositoryHelperMock
            ->expects($this->at(0))
            ->method('loadContent')
            ->with($this->equalTo(self::CONTENT_ID))
            ->willReturn(new Content([
                'versionInfo' => new VersionInfo([
                    'contentInfo' => new ContentInfo([
                        'id' => self::CONTENT_ID,
                        'contentTypeId' => self::CONTENT_TYPE_ID,
                    ]),
                ]),
                'internalFields' => [],
            ]));

        $repositoryHelperMock
            ->expects($this->at(1))
            ->method('loadContentType')
            ->with($this->equalTo(self::CONTENT_TYPE_ID))
            ->willReturn(new ContentType(['fieldDefinitions' => [], 'identifier' => self::CONTENT_TYPE_ID]));

        $repositoryHelperMock
            ->expects($this->at(2))
            ->method('loadVersionInfo')
            ->with(new ContentInfo(['id' => self::CONTENT_ID, 'contentTypeId' => self::CONTENT_TYPE_ID]))
            ->will($this->returnValue(new VersionInfo(['languageCodes' => ['eng-GB']])));

        /* Use Case */
        $notifier = new YooChooseNotifier(
            $guzzleClientMock,
            $repositoryHelperMock,
            [
                'customer-id' => self::CUSTOMER_ID,
                'license-key' => self::LICENSE_KEY,
                'api-endpoint' => self::API_ENDPOINT,
                'server-uri' => self::SERVER_URI,
            ],
            new NullLogger()
        );
        $notifier->setIncludedContentTypes([self::CONTENT_TYPE_ID]);
        $notifier->updateContent(self::CONTENT_ID);
    }

    /**
     * Test for the deleteContent() method.
     */
    public function testDeleteContent()
    {
        $guzzleClientMock = $this->getGuzzleClientMock();
        $this->setGuzzleExpectationsFor('DELETE', $guzzleClientMock);

        $repositoryHelperMock = $this->getMockBuilder('EzSystems\RecommendationBundle\Repository\RepositoryHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $repositoryHelperMock
            ->expects($this->at(0))
            ->method('loadContent')
            ->with($this->equalTo(self::CONTENT_ID))
            ->willReturn(new Content([
                'versionInfo' => new VersionInfo([
                    'contentInfo' => new ContentInfo([
                        'id' => self::CONTENT_ID,
                        'contentTypeId' => self::CONTENT_TYPE_ID,
                    ]),
                ]),
                'internalFields' => [],
            ]));

        $repositoryHelperMock
            ->expects($this->at(1))
            ->method('loadContentType')
            ->with($this->equalTo(self::CONTENT_TYPE_ID))
            ->willReturn(new ContentType(['fieldDefinitions' => [], 'identifier' => self::CONTENT_TYPE_ID]));

        $repositoryHelperMock
            ->expects($this->at(2))
            ->method('loadVersionInfo')
            ->with(new ContentInfo(['id' => self::CONTENT_ID, 'contentTypeId' => self::CONTENT_TYPE_ID]))
            ->will($this->returnValue(new VersionInfo(['languageCodes' => ['eng-GB']])));

        /* Use Case */
        $notifier = new YooChooseNotifier(
            $guzzleClientMock,
            $repositoryHelperMock,
            [
                'customer-id' => self::CUSTOMER_ID,
                'license-key' => self::LICENSE_KEY,
                'api-endpoint' => self::API_ENDPOINT,
                'server-uri' => self::SERVER_URI,
            ],
            new NullLogger()
        );
        $notifier->setIncludedContentTypes([self::CONTENT_TYPE_ID]);
        $notifier->deleteContent(self::CONTENT_ID);
    }

    /**
     * Test for the hideLocation() method without children in location.
     */
    public function testHideLocation()
    {
        $guzzleClientMock = $this->getGuzzleClientMock();
        $this->setGuzzleExpectationsFor('DELETE', $guzzleClientMock);

        $repositoryHelperMock = $this->getMockBuilder('EzSystems\RecommendationBundle\Repository\RepositoryHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $repositoryHelperMock
            ->expects($this->at(0))
            ->method('loadLocation')
            ->with($this->equalTo(self::LOCATION_ID))
            ->willReturn(new Location([
                'id' => 5,
                'path' => ['1', '5'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID]),
            ]));

        $repositoryHelperMock
            ->expects($this->at(1))
            ->method('loadLocationChildren')
            ->willReturn(new LocationList([
                'totalCount' => 0,
                'locations' => [],
            ]));

        $repositoryHelperMock
            ->expects($this->at(2))
            ->method('loadContent')
            ->with($this->equalTo(self::CONTENT_ID))
            ->willReturn(new Content([
                'versionInfo' => new VersionInfo([
                    'contentInfo' => new ContentInfo([
                        'id' => self::CONTENT_ID,
                        'contentTypeId' => self::CONTENT_TYPE_ID,
                    ]),
                ]),
                'internalFields' => [],
            ]));

        $repositoryHelperMock
            ->expects($this->at(3))
            ->method('loadContentType')
            ->with($this->equalTo(self::CONTENT_TYPE_ID))
            ->willReturn(new ContentType(['fieldDefinitions' => [], 'identifier' => self::CONTENT_TYPE_ID]));

        $repositoryHelperMock
            ->expects($this->at(4))
            ->method('loadLocations')
            ->with($this->equalTo(new ContentInfo([
                'id' => self::CONTENT_ID,
                'contentTypeId' => self::CONTENT_TYPE_ID,
            ])))
            ->willReturn([
                new Location(['path' => ['1', '5'], 'hidden' => true]),
                new Location(['path' => ['1', '5', '8'], 'hidden' => true]),
                new Location(['path' => ['1', '5', '9'], 'hidden' => true]),
            ]);

        $repositoryHelperMock
            ->expects($this->at(5))
            ->method('loadVersionInfo')
            ->with(new ContentInfo(['id' => self::CONTENT_ID, 'contentTypeId' => self::CONTENT_TYPE_ID]))
            ->will($this->returnValue(new VersionInfo(['languageCodes' => ['eng-GB']])));

        /* Use Case */
        $notifier = new YooChooseNotifier(
            $guzzleClientMock,
            $repositoryHelperMock,
            [
                'customer-id' => self::CUSTOMER_ID,
                'license-key' => self::LICENSE_KEY,
                'api-endpoint' => self::API_ENDPOINT,
                'server-uri' => self::SERVER_URI,
            ],
            new NullLogger()
        );
        $notifier->setIncludedContentTypes([self::CONTENT_TYPE_ID]);
        $notifier->hideLocation(self::LOCATION_ID);
    }

    /**
     * Test for the hideLocation() method without children in location.
     */
    public function testHideLocationWithChildren()
    {
        $guzzleClientMock = $this->getGuzzleClientMock();
        $this->setGuzzleExpectationsFor('DELETE', $guzzleClientMock, self::CONTENT_ID + 1, 0);
        $this->setGuzzleExpectationsFor('DELETE', $guzzleClientMock, self::CONTENT_ID + 2, 1);
        $this->setGuzzleExpectationsFor('DELETE', $guzzleClientMock, self::CONTENT_ID, 2);

        $repositoryHelperMock = $this->getMockBuilder('EzSystems\RecommendationBundle\Repository\RepositoryHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $repositoryHelperMock
            ->expects($this->at(0))
            ->method('loadLocation')
            ->with($this->equalTo(self::LOCATION_ID))
            ->willReturn(new Location([
                'id' => 5,
                'path' => ['1', '5'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID]),
            ]));

        $repositoryHelperMock
            ->expects($this->at(1))
            ->method('loadLocationChildren')
            ->with($this->equalTo(new Location([
                'id' => 5,
                'path' => ['1', '5'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID]),
            ])))
            ->willReturn(new LocationList([
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
            ]));

        $repositoryHelperMock
            ->expects($this->at(2))
            ->method('loadLocation')
            ->with($this->equalTo(20))
            ->willReturn(new Location([
                'id' => 20,
                'path' => ['1', '5', '20'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID + 1]),
            ]));

        $repositoryHelperMock
            ->expects($this->at(3))
            ->method('loadLocationChildren')
            ->with($this->equalTo(new Location([
                'id' => 20,
                'path' => ['1', '5', '20'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID + 1]),
            ])))
            ->willReturn(new LocationList(['totalCount' => 0, 'locations' => []]));

        $repositoryHelperMock
            ->expects($this->at(4))
            ->method('loadContent')
            ->with($this->equalTo(self::CONTENT_ID + 1))
            ->willReturn(new Content([
                'versionInfo' => new VersionInfo([
                    'contentInfo' => new ContentInfo([
                        'id' => self::CONTENT_ID + 1,
                        'contentTypeId' => self::CONTENT_TYPE_ID,
                    ]),
                ]),
                'internalFields' => [],
            ]));

        $repositoryHelperMock
            ->expects($this->at(5))
            ->method('loadContentType')
            ->with($this->equalTo(self::CONTENT_TYPE_ID))
            ->willReturn(new ContentType(['fieldDefinitions' => [], 'identifier' => self::CONTENT_TYPE_ID]));

        $repositoryHelperMock
            ->expects($this->at(6))
            ->method('loadVersionInfo')
            ->with(new ContentInfo(['id' => self::CONTENT_ID + 1, 'contentTypeId' => self::CONTENT_TYPE_ID]))
            ->will($this->returnValue(new VersionInfo(['languageCodes' => ['eng-GB']])));

        $repositoryHelperMock
            ->expects($this->at(7))
            ->method('loadLocation')
            ->with($this->equalTo(30))
            ->willReturn(new Location([
                'id' => 30,
                'path' => ['1', '5', '30'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID + 2]),
            ]));

        $repositoryHelperMock
            ->expects($this->at(8))
            ->method('loadLocationChildren')
            ->with($this->equalTo(new Location([
                'id' => 30,
                'path' => ['1', '5', '30'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID + 2]),
            ])))
            ->willReturn(new LocationList(['totalCount' => 0, 'locations' => []]));

        $repositoryHelperMock
            ->expects($this->at(9))
            ->method('loadContent')
            ->with($this->equalTo(self::CONTENT_ID + 2))
            ->willReturn(new Content([
                'versionInfo' => new VersionInfo([
                    'contentInfo' => new ContentInfo([
                        'id' => self::CONTENT_ID + 2,
                        'contentTypeId' => self::CONTENT_TYPE_ID,
                    ]),
                ]),
                'internalFields' => [],
            ]));

        $repositoryHelperMock
            ->expects($this->at(10))
            ->method('loadContentType')
            ->with($this->equalTo(self::CONTENT_TYPE_ID))
            ->willReturn(new ContentType(['fieldDefinitions' => [], 'identifier' => self::CONTENT_TYPE_ID]));

        $repositoryHelperMock
            ->expects($this->at(11))
            ->method('loadVersionInfo')
            ->with(new ContentInfo(['id' => self::CONTENT_ID + 2, 'contentTypeId' => self::CONTENT_TYPE_ID]))
            ->will($this->returnValue(new VersionInfo(['languageCodes' => ['eng-GB']])));

        $repositoryHelperMock
            ->expects($this->at(12))
            ->method('loadContent')
            ->with($this->equalTo(self::CONTENT_ID))
            ->willReturn(new Content([
                'versionInfo' => new VersionInfo([
                    'contentInfo' => new ContentInfo([
                        'id' => self::CONTENT_ID,
                        'contentTypeId' => self::CONTENT_TYPE_ID,
                    ]),
                ]),
                'internalFields' => [],
            ]));

        $repositoryHelperMock
            ->expects($this->at(13))
            ->method('loadContentType')
            ->with($this->equalTo(self::CONTENT_TYPE_ID))
            ->willReturn(new ContentType(['fieldDefinitions' => [], 'identifier' => self::CONTENT_TYPE_ID]));

        $repositoryHelperMock
            ->expects($this->at(14))
            ->method('loadLocations')
            ->with($this->equalTo(new ContentInfo([
                'id' => self::CONTENT_ID,
                'contentTypeId' => self::CONTENT_TYPE_ID,
            ])))
            ->willReturn([
                new Location(['path' => ['1', '5'], 'hidden' => true]),
                new Location(['path' => ['1', '5', '8'], 'hidden' => true]),
                new Location(['path' => ['1', '5', '9'], 'hidden' => true]),
            ]);

        $repositoryHelperMock
            ->expects($this->at(15))
            ->method('loadVersionInfo')
            ->with(new ContentInfo(['id' => self::CONTENT_ID, 'contentTypeId' => self::CONTENT_TYPE_ID]))
            ->will($this->returnValue(new VersionInfo(['languageCodes' => ['eng-GB']])));

        /* Use Case */
        $notifier = new YooChooseNotifier(
            $guzzleClientMock,
            $repositoryHelperMock,
            [
                'customer-id' => self::CUSTOMER_ID,
                'license-key' => self::LICENSE_KEY,
                'api-endpoint' => self::API_ENDPOINT,
                'server-uri' => self::SERVER_URI,
            ],
            new NullLogger()
        );
        $notifier->setIncludedContentTypes([self::CONTENT_TYPE_ID]);
        $notifier->hideLocation(self::LOCATION_ID);
    }

    /**
     * Test for the unhideLocation() method.
     */
    public function testUnhideLocation()
    {
        $guzzleClientMock = $this->getGuzzleClientMock();
        $this->setGuzzleExpectationsFor('UPDATE', $guzzleClientMock);

        $repositoryHelperMock = $this->getMockBuilder('EzSystems\RecommendationBundle\Repository\RepositoryHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $repositoryHelperMock
            ->expects($this->at(0))
            ->method('loadLocation')
            ->with($this->equalTo(self::LOCATION_ID))
            ->willReturn(new Location([
                'id' => 5,
                'path' => ['1', '5'],
                'hidden' => true,
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID]),
            ]));

        $repositoryHelperMock
            ->expects($this->at(1))
            ->method('loadLocationChildren')
            ->withAnyParameters()
            ->willReturn(new LocationList(['totalCount' => 0, 'locations' => []]));

        $repositoryHelperMock
            ->expects($this->at(2))
            ->method('loadContent')
            ->with($this->equalTo(self::CONTENT_ID))
            ->willReturn(new Content([
                'versionInfo' => new VersionInfo([
                    'contentInfo' => new ContentInfo([
                        'id' => self::CONTENT_ID,
                        'contentTypeId' => self::CONTENT_TYPE_ID,
                    ]),
                ]),
                'internalFields' => [],
            ]));

        $repositoryHelperMock
            ->expects($this->at(3))
            ->method('loadContentType')
            ->with($this->equalTo(self::CONTENT_TYPE_ID))
            ->willReturn(new ContentType(['fieldDefinitions' => [], 'identifier' => self::CONTENT_TYPE_ID]));

        $repositoryHelperMock
            ->expects($this->at(4))
            ->method('loadVersionInfo')
            ->with(new ContentInfo(['id' => self::CONTENT_ID, 'contentTypeId' => self::CONTENT_TYPE_ID]))
            ->will($this->returnValue(new VersionInfo(['languageCodes' => ['eng-GB']])));

        /* Use Case */
        $notifier = new YooChooseNotifier(
            $guzzleClientMock,
            $repositoryHelperMock,
            [
                'customer-id' => self::CUSTOMER_ID,
                'license-key' => self::LICENSE_KEY,
                'api-endpoint' => self::API_ENDPOINT,
                'server-uri' => self::SERVER_URI,
            ],
            new NullLogger()
        );
        $notifier->setIncludedContentTypes([self::CONTENT_TYPE_ID]);
        $notifier->unhideLocation(self::LOCATION_ID);
    }

    /**
     * Test for the unhideLocation() method without children in location.
     */
    public function testUnhideLocationWithChildren()
    {
        $guzzleClientMock = $this->getGuzzleClientMock();
        $this->setGuzzleExpectationsFor('UPDATE', $guzzleClientMock, self::CONTENT_ID + 1, 0);
        $this->setGuzzleExpectationsFor('UPDATE', $guzzleClientMock, self::CONTENT_ID + 2, 1);
        $this->setGuzzleExpectationsFor('UPDATE', $guzzleClientMock, self::CONTENT_ID, 2);

        $repositoryHelperMock = $this->getMockBuilder('EzSystems\RecommendationBundle\Repository\RepositoryHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $repositoryHelperMock
            ->expects($this->at(0))
            ->method('loadLocation')
            ->with($this->equalTo(self::LOCATION_ID))
            ->willReturn(new Location([
                'id' => 5,
                'path' => ['1', '5'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID]),
            ]));

        $repositoryHelperMock
            ->expects($this->at(1))
            ->method('loadLocationChildren')
            ->with($this->equalTo(new Location([
                'id' => 5,
                'path' => ['1', '5'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID]),
            ])))
            ->willReturn(new LocationList([
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
            ]));

        $repositoryHelperMock
            ->expects($this->at(2))
            ->method('loadLocation')
            ->with($this->equalTo(20))
            ->willReturn(new Location([
                'id' => 20,
                'path' => ['1', '5', '20'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID + 1]),
            ]));

        $repositoryHelperMock
            ->expects($this->at(3))
            ->method('loadLocationChildren')
            ->with($this->equalTo(new Location([
                'id' => 20,
                'path' => ['1', '5', '20'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID + 1]),
            ])))
            ->willReturn(new LocationList(['totalCount' => 0, 'locations' => []]));

        $repositoryHelperMock
            ->expects($this->at(4))
            ->method('loadContent')
            ->with($this->equalTo(self::CONTENT_ID + 1))
            ->willReturn(new Content([
                'versionInfo' => new VersionInfo([
                    'contentInfo' => new ContentInfo([
                        'id' => self::CONTENT_ID + 1,
                        'contentTypeId' => self::CONTENT_TYPE_ID,
                    ]),
                ]),
                'internalFields' => [],
            ]));

        $repositoryHelperMock
            ->expects($this->at(5))
            ->method('loadContentType')
            ->with($this->equalTo(self::CONTENT_TYPE_ID))
            ->willReturn(new ContentType(['fieldDefinitions' => [], 'identifier' => self::CONTENT_TYPE_ID]));

        $repositoryHelperMock
            ->expects($this->at(6))
            ->method('loadVersionInfo')
            ->with(new ContentInfo(['id' => self::CONTENT_ID + 1, 'contentTypeId' => self::CONTENT_TYPE_ID]))
            ->will($this->returnValue(new VersionInfo(['languageCodes' => ['eng-GB']])));

        $repositoryHelperMock
            ->expects($this->at(7))
            ->method('loadLocation')
            ->with($this->equalTo(30))
            ->willReturn(new Location([
                'id' => 30,
                'path' => ['1', '5', '30'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID + 2]),
            ]));

        $repositoryHelperMock
            ->expects($this->at(8))
            ->method('loadLocationChildren')
            ->with($this->equalTo(new Location([
                'id' => 30,
                'path' => ['1', '5', '30'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID + 2]),
            ])))
            ->willReturn(new LocationList(['totalCount' => 0, 'locations' => []]));

        $repositoryHelperMock
            ->expects($this->at(9))
            ->method('loadContent')
            ->with($this->equalTo(self::CONTENT_ID + 2))
            ->willReturn(new Content([
                'versionInfo' => new VersionInfo([
                    'contentInfo' => new ContentInfo([
                        'id' => self::CONTENT_ID + 2,
                        'contentTypeId' => self::CONTENT_TYPE_ID,
                    ]),
                ]),
                'internalFields' => [],
            ]));

        $repositoryHelperMock
            ->expects($this->at(10))
            ->method('loadContentType')
            ->with($this->equalTo(self::CONTENT_TYPE_ID))
            ->willReturn(new ContentType(['fieldDefinitions' => [], 'identifier' => self::CONTENT_TYPE_ID]));

        $repositoryHelperMock
            ->expects($this->at(11))
            ->method('loadVersionInfo')
            ->with(new ContentInfo(['id' => self::CONTENT_ID + 2, 'contentTypeId' => self::CONTENT_TYPE_ID]))
            ->will($this->returnValue(new VersionInfo(['languageCodes' => ['eng-GB']])));

        $repositoryHelperMock
            ->expects($this->at(12))
            ->method('loadContent')
            ->with($this->equalTo(self::CONTENT_ID))
            ->willReturn(new Content([
                'versionInfo' => new VersionInfo([
                    'contentInfo' => new ContentInfo([
                        'id' => self::CONTENT_ID,
                        'contentTypeId' => self::CONTENT_TYPE_ID,
                    ]),
                ]),
                'internalFields' => [],
            ]));

        $repositoryHelperMock
            ->expects($this->at(13))
            ->method('loadContentType')
            ->with($this->equalTo(self::CONTENT_TYPE_ID))
            ->willReturn(new ContentType(['fieldDefinitions' => [], 'identifier' => self::CONTENT_TYPE_ID]));

        $repositoryHelperMock
            ->expects($this->at(14))
            ->method('loadVersionInfo')
            ->with(new ContentInfo(['id' => self::CONTENT_ID, 'contentTypeId' => self::CONTENT_TYPE_ID]))
            ->will($this->returnValue(new VersionInfo(['languageCodes' => ['eng-GB']])));

        /* Use Case */
        $notifier = new YooChooseNotifier(
            $guzzleClientMock,
            $repositoryHelperMock,
            [
                'customer-id' => self::CUSTOMER_ID,
                'license-key' => self::LICENSE_KEY,
                'api-endpoint' => self::API_ENDPOINT,
                'server-uri' => self::SERVER_URI,
            ],
            new NullLogger()
        );
        $notifier->setIncludedContentTypes([self::CONTENT_TYPE_ID]);
        $notifier->unhideLocation(self::LOCATION_ID);
    }

    /**
     * @return \GuzzleHttp\ClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getGuzzleClientMock()
    {
        $guzzleClientMock = $this
            ->getMockBuilder('GuzzleHttp\ClientInterface')
            ->getMock();

        return $guzzleClientMock;
    }

    /**
     * Returns Guzzle expected response.
     *
     * @param string $action
     * @param mixed $contentId
     * @param int $contentTypeId
     * @param string $serverUri
     * @param int $customerId
     * @param string $licenseKey
     *
     * @return array
     */
    protected function getNotificationBody($action, $contentId, $contentTypeId, $serverUri, $customerId, $licenseKey)
    {
        return [
            'json' => [
                'transaction' => null,
                'events' => [
                    [
                        'action' => $action,
                        'uri' => sprintf('%s/api/ezp/v2/ez_recommendation/v1/content/%s?lang=%s', $serverUri, $contentId, 'eng-GB'),
                        'contentTypeId' => $contentTypeId,
                        'format' => 'EZ',
                        'itemId' => $contentId,
                        'lang' => 'eng-GB',
                    ],
                ],
            ],
            'auth' => [
                $customerId,
                $licenseKey,
            ],
        ];
    }

    /**
     * Returns the expected API endpoint for notifications.
     *
     * @param string $apiEndpoint
     * @param int $customerId
     *
     * @return string
     */
    protected function getExpectedEndpoint($apiEndpoint, $customerId)
    {
        return sprintf('%s/api/%d/items', $apiEndpoint, $customerId);
    }

    /**
     * @param string $operationType Operation type: UPDATE / DELETE
     * @param \GuzzleHttp\ClientInterface|\PHPUnit_Framework_MockObject_MockObject $guzzleClientMock
     * @param int $contentId
     * @param int $expectAtIndex
     */
    protected function setGuzzleExpectationsFor(
        $operationType,
        $guzzleClientMock,
        $contentId = self::CONTENT_ID,
        $expectAtIndex = 0
    ) {
        return $this->setGuzzleExpectations(
            $guzzleClientMock,
            strtoupper($operationType),
            $contentId,
            self::CONTENT_TYPE_ID,
            self::CUSTOMER_ID,
            self::SERVER_URI,
            self::LICENSE_KEY,
            self::API_ENDPOINT,
            $expectAtIndex
        );
    }

    /**
     * Sets Guzzle expectations.
     *
     * @param \GuzzleHttp\ClientInterface|\PHPUnit_Framework_MockObject_MockObject $guzzleClientMock
     * @param string $action
     * @param mixed $contentId
     * @param int $contentTypeId
     * @param int $customerId
     * @param string $serverUri
     * @param string $licenseKey
     * @param string $apiEndpoint
     * @param int $expectAtIndex
     */
    protected function setGuzzleExpectations(
        $guzzleClientMock,
        $action,
        $contentId,
        $contentTypeId,
        $customerId,
        $serverUri,
        $licenseKey,
        $apiEndpoint,
        $expectAtIndex = 0
    ) {
        if (method_exists($guzzleClientMock, 'post')) {
            $guzzleClientMock
                ->expects($this->at($expectAtIndex))
                ->method('post')
                ->with(
                    $this->equalTo($this->getExpectedEndpoint($apiEndpoint, $customerId)),
                    $this->equalTo($this->getNotificationBody(
                        $action, $contentId, $contentTypeId, $serverUri, $customerId, $licenseKey
                    ))
                )
                ->will($this->returnValue(new \GuzzleHttp\Message\Response(202)));
        } else {
            $guzzleClientMock
                ->expects($this->at($expectAtIndex))
                ->method('request')
                ->with(
                    'POST',
                    $this->equalTo($this->getExpectedEndpoint($apiEndpoint, $customerId)),
                    $this->equalTo($this->getNotificationBody(
                        $action, $contentId, $contentTypeId, $serverUri, $customerId, $licenseKey
                    ))
                )
                ->will($this->returnValue(new Response(200)));
        }
    }
}
