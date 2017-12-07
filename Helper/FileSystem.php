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
     * Returns directory for export files or default directory if not exists.
     *
     * @return string
     */
    public function getDir()
    {
        return $this->exportDocumentRoot;
    }
}
