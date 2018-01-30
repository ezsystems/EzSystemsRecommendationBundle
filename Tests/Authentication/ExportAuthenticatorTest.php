<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Tests\Authentication;

use EzSystems\RecommendationBundle\Authentication\ExportAuthenticator;
use EzSystems\RecommendationBundle\Helper\FileSystem;
use Symfony\Component\Filesystem\Filesystem as BaseFileSystem;
use Symfony\Component\HttpFoundation\ParameterBag;
use PHPUnit_Framework_TestCase;

class ExportAuthenticatorTest extends PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\HttpFoundation\RequestStack */
    private $requestStack;

    /** @var \PHPUnit_Framework_MockObject_MockObject|\EzSystems\RecommendationBundle\Helper\FileSystem */
    private $fileSystem;

    public function setUp()
    {
        parent::setUp();

        $this->requestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')->getMock();
        $this->fileSystem = $this->getMockBuilder('EzSystems\RecommendationBundle\Helper\FileSystem')->disableOriginalConstructor()->getMock();
    }

    public function testGetCredentials()
    {
        $exportAuthenticator = new ExportAuthenticator(
            $this->requestStack,
            $this->fileSystem,
            'user',
            'login',
            'pass'
        );

        $result = $exportAuthenticator->getCredentials();

        $this->assertEquals(
            [
                'method' => 'user',
                'login' => 'login',
                'password' => 'pass',
            ],
            $result
        );
    }

    public function testAuthenticateWithMethodNone()
    {
        $return = new \stdClass();
        $return->server = new ParameterBag([
            'PHP_AUTH_USER' => 'login',
            'PHP_AUTH_PW' => 'password',
        ]);

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->withAnyParameters()
            ->willReturn($return)
        ;

        $exportAuthenticator = new ExportAuthenticator(
            $this->requestStack,
            $this->fileSystem,
            'none',
            'login',
            'pass'
        );

        $this->assertTrue($exportAuthenticator->authenticate());
    }

    public function testAuthenticateWithMethodUser()
    {
        $return = new \stdClass();
        $return->server = new ParameterBag([
            'PHP_AUTH_USER' => 'login',
            'PHP_AUTH_PW' => 'password',
        ]);

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->withAnyParameters()
            ->willReturn($return)
        ;

        $exportAuthenticator = new ExportAuthenticator(
            $this->requestStack,
            $this->fileSystem,
            'user',
            'login',
            'password'
        );

        $this->assertTrue($exportAuthenticator->authenticate());
    }

    public function testAuthenticateWithMethodUserAndWrongCredentials()
    {
        $return = new \stdClass();
        $return->server = new ParameterBag([
            'PHP_AUTH_USER' => 'wrong_login',
            'PHP_AUTH_PW' => 'wrong_password',
        ]);

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->withAnyParameters()
            ->willReturn($return)
        ;

        $exportAuthenticator = new ExportAuthenticator(
            $this->requestStack,
            $this->fileSystem,
            'user',
            'login',
            'password'
        );

        $this->assertFalse($exportAuthenticator->authenticate());
    }

    public function testAuthenticateByFile()
    {
        $return = new \stdClass();
        $return->server = new ParameterBag([
            'PHP_AUTH_USER' => 'login',
            'PHP_AUTH_PW' => 'password',
        ]);

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->withAnyParameters()
            ->willReturn($return)
        ;

        $this->fileSystem
            ->expects($this->once())
            ->method('load')
            ->withAnyParameters()
            ->willReturn('login:5fjgIzboD2FrE')
        ;

        $exportAuthenticator = new ExportAuthenticator(
            $this->requestStack,
            $this->fileSystem,
            'user',
            'login',
            'password'
        );

        $this->assertTrue($exportAuthenticator->authenticateByFile('file'));
    }

    public function testAuthenticateByFileWithWrongCredenrials()
    {
        $return = new \stdClass();
        $return->server = new ParameterBag([
            'PHP_AUTH_USER' => 'wrong_login',
            'PHP_AUTH_PW' => 'wrong_password',
        ]);

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->withAnyParameters()
            ->willReturn($return)
        ;

        $this->fileSystem
            ->expects($this->once())
            ->method('load')
            ->withAnyParameters()
            ->willReturn('login:5fjgIzboD2FrE')
        ;

        $exportAuthenticator = new ExportAuthenticator(
            $this->requestStack,
            $this->fileSystem,
            'user',
            'login',
            'password'
        );

        $this->assertFalse($exportAuthenticator->authenticateByFile('file'));
    }

    public function testAuthenticateByFileWithWrongFile()
    {
        $return = new \stdClass();
        $return->server = new ParameterBag([
            'PHP_AUTH_USER' => 'wrong_login',
            'PHP_AUTH_PW' => 'wrong_password',
        ]);

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->withAnyParameters()
            ->willReturn($return)
        ;

        $this->fileSystem
            ->expects($this->never())
            ->method('load')
            ->withAnyParameters()
        ;

        $exportAuthenticator = new ExportAuthenticator(
            $this->requestStack,
            $this->fileSystem,
            'user',
            'login',
            'password'
        );

        $this->assertFalse($exportAuthenticator->authenticateByFile('../file'));
    }

    public function testAuthenticateByFileWithRealFile()
    {
        $return = new \stdClass();
        $return->server = new ParameterBag([
            'PHP_AUTH_USER' => 'login',
            'PHP_AUTH_PW' => 'PassTest00123A',
        ]);

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->withAnyParameters()
            ->willReturn($return)
        ;

        $exportAuthenticator = new ExportAuthenticator(
            $this->requestStack,
            new FileSystem(
                new BaseFilesystem(),
                __DIR__ . '/../fixtures/directory/'
            ),
            'user',
            'login',
            'password'
        );

        $this->assertTrue($exportAuthenticator->authenticateByFile('export_directory/the_file'));
    }
}
