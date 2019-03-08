<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Tests\Helper;

use EzSystems\RecommendationBundle\Helper\FileSystem;
use PHPUnit\Framework\TestCase;

class FileSystemTest extends TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Filesystem\Filesystem */
    private $baseFileSystem;

    public function setUp()
    {
        parent::setUp();

        $this->baseFileSystem = $this->getMockBuilder('Symfony\Component\Filesystem\Filesystem')->getMock();
    }

    public function testLoad()
    {
        $this->baseFileSystem
            ->expects($this->once())
            ->method('exists')
            ->withAnyParameters()
            ->willReturn(true)
        ;

        $fileSystem = new FileSystem(
            $this->baseFileSystem,
            __DIR__ . '/../fixtures/'
        );

        $result = $fileSystem->load('testfile.txt');

        $this->assertContains('testfile.txt content', $result);
    }

    /**
     * @expectedException \eZ\Publish\Core\REST\Common\Exceptions\NotFoundException
     * @expectedExceptionMessage File not found.
     */
    public function testLoadUnexistingFile()
    {
        $this->baseFileSystem
            ->expects($this->once())
            ->method('exists')
            ->withAnyParameters()
            ->willReturn(false)
        ;

        $fileSystem = new FileSystem(
            $this->baseFileSystem,
            __DIR__ . '/../fixtures/'
        );

        $result = $fileSystem->load('unexisting_file.txt');
    }

    public function testSave()
    {
        $this->baseFileSystem
            ->expects($this->once())
            ->method('dumpFile')
            ->withAnyParameters()
            ->willReturn(true)
        ;

        $fileSystem = new FileSystem(
            $this->baseFileSystem,
            __DIR__ . '/../fixtures/'
        );

        $fileSystem->save('testfile.txt', 'test');
    }

    public function testGetDir()
    {
        $dir = 'directory/';

        $fileSystem = new FileSystem(
            $this->baseFileSystem,
            $dir
        );

        $result = $fileSystem->getDir();

        $this->assertEquals($dir, $result);
    }

    public function testCreateChunkDir()
    {
        $this->baseFileSystem
            ->expects($this->once())
            ->method('exists')
            ->withAnyParameters()
            ->willReturn(true)
        ;

        $fileSystem = new FileSystem(
            $this->baseFileSystem,
            __DIR__ . '/../fixtures/'
        );

        $result = $fileSystem->createChunkDir();

        $this->assertTrue(strlen($result) > 5);
    }

    public function testCreateChunkDirWithUnexistingDir()
    {
        $this->baseFileSystem
            ->expects($this->once())
            ->method('exists')
            ->withAnyParameters()
            ->willReturn(false)
        ;

        $this->baseFileSystem
            ->expects($this->once())
            ->method('mkdir')
            ->withAnyParameters()
            ->willReturn(true)
        ;

        $fileSystem = new FileSystem(
            $this->baseFileSystem,
            __DIR__ . '/../fixtures/'
        );

        $result = $fileSystem->createChunkDir();

        $this->assertTrue(strlen($result) > 5);
    }

    public function testLock()
    {
        $this->baseFileSystem
            ->expects($this->once())
            ->method('touch')
            ->withAnyParameters()
            ->willReturn(true)
        ;

        $fileSystem = new FileSystem(
            $this->baseFileSystem,
            __DIR__ . '/../fixtures/'
        );

        $fileSystem->lock();
    }

    public function testUnlock()
    {
        $this->baseFileSystem
            ->expects($this->once())
            ->method('exists')
            ->withAnyParameters()
            ->willReturn(true)
        ;

        $this->baseFileSystem
            ->expects($this->once())
            ->method('remove')
            ->withAnyParameters()
            ->willReturn(true)
        ;

        $fileSystem = new FileSystem(
            $this->baseFileSystem,
            __DIR__ . '/../fixtures/'
        );

        $result = $fileSystem->unlock();
    }

    public function testUnlockWithoutLockedFile()
    {
        $this->baseFileSystem
            ->expects($this->once())
            ->method('exists')
            ->withAnyParameters()
            ->willReturn(false)
        ;

        $fileSystem = new FileSystem(
            $this->baseFileSystem,
            __DIR__ . '/../fixtures/'
        );

        $result = $fileSystem->unlock();
    }

    public function testiIsLockedWithLockedFile()
    {
        $this->baseFileSystem
            ->expects($this->once())
            ->method('exists')
            ->withAnyParameters()
            ->willReturn(true)
        ;

        $fileSystem = new FileSystem(
            $this->baseFileSystem,
            __DIR__ . '/../fixtures/'
        );

        $this->assertTrue($fileSystem->isLocked());
    }

    public function testiIsLockedWithoutLockedFile()
    {
        $this->baseFileSystem
            ->expects($this->once())
            ->method('exists')
            ->withAnyParameters()
            ->willReturn(false)
        ;

        $fileSystem = new FileSystem(
            $this->baseFileSystem,
            __DIR__ . '/../fixtures/'
        );

        $this->assertFalse($fileSystem->isLocked());
    }

    public function testSecureDirWithMethodNone()
    {
        $fileSystem = new FileSystem(
            $this->baseFileSystem,
            __DIR__ . '/../fixtures/'
        );

        $result = $fileSystem->secureDir(
            'dir',
            [
                'method' => 'none',
            ]
        );

        $this->assertEquals([], $result);
    }

    public function testSecureDirWithMethodUser()
    {
        $fileSystem = new FileSystem(
            $this->baseFileSystem,
            __DIR__ . '/../fixtures/'
        );

        $result = $fileSystem->secureDir(
            'dir',
            [
                'method' => 'user',
                'login' => 'login',
                'password' => 'pass',
            ]
        );

        $this->assertEquals(
            [
                'login' => 'login',
                'password' => 'pass',
            ],
            $result
        );
    }

    public function testSecureDirWithMethodBasic()
    {
        $this->baseFileSystem
            ->expects($this->once())
            ->method('dumpFile')
            ->withAnyParameters()
            ->willReturn(true)
        ;

        $fileSystem = new FileSystem(
            $this->baseFileSystem,
            __DIR__ . '/../fixtures/'
        );

        $result = $fileSystem->secureDir(
            'dir',
            [
                'method' => 'basic',
                'login' => 'login',
                'password' => 'pass',
            ]
        );

        $this->assertEquals('yc', $result['login']);
        $this->assertTrue(strlen($result['password']) > 5);
    }
}
