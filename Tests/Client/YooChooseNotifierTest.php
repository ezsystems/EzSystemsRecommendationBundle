<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Tests\Client;

use Guzzle\Http\Message\Response;
use PHPUnit_Framework_TestCase;
use GuzzleHttp\Promise\Promise;
use eZ\Publish\Core\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use EzSystems\RecommendationBundle\Client\YooChooseNotifier;

class YooChooseNotifierTest extends PHPUnit_Framework_TestCase
{
    const CUSTOMER_ID = '12345';

    const LICENSE_KEY = '1234-5678-9012-3456-7890';

    const SERVER_URI = 'http://example.com';

    const API_ENDPOINT = 'http://yoochoose.example.com';

    const CONTENT_TYPE_ID = 1;

    const CONTENT_ID = 31415;

    /** @var \EzSystems\RecommendationBundle\Client\YooChooseNotifier */
    protected $notifier;

    /** @var \GuzzleHttp\ClientInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $guzzleClientMock;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->guzzleClientMock = $this->getMockBuilder('GuzzleHttp\ClientInterface')->getMock();
        $this->notifier = new YooChooseNotifier(
            $this->guzzleClientMock,
            $this->getRepositoryServiceMock(self::CONTENT_TYPE_ID),
            $this->getContentServiceMock(self::CONTENT_TYPE_ID),
            $this->getMockBuilder('eZ\Publish\API\Repository\LocationService')->getMock(),
            $this->getMockBuilder('eZ\Publish\API\Repository\ContentTypeService')->getMock(),
            array(
                'customer-id' => self::CUSTOMER_ID,
                'license-key' => self::LICENSE_KEY,
                'api-endpoint' => self::API_ENDPOINT,
                'server-uri' => self::SERVER_URI,
            )
        );
        $this->notifier->setIncludedContentTypes(array(self::CONTENT_TYPE_ID));
    }

    public function testUpdateContent()
    {
        $this->setGuzzleExpectations(
            'update',
            self::CONTENT_ID,
            self::CONTENT_TYPE_ID,
            self::CUSTOMER_ID,
            self::SERVER_URI,
            self::LICENSE_KEY,
            self::API_ENDPOINT
        );
        $this->notifier->updateContent(self::CONTENT_ID);
    }

    public function testDeleteContent()
    {
        $this->setGuzzleExpectations(
            'delete',
            self::CONTENT_ID,
            self::CONTENT_TYPE_ID,
            self::CUSTOMER_ID,
            self::SERVER_URI,
            self::LICENSE_KEY,
            self::API_ENDPOINT
        );
        $this->notifier->deleteContent(self::CONTENT_ID);
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
        return array(
            'json' => array(
                'transaction' => null,
                'events' => array(
                    array(
                        'action' => $action,
                        'uri' => sprintf('%s/api/ezp/v2/content/objects/%s', $serverUri, $contentId),
                        'contentTypeId' => $contentTypeId,
                    ),
                ),
            ),
            'auth' => array(
                $customerId,
                $licenseKey,
            ),
        );
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
        return sprintf('%s/api/v4/publisher/ez/%d/notifications', $apiEndpoint, $customerId);
    }

    /**
     * Sets Guzzle expectations.
     *
     * @param string $action
     * @param mixed $contentId
     * @param int $contentTypeId
     * @param int $customerId
     * @param string $serverUri
     * @param string $licenseKey
     * @param string $apiEndpoint
     */
    protected function setGuzzleExpectations(
        $action,
        $contentId,
        $contentTypeId,
        $customerId,
        $serverUri,
        $licenseKey,
        $apiEndpoint
    ) {
        if (method_exists($this->guzzleClientMock, 'post')) {
            $this->guzzleClientMock
                ->expects($this->once())
                ->method('post')
                ->with(
                    $this->equalTo($this->getExpectedEndpoint($apiEndpoint, $customerId)),
                    $this->equalTo($this->getNotificationBody(
                        $action, $contentId, $contentTypeId, $serverUri, $customerId, $licenseKey
                    ))
                )
                ->will($this->returnValue(new Response(202)));
        } else {
            $this->guzzleClientMock
                ->expects($this->once())
                ->method('requestAsync')
                ->with(
                    'POST',
                    $this->equalTo($this->getExpectedEndpoint($apiEndpoint, $customerId)),
                    $this->equalTo($this->getNotificationBody(
                        $action, $contentId, $contentTypeId, $serverUri, $customerId, $licenseKey
                    ))
                )
                ->will($this->returnValue(new Promise()));
        }
    }

    /**
     * Returns ContentTypeService mock object.
     *
     * @param int $contentTypeId
     *
     * @return \eZ\Publish\API\Repository\ContentService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getContentServiceMock($contentTypeId)
    {
        $contentServiceMock = $this->getMockBuilder('eZ\Publish\API\Repository\ContentService')->getMock();

        $contentServiceMock
            ->expects($this->any())
            ->method('loadContentInfo')
            ->will($this->returnValue(new ContentInfo(array(
                'contentTypeId' => $contentTypeId,
            ))));

        return $contentServiceMock;
    }

    /**
     * Returns Repository mock object.
     *
     * @param int $identifier
     *
     * @return \eZ\Publish\Core\SignalSlot\Repository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRepositoryServiceMock($identifier)
    {
        $repositoryServiceMock = $this->getMockBuilder('eZ\Publish\Core\SignalSlot\Repository')->disableOriginalConstructor()->getMock();

        $repositoryServiceMock
            ->expects($this->any())
            ->method('getContentService')
            ->will($this->returnValue($this->getContentServiceMock($identifier)));

        $repositoryServiceMock
            ->expects($this->any())
            ->method('getContentTypeService')
            ->will($this->returnValue(null));

        $repositoryServiceMock
            ->expects($this->any())
            ->method('sudo')
            ->will($this->returnValue(new ContentType(array(
                'fieldDefinitions' => array(),
                'identifier' => $identifier,
            ))));

        return $repositoryServiceMock;
    }
}
