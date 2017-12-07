<?php
/**
 * This file is part of the EzSystemRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Helper;

use eZ\Publish\Core\REST\Common\Exceptions\NotFoundException;
use Symfony\Component\Filesystem\Filesystem as BaseFilesystem;

class FileSystem
{
    /** @var \Symfony\Component\Filesystem\Filesystem */
    private $filesystem;

    /** @var string */
    private $exportDocumentRoot;

    /**
     * @param \Symfony\Component\Filesystem\Filesystem $filesystem
     * @param string $exportDocumentRoot
     */
    public function __construct(
        BaseFilesystem $filesystem,
        $exportDocumentRoot
    ) {
        $this->filesystem = $filesystem;
        $this->exportDocumentRoot = $exportDocumentRoot;
    }

    /**
     * Load the content from file.
     *
     * @param string $file
     *
     * @return bool|string
     *
     * @throws NotFoundException when file not found.
     */
    public function load($file)
    {
        $dir = $this->getDir();

        if (!$this->filesystem->exists($dir . $file)) {
            throw new NotFoundException('File not found.');
        }

        return file_get_contents($dir . $file);
    }

    /**
     * Saves the content to file.
     *
     * @param string $file
     * @param string $content
     */
    public function save($file, $content)
    {
        $this->filesystem->dumpFile($file, $content);
    }

    /**
     * Returns directory for export files or default directory if not exists.
     *
     * @return string
     */
    public function getDir()
    {
        return $this->exportDocumentRoot;
    }

    /**
     * Generates directory for export files.
     *
     * @return string
     */
    public function createChunkDir()
    {
        $directoryName = date('Y/m/d/H/i/', time());
        $dir = $this->getDir() . $directoryName;

        if (!$this->filesystem->exists($dir)) {
            $this->filesystem->mkdir($dir, 0755);
        }

        return $directoryName;
    }

    /**
     * Locks directory by creating lock file.
     */
    public function lock()
    {
        $dir = $this->getDir();

        $this->filesystem->touch($dir . '.lock');
    }

    /**
     * Unlock directory by deleting lock file.
     */
    public function unlock()
    {
        $dir = $this->getDir();

        if ($this->filesystem->exists($dir . '.lock')) {
            $this->filesystem->remove($dir . '.lock');
        }
    }

    /**
     * Securing the directory regarding the authentication method.
     *
     * @param string $chunkDir
     * @param array $credentials
     *
     * @return array
     */
    public function secureDir($chunkDir, $credentials)
    {
        $dir = $this->getDir() . $chunkDir;

        if ($credentials['method'] == 'none') {
            return array();
        } elseif ($credentials['method'] == 'user') {
            return array(
                'login' => $credentials['login'],
                'password' => $credentials['password'],
            );
        }

        $user = 'yc';
        $password = substr(md5(microtime()), 0, 10);

        $this->filesystem->dumpFile(
            $dir . '.htpasswd',
            sprintf('%s:%s', $user, crypt($password, md5($password)))
        );

        return array(
            'login' => $user,
            'password' => $password,
        );
    }
}
