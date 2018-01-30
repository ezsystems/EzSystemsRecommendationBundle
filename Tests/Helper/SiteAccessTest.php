<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Tests\Helper;

use eZ\Publish\Core\MVC\Symfony\SiteAccess as CurrentSiteAccess;
use EzSystems\RecommendationBundle\Helper\SiteAccess;
use PHPUnit_Framework_TestCase;

class SiteAccessTest extends PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|\eZ\Publish\Core\MVC\ConfigResolverInterface */
    private $configResolver;

    public function setUp()
    {
        parent::setUp();

        $this->configResolver = $this->getMockBuilder('eZ\Publish\Core\MVC\ConfigResolverInterface')->getMock();
    }

    public function testGetRootLocationBySiteAccessNameWithoutParameterSpecified()
    {
        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with($this->equalTo('content.tree_root.location_id'))
            ->willReturn(123)
        ;

        $siteAccess = new CurrentSiteAccess('foo', 'test');

        $siteAccessMock = new SiteAccess(
            $this->configResolver,
            $siteAccess,
            [],
            'default'
        );

        $result = $siteAccessMock->getRootLocationBySiteAccessName();

        $this->assertEquals(123, $result);
    }

    public function testGetRootLocationBySiteAccessName()
    {
        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with($this->equalTo('content.tree_root.location_id'), null, 'foo')
            ->willReturn(123)
        ;

        $siteAccess = new CurrentSiteAccess('foo', 'test');

        $siteAccessMock = new SiteAccess(
            $this->configResolver,
            $siteAccess,
            [],
            'default'
        );

        $result = $siteAccessMock->getRootLocationBySiteAccessName('foo');

        $this->assertEquals(123, $result);
    }

    public function testGetRootLocationsBySiteAccesses()
    {
        $siteAccesses = [
            'abc',
            'cde',
        ];

        $this->configResolver
            ->expects($this->at(0))
            ->method('getParameter')
            ->with($this->equalTo('content.tree_root.location_id'), null, 'abc')
            ->willReturn(1)
        ;

        $this->configResolver
            ->expects($this->at(1))
            ->method('getParameter')
            ->with($this->equalTo('content.tree_root.location_id'), null, 'cde')
            ->willReturn(2)
        ;

        $siteAccess = new CurrentSiteAccess('foo', 'test');

        $siteAccessMock = new SiteAccess(
            $this->configResolver,
            $siteAccess,
            [],
            'default'
        );

        $result = $siteAccessMock->getRootLocationsBySiteAccesses($siteAccesses);

        $this->assertEquals([1, 2], $result);
    }

    public function testGetLanguagesNoParameters()
    {
        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with($this->equalTo('languages'))
            ->willReturn(['eng-GB', 'fre-FR'])
        ;

        $siteAccess = new CurrentSiteAccess('foo', 'test');

        $siteAccessMock = new SiteAccess(
            $this->configResolver,
            $siteAccess,
            [],
            'default'
        );

        $result = $siteAccessMock->getLanguages();

        $this->assertEquals(['eng-GB', 'fre-FR'], $result);
    }

    public function testGetLanguagesWithSiteAccess()
    {
        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with($this->equalTo('languages'), null, 'foo')
            ->willReturn(['eng-GB', 'fre-FR'])
        ;

        $siteAccess = new CurrentSiteAccess('foo', 'test');

        $siteAccessMock = new SiteAccess(
            $this->configResolver,
            $siteAccess,
            [],
            'default'
        );

        $result = $siteAccessMock->getLanguages(null, 'foo');

        $this->assertEquals(['eng-GB', 'fre-FR'], $result);
    }

    public function testGetLanguagesByMandatorId()
    {
        $this->configResolver
            ->expects($this->once())
            ->method('getParameter')
            ->with($this->equalTo('languages'), null, 'foo')
            ->willReturn(['eng-GB', 'fre-FR'])
        ;

        $siteAccess = new CurrentSiteAccess('foo', 'test');

        $siteAccessConfig = [
            'default' => [
                'yoochoose' => [
                    'customer_id' => 1,
                ],
            ],
            'foo' => [
                'yoochoose' => [
                    'customer_id' => 123,
                ],
            ],
        ];

        $siteAccessMock = new SiteAccess(
            $this->configResolver,
            $siteAccess,
            $siteAccessConfig,
            'default'
        );

        $result = $siteAccessMock->getLanguages(123);

        // should return only one language: main language by matched siteAccess
        $this->assertEquals(['eng-GB'], $result);
    }

    public function testGetSiteAccessesByMandatorIdWithoutMandatorId()
    {
        $siteAccess = new CurrentSiteAccess('foo', 'test');

        $siteAccessMock = new SiteAccess(
            $this->configResolver,
            $siteAccess,
            [],
            'default'
        );

        $result = $siteAccessMock->getSiteAccessesByMandatorId();

        $this->assertEquals(['foo'], $result);
    }

    public function testGetSiteAccessesByMandatorId()
    {
        $siteAccess = new CurrentSiteAccess('foo', 'test');

        $siteAccessConfig = [
            'default' => [
                'yoochoose' => [
                    'customer_id' => 1,
                ],
            ],
            'foo' => [
                'yoochoose' => [
                    'customer_id' => 2,
                ],
            ],
            'bar' => [
                'yoochoose' => [
                    'customer_id' => 3,
                ],
            ],
        ];

        $siteAccessMock = new SiteAccess(
            $this->configResolver,
            $siteAccess,
            $siteAccessConfig,
            'default'
        );

        $result = $siteAccessMock->getSiteAccessesByMandatorId(1);

        $this->assertEquals(['default'], $result);
    }

    public function testGetSiteAccessesByMandatorIdWithChangedDefaultSiteAccess()
    {
        $siteAccess = new CurrentSiteAccess('foo', 'test');

        $siteAccessConfig = [
            'default' => [
                'yoochoose' => [
                    'customer_id' => 1,
                ],
            ],
            'foo' => [
                'yoochoose' => [
                    'customer_id' => 2,
                ],
            ],
            'bar' => [
                'yoochoose' => [
                    'customer_id' => 3,
                ],
            ],
        ];

        $siteAccessMock = new SiteAccess(
            $this->configResolver,
            $siteAccess,
            $siteAccessConfig,
            'foo'
        );

        $result = $siteAccessMock->getSiteAccessesByMandatorId(1);

        $this->assertEquals(['default'], $result);
    }

    public function testGetSiteAccessesByMandatorIdWithChangedDefaultSiteAccessDifferentMandatorId()
    {
        $siteAccess = new CurrentSiteAccess('foo', 'test');

        $siteAccessConfig = [
            'default' => [
                'yoochoose' => [
                    'customer_id' => 1,
                ],
            ],
            'foo' => [
                'yoochoose' => [
                    'customer_id' => 2,
                ],
            ],
            'bar' => [
                'yoochoose' => [
                    'customer_id' => 3,
                ],
            ],
        ];

        $siteAccessMock = new SiteAccess(
            $this->configResolver,
            $siteAccess,
            $siteAccessConfig,
            'foo'
        );

        $result = $siteAccessMock->getSiteAccessesByMandatorId(2);

        $this->assertEquals(['foo'], $result);
    }

    public function testGetSiteAccessesByMandatorIdWithMultipleConfig()
    {
        $siteAccess = new CurrentSiteAccess('foo', 'test');

        $siteAccessConfig = [
            'default' => [
                'yoochoose' => [
                    'customer_id' => 3,
                ],
            ],
            'foo' => [
                'yoochoose' => [
                    'customer_id' => 3,
                ],
            ],
            'bar' => [
                'yoochoose' => [
                    'customer_id' => 3,
                ],
            ],
        ];

        $siteAccessMock = new SiteAccess(
            $this->configResolver,
            $siteAccess,
            $siteAccessConfig,
            'default'
        );

        $result = $siteAccessMock->getSiteAccessesByMandatorId(3);

        $this->assertEquals(['default', 'foo', 'bar'], $result);
    }

    public function testGetSiteAccessesByMandatorIdWithChangedDefaultSiteAccessAndMultipleConfig()
    {
        $siteAccess = new CurrentSiteAccess('foo', 'test');

        $siteAccessConfig = [
            'default' => [
                'yoochoose' => [
                    'customer_id' => 3,
                ],
            ],
            'foo' => [
                'yoochoose' => [
                    'customer_id' => 3,
                ],
            ],
            'bar' => [
                'yoochoose' => [
                    'customer_id' => 3,
                ],
            ],
        ];

        $siteAccessMock = new SiteAccess(
            $this->configResolver,
            $siteAccess,
            $siteAccessConfig,
            'foo'
        );

        $result = $siteAccessMock->getSiteAccessesByMandatorId(3);

        $this->assertEquals(['default', 'foo', 'bar'], $result);
    }

    /**
     * @expectedException \eZ\Publish\Core\Base\Exceptions\NotFoundException
     * @expectedExceptionMessage Could not find 'configuration for eZ Recommendation' with identifier 'mandatorId: 1007'
     */
    public function testGetSiteAccessesByMandatorIdWithWrongMandatorId()
    {
        $siteAccess = new CurrentSiteAccess('foo', 'test');

        $siteAccessConfig = [
            'default' => [
                'yoochoose' => [
                    'customer_id' => 3,
                ],
            ],
            'foo' => [
                'yoochoose' => [
                    'customer_id' => 3,
                ],
            ],
        ];

        $siteAccessMock = new SiteAccess(
            $this->configResolver,
            $siteAccess,
            $siteAccessConfig
        );

        $result = $siteAccessMock->getSiteAccessesByMandatorId(1007);
    }

    public function testGetSiteAccesses()
    {
        $siteAccess = new CurrentSiteAccess('default', 'test');

        $siteAccessMock = new SiteAccess(
            $this->configResolver,
            $siteAccess,
            [],
            'foo'
        );

        $result = $siteAccessMock->getSiteAccesses();

        $this->assertEquals(['default'], $result);
    }

    public function testGetSiteAccessesWithMandatorId()
    {
        $siteAccess = new CurrentSiteAccess('default', 'test');

        $siteAccessConfig = [
            'foo' => [
                'yoochoose' => [
                    'customer_id' => 123,
                ],
            ],
        ];

        $siteAccessMock = new SiteAccess(
            $this->configResolver,
            $siteAccess,
            $siteAccessConfig,
            'foo'
        );

        $result = $siteAccessMock->getSiteAccesses(123);

        $this->assertEquals(['foo'], $result);
    }

    public function testGetSiteAccessesWithSiteAccess()
    {
        $siteAccess = new CurrentSiteAccess('default', 'test');

        $siteAccessMock = new SiteAccess(
            $this->configResolver,
            $siteAccess,
            [],
            'foo'
        );

        $result = $siteAccessMock->getSiteAccesses(null, 'foo');

        $this->assertEquals(['foo'], $result);
    }

    /**
     * @expectedException \eZ\Publish\Core\Base\Exceptions\NotFoundException
     * @expectedExceptionMessage Could not find 'configuration for eZ Recommendation' with identifier 'mandatorId: 123'
     */
    public function testGetSiteAccessesWithWrongMandatorId()
    {
        $siteAccess = new CurrentSiteAccess('default', 'test');

        $siteAccessMock = new SiteAccess(
            $this->configResolver,
            $siteAccess,
            [],
            'foo'
        );

        $result = $siteAccessMock->getSiteAccesses(123);
    }

    public function testGetRecommendationServiceCredentials()
    {
        $this->configResolver
            ->expects($this->at(0))
            ->method('getParameter')
            ->with('yoochoose.customer_id', 'ez_recommendation', null)
            ->willReturn('123')
        ;

        $this->configResolver
            ->expects($this->at(1))
            ->method('getParameter')
            ->with('yoochoose.license_key', 'ez_recommendation', null)
            ->willReturn('licence-key')
        ;

        $siteAccess = new CurrentSiteAccess('default', 'test');

        $siteAccessMock = new SiteAccess(
            $this->configResolver,
            $siteAccess,
            [],
            'default'
        );

        $result = $siteAccessMock->getRecommendationServiceCredentials();

        $this->assertEquals(['123', 'licence-key'], $result);
    }

    public function testGetRecommendationServiceCredentialsWithMandatorId()
    {
        $this->configResolver
            ->expects($this->at(0))
            ->method('getParameter')
            ->with('yoochoose.customer_id', 'ez_recommendation', null)
            ->willReturn('123')
        ;

        $this->configResolver
            ->expects($this->at(1))
            ->method('getParameter')
            ->with('yoochoose.license_key', 'ez_recommendation', null)
            ->willReturn('licence-key')
        ;

        $siteAccess = new CurrentSiteAccess('default', 'test');

        $siteAccessConfig = [
            'default' => [
                'yoochoose' => [
                    'customer_id' => 123,
                ],
            ],
        ];

        $siteAccessMock = new SiteAccess(
            $this->configResolver,
            $siteAccess,
            $siteAccessConfig,
            'default'
        );

        $result = $siteAccessMock->getRecommendationServiceCredentials(123);

        $this->assertEquals(['123', 'licence-key'], $result);
    }

    public function testGetRecommendationServiceCredentialsWithSiteAccess()
    {
        $this->configResolver
            ->expects($this->at(0))
            ->method('getParameter')
            ->with('yoochoose.customer_id', 'ez_recommendation', 'foo')
            ->willReturn('123')
        ;

        $this->configResolver
            ->expects($this->at(1))
            ->method('getParameter')
            ->with('yoochoose.license_key', 'ez_recommendation', 'foo')
            ->willReturn('licence-key')
        ;

        $siteAccess = new CurrentSiteAccess('default', 'test');

        $siteAccessMock = new SiteAccess(
            $this->configResolver,
            $siteAccess,
            [],
            'default'
        );

        $result = $siteAccessMock->getRecommendationServiceCredentials(null, 'foo');

        $this->assertEquals(['123', 'licence-key'], $result);
    }
}
