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

        list($repositoryServiceMock, $contentServiceMock) = $this
            ->getServicesWithExpectationsForContentModification();

        /* Use Case */
        $notifier = new YooChooseNotifier(
            $guzzleClientMock,
            $repositoryServiceMock,
            $contentServiceMock,
            $this->getLocationServiceMock(),
            $this->getContentTypeServiceMock(),
            [
                'customer-id' => self::CUSTOMER_ID,
                'license-key' => self::LICENSE_KEY,
                'api-endpoint' => self::API_ENDPOINT,
                'server-uri' => self::SERVER_URI,
            ]
        );
        $notifier->setIncludedContentTypes([self::CONTENT_TYPE_ID]);
        $result = $notifier->updateContent(self::CONTENT_ID);

        $this->assertTrue($result);
    }

    /**
     * Test for the deleteContent() method.
     */
    public function testDeleteContent()
    {
        $guzzleClientMock = $this->getGuzzleClientMock();
        $this->setGuzzleExpectationsFor('DELETE', $guzzleClientMock);

        list($repositoryServiceMock, $contentServiceMock) = $this
            ->getServicesWithExpectationsForContentModification();

        /* Use Case */
        $notifier = new YooChooseNotifier(
            $guzzleClientMock,
            $repositoryServiceMock,
            $contentServiceMock,
            $this->getLocationServiceMock(),
            $this->getContentTypeServiceMock(),
            [
                'customer-id' => self::CUSTOMER_ID,
                'license-key' => self::LICENSE_KEY,
                'api-endpoint' => self::API_ENDPOINT,
                'server-uri' => self::SERVER_URI,
            ]
        );
        $notifier->setIncludedContentTypes([self::CONTENT_TYPE_ID]);
        $result = $notifier->deleteContent(self::CONTENT_ID);

        $this->assertTrue($result);
    }

    /**
     * Test for the hideLocation() method without children in location.
     */
    public function testHideLocation()
    {
        $guzzleClientMock = $this->getGuzzleClientMock();
        $this->setGuzzleExpectationsFor('DELETE', $guzzleClientMock);

        list($repositoryServiceMock, $contentServiceMock) = $this
            ->getServicesWithExpectationsForContentModification();

        $locationServiceMock = $this->getLocationServiceMock();
        $locationServiceMock
            ->expects($this->once())
            ->method('loadLocation')
            ->with($this->equalTo(self::LOCATION_ID))
            ->willReturn(new Location([
                'path' => ['1', '5'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID]),
            ]));
        $locationServiceMock
            ->expects($this->once())
            ->method('loadLocationChildren')
            ->withAnyParameters()
            ->willReturn(new LocationList(['totalCount' => 0, 'locations' => []]));
        $locationServiceMock
            ->expects($this->once())
            ->method('loadLocations')
            ->with($this->equalTo(new ContentInfo(['id' => self::CONTENT_ID, 'contentTypeId' => self::CONTENT_TYPE_ID])))
            ->willReturn([
                new Location([
                    'path' => ['1', '5', '10'],
                    'hidden' => true,
                    'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID]),
                ]),
                new Location([
                    'path' => ['1', '5', '20'],
                    'hidden' => true,
                    'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID]),
                ]),
            ]);

        /* Use Case */
        $notifier = new YooChooseNotifier(
            $guzzleClientMock,
            $repositoryServiceMock,
            $contentServiceMock,
            $locationServiceMock,
            $this->getContentTypeServiceMock(),
            [
                'customer-id' => self::CUSTOMER_ID,
                'license-key' => self::LICENSE_KEY,
                'api-endpoint' => self::API_ENDPOINT,
                'server-uri' => self::SERVER_URI,
            ]
        );
        $notifier->setIncludedContentTypes([self::CONTENT_TYPE_ID]);
        $result = $notifier->hideLocation(self::LOCATION_ID);

        $this->assertTrue($result);
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

        $locationServiceMock = $this->getLocationServiceMock();
        $locationServiceMock
            ->expects($this->at(0))
            ->method('loadLocation')
            ->with($this->equalTo(self::LOCATION_ID))
            ->willReturn(new Location([
                'id' => 5,
                'path' => ['1', '5'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID]),
            ]));

        $locationServiceMock
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

        $locationServiceMock
            ->expects($this->at(2))
            ->method('loadLocation')
            ->with($this->equalTo(20))
            ->willReturn(new Location([
                'id' => 20,
                'path' => ['1', '5', '20'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID + 1]),
            ]));
        $locationServiceMock
            ->expects($this->at(3))
            ->method('loadLocationChildren')
            ->with($this->equalTo(new Location([
                'id' => 20,
                'path' => ['1', '5', '20'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID + 1]),
            ])))
            ->willReturn(new LocationList(['totalCount' => 0, 'locations' => []]));

        $locationServiceMock
            ->expects($this->at(4))
            ->method('loadLocation')
            ->with($this->equalTo(30))
            ->willReturn(new Location([
                'id' => 30,
                'path' => ['1', '5', '30'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID + 2]),
            ]));
        $locationServiceMock
            ->expects($this->at(5))
            ->method('loadLocationChildren')
            ->with($this->equalTo(new Location([
                'id' => 30,
                'path' => ['1', '5', '30'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID + 2]),
            ])))
            ->willReturn(new LocationList(['totalCount' => 0, 'locations' => []]));

        $locationServiceMock
            ->expects($this->at(6))
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

        $contentServiceMock = $this->getContentServiceMock();

        $contentServiceMock
            ->expects($this->at(0))
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
        $contentServiceMock
            ->expects($this->at(1))
            ->method('loadVersionInfo')
            ->with(new ContentInfo(['id' => self::CONTENT_ID + 1, 'contentTypeId' => self::CONTENT_TYPE_ID]))
            ->will($this->returnValue(new VersionInfo(['languageCodes' => ['eng-GB']])));

        $contentServiceMock
            ->expects($this->at(2))
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
        $contentServiceMock
            ->expects($this->at(3))
            ->method('loadVersionInfo')
            ->with(new ContentInfo(['id' => self::CONTENT_ID + 2, 'contentTypeId' => self::CONTENT_TYPE_ID]))
            ->will($this->returnValue(new VersionInfo(['languageCodes' => ['eng-GB']])));

        $contentServiceMock
            ->expects($this->at(4))
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
        $contentServiceMock
            ->expects($this->at(5))
            ->method('loadVersionInfo')
            ->with(new ContentInfo(['id' => self::CONTENT_ID, 'contentTypeId' => self::CONTENT_TYPE_ID]))
            ->will($this->returnValue(new VersionInfo(['languageCodes' => ['eng-GB']])));

        $repositoryServiceMock = $this->getRepositoryServiceMock();
        $repositoryServiceMock
            ->expects($this->any())
            ->method('sudo')
            ->will($this->returnValue(new ContentType(['fieldDefinitions' => [], 'identifier' => self::CONTENT_TYPE_ID])));

        /* Use Case */
        $notifier = new YooChooseNotifier(
            $guzzleClientMock,
            $repositoryServiceMock,
            $contentServiceMock,
            $locationServiceMock,
            $this->getContentTypeServiceMock(),
            [
                'customer-id' => self::CUSTOMER_ID,
                'license-key' => self::LICENSE_KEY,
                'api-endpoint' => self::API_ENDPOINT,
                'server-uri' => self::SERVER_URI,
            ]
        );
        $notifier->setIncludedContentTypes([self::CONTENT_TYPE_ID]);
        $result = $notifier->hideLocation(self::LOCATION_ID);

        $this->assertTrue($result);
    }

    /**
     * Test for the unhideLocation() method.
     */
    public function testUnhideLocation()
    {
        $guzzleClientMock = $this->getGuzzleClientMock();
        $this->setGuzzleExpectationsFor('UPDATE', $guzzleClientMock);

        list($repositoryServiceMock, $contentServiceMock) = $this
            ->getServicesWithExpectationsForContentModification();

        $locationServiceMock = $this->getLocationServiceMock();
        $locationServiceMock
            ->expects($this->once())
            ->method('loadLocation')
            ->with($this->equalTo(self::LOCATION_ID))
            ->willReturn(new Location([
                'path' => ['1', '5'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID]),
            ]));
        $locationServiceMock
            ->expects($this->once())
            ->method('loadLocationChildren')
            ->withAnyParameters()
            ->willReturn(new LocationList(['totalCount' => 0, 'locations' => []]));

        /* Use Case */
        $notifier = new YooChooseNotifier(
            $guzzleClientMock,
            $repositoryServiceMock,
            $contentServiceMock,
            $locationServiceMock,
            $this->getContentTypeServiceMock(),
            [
                'customer-id' => self::CUSTOMER_ID,
                'license-key' => self::LICENSE_KEY,
                'api-endpoint' => self::API_ENDPOINT,
                'server-uri' => self::SERVER_URI,
            ]
        );
        $notifier->setIncludedContentTypes([self::CONTENT_TYPE_ID]);
        $result = $notifier->unhideLocation(self::LOCATION_ID);

        $this->assertTrue($result);
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

        $locationServiceMock = $this->getLocationServiceMock();
        $locationServiceMock
            ->expects($this->at(0))
            ->method('loadLocation')
            ->with($this->equalTo(self::LOCATION_ID))
            ->willReturn(new Location([
                'id' => 5,
                'path' => ['1', '5'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID]),
            ]));

        $locationServiceMock
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

        $locationServiceMock
            ->expects($this->at(2))
            ->method('loadLocation')
            ->with($this->equalTo(20))
            ->willReturn(new Location([
                'id' => 20,
                'path' => ['1', '5', '20'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID + 1]),
            ]));
        $locationServiceMock
            ->expects($this->at(3))
            ->method('loadLocationChildren')
            ->with($this->equalTo(new Location([
                'id' => 20,
                'path' => ['1', '5', '20'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID + 1]),
            ])))
            ->willReturn(new LocationList(['totalCount' => 0, 'locations' => []]));

        $locationServiceMock
            ->expects($this->at(4))
            ->method('loadLocation')
            ->with($this->equalTo(30))
            ->willReturn(new Location([
                'id' => 30,
                'path' => ['1', '5', '30'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID + 2]),
            ]));
        $locationServiceMock
            ->expects($this->at(5))
            ->method('loadLocationChildren')
            ->with($this->equalTo(new Location([
                'id' => 30,
                'path' => ['1', '5', '30'],
                'contentInfo' => new ContentInfo(['id' => self::CONTENT_ID + 2]),
            ])))
            ->willReturn(new LocationList(['totalCount' => 0, 'locations' => []]));

        $contentServiceMock = $this->getContentServiceMock();

        $contentServiceMock
            ->expects($this->at(0))
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
        $contentServiceMock
            ->expects($this->at(1))
            ->method('loadVersionInfo')
            ->with(new ContentInfo(['id' => self::CONTENT_ID + 1, 'contentTypeId' => self::CONTENT_TYPE_ID]))
            ->will($this->returnValue(new VersionInfo(['languageCodes' => ['eng-GB']])));

        $contentServiceMock
            ->expects($this->at(2))
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
        $contentServiceMock
            ->expects($this->at(3))
            ->method('loadVersionInfo')
            ->with(new ContentInfo(['id' => self::CONTENT_ID + 2, 'contentTypeId' => self::CONTENT_TYPE_ID]))
            ->will($this->returnValue(new VersionInfo(['languageCodes' => ['eng-GB']])));

        $contentServiceMock
            ->expects($this->at(4))
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
        $contentServiceMock
            ->expects($this->at(5))
            ->method('loadVersionInfo')
            ->with(new ContentInfo(['id' => self::CONTENT_ID, 'contentTypeId' => self::CONTENT_TYPE_ID]))
            ->will($this->returnValue(new VersionInfo(['languageCodes' => ['eng-GB']])));

        $repositoryServiceMock = $this->getRepositoryServiceMock();
        $repositoryServiceMock
            ->expects($this->any())
            ->method('sudo')
            ->will($this->returnValue(new ContentType(['fieldDefinitions' => [], 'identifier' => self::CONTENT_TYPE_ID])));

        /* Use Case */
        $notifier = new YooChooseNotifier(
            $guzzleClientMock,
            $repositoryServiceMock,
            $contentServiceMock,
            $locationServiceMock,
            $this->getContentTypeServiceMock(),
            [
                'customer-id' => self::CUSTOMER_ID,
                'license-key' => self::LICENSE_KEY,
                'api-endpoint' => self::API_ENDPOINT,
                'server-uri' => self::SERVER_URI,
            ]
        );
        $notifier->setIncludedContentTypes([self::CONTENT_TYPE_ID]);
        $result = $notifier->unhideLocation(self::LOCATION_ID);

        $this->assertTrue($result);
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
     * Returns services with expectations for content modification.
     *
     * @return array
     */
    private function getServicesWithExpectationsForContentModification()
    {
        $repositoryServiceMock = $this->getRepositoryServiceMock();
        $repositoryServiceMock
            ->expects($this->once())
            ->method('sudo')
            ->will($this->returnValue(new ContentType([
                'fieldDefinitions' => [],
                'identifier' => self::CONTENT_TYPE_ID,
            ])));

        $contentServiceMock = $this->getContentServiceMock();
        $contentServiceMock
            ->expects($this->once())
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
        $contentServiceMock
            ->expects($this->once())
            ->method('loadVersionInfo')
            ->with(new ContentInfo([
                'id' => self::CONTENT_ID,
                'contentTypeId' => self::CONTENT_TYPE_ID,
            ]))
            ->will($this->returnValue(new VersionInfo(['languageCodes' => ['eng-GB']])));

        return array($repositoryServiceMock, $contentServiceMock);
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

    /**
     * Returns ContentService mock object.
     *
     * @return \eZ\Publish\API\Repository\ContentService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getContentServiceMock()
    {
        $contentServiceMock = $this
            ->getMockBuilder('eZ\Publish\API\Repository\ContentService')
            ->getMock();

        return $contentServiceMock;
    }

    /**
     * Returns LocationService mock object.
     *
     * @return \eZ\Publish\API\Repository\LocationService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getLocationServiceMock()
    {
        $locationServiceMock = $this
            ->getMockBuilder('eZ\Publish\API\Repository\LocationService')
            ->getMock();

        return $locationServiceMock;
    }

    /**
     * Returns ContentTypeService mock object.
     *
     * @return \eZ\Publish\API\Repository\ContentTypeService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getContentTypeServiceMock()
    {
        $contentTypeServiceMock = $this
            ->getMockBuilder('eZ\Publish\API\Repository\ContentTypeService')
            ->getMock();

        return $contentTypeServiceMock;
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
}
