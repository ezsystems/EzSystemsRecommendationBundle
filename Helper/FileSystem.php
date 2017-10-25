<?php
/**
 * This file is part of the EzSystemRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Helper;

use EzSystems\RecommendationBundle\Authentication\Authenticator;

class FileSystem
{
    /** @var \EzSystems\RecommendationBundle\Authentication\Authenticator */
    protected $authenticator;

    /**
     * FileSystem constructor.
     * @param \EzSystems\RecommendationBundle\Authentication\Authenticator $authenticator
     */
    public function __construct(
        Authenticator $authenticator
    ) {
        $this->authenticator = $authenticator;
    }

    /**
     * Generates directory for export files.
     *
     * @param string $path
     *
     * @return string
     */
    public function createChunkDir($path)
    {
        $directoryName = date('/Y/m/d/H/i/', time());

        $dir = $path . '/var/export' . $directoryName;
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        return $directoryName;
    }

    /**
     * @param string $dir
     *
     * @return array
     */
    public function secureDir($dir)
    {
        $credentials = $this->authenticator->getCredentials();

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

        file_put_contents($dir . '.htpasswd', sprintf('%s:%s', $user, crypt($password, md5($password))));

        return array(
            'login' => $user,
            'password' => $password,
        );
    }

    /**
     * Saves the content to file.
     *
     * @param $file
     * @param $content
     */
    public function save($file, $content)
    {
        file_put_contents($file, $content);
    }

    /**
     * Locks directory by creating lock file.
     *
     * @param string $path
     */
    public function lock($path)
    {
        touch($path . '/var/export/.lock');
    }

    /**
     * Unlock directory by deleting lock file.
     *
     * @param string $path
     */
    public function unlock($path)
    {
        if (file_exists($path . '/var/export/.lock')) {
            unlink($path . '/var/export/.lock');
        }
    }
}
