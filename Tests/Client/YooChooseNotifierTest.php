<?php
/**
 * This file is part of the eZ Publish Kernel package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributd with this source code.
 */

namespace EzSystems\RecommendationBundle\Tests\Client;

use EzSystems\RecommendationBundle\Client\YooChooseNotifier;
use Guzzle\Http\Message\Response;
use GuzzleHttp\ClientInterface;
use PHPUnit_Framework_TestCase;

class YooChooseNotifierTest extends PHPUnit_Framework_TestCase
{
    /** @var \EzSystems\RecommendationBundle\Client\YooChooseNotifier */
    protected $notifier;

    /** @var \GuzzleHttp\ClientInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $guzzleClientMock;

    public function setUp()
    {
        $this->notifier = new YooChooseNotifier(
            array(
                'customer-id' => '12345',
                'license-key' => '1234-5678-9012-3456-7890',
                'api-endpoint' => 'http://yoochoose.example.com',
                'base-uri' => 'http://example.com'
            ),
            $this->guzzleClientMock = $this->getMock( 'GuzzleHttp\ClientInterface' )
        );
    }

    public function testUpdateContent()
    {
        $this->setGuzzleExpectations( 'update', 31415 );
        $this->notifier->updateContent( 31415 );
    }

    public function testDeleteContent()
    {
        $this->setGuzzleExpectations( 'delete', 31415 );
        $this->notifier->deleteContent( 31415 );
    }

    protected function getNotificationBody( $action, $contentId )
    {
        return array(
            'json' => array(
                'transaction' => null,
                'events' => array(
                    array(
                        'action' => $action,
                        'uri' => 'http://example.com/api/ezp/v2/content/objects/' . $contentId
                    )
                )
            )
        );
    }

    /**
     * Returns the expected API endpoint for notifications
     * @return string
     */
    protected function getExpectedEndpoint()
    {
        return 'http://yoochoose.example.com/api/v1/publisher/ez/12345/notifications';
    }

    protected function setGuzzleExpectations( $action, $contentId )
    {
        $this->guzzleClientMock
            ->expects( $this->once() )
            ->method( 'post' )
            ->with(
                $this->equalTo( $this->getExpectedEndpoint() ),
                $this->equalTo( $this->getNotificationBody( $action, $contentId ) )
            )
            ->will( $this->returnValue( new Response( 202 ) ) );
    }
}
