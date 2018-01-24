<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Authentication;

use eZ\Publish\Core\REST\Common\Exceptions\NotFoundException;
use EzSystems\RecommendationBundle\Helper\FileSystem;
use Symfony\Component\HttpFoundation\RequestStack;

class ExportAuthenticator implements Authenticator
{
    /** @var \Symfony\Component\HttpFoundation\RequestStack */
    private $requestStack;

    /** @var \EzSystems\RecommendationBundle\Helper\FileSystem */
    private $fileSystem;

    /** @var bool */
    private $method;

    /** @var string */
    private $login;

    /** @var string */
    private $password;

    /**
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     * @param \EzSystems\RecommendationBundle\Helper\FileSystem $fileSystem
     * @param string $method
     * @param string $login
     * @param string $password
     */
    public function __construct(
        RequestStack $requestStack,
        FileSystem $fileSystem,
        $method,
        $login,
        $password
    ) {
        $this->requestStack = $requestStack;
        $this->fileSystem = $fileSystem;
        $this->method = $method;
        $this->login = $login;
        $this->password = $password;
    }

    /**
     * Returns current authentication method and credentials.
     *
     * @return array
     */
    public function getCredentials()
    {
        return array(
            'method' => $this->method,
            'login' => $this->login,
            'password' => $this->password,
        );
    }

    /**
     * @return bool
     */
    public function authenticate()
    {
        $server = $this->requestStack->getCurrentRequest()->server;

        if ($this->method == 'none') {
            return true;
        }

        if (!empty($this->login)
            && !empty($this->password)
            && $server->get('PHP_AUTH_USER') === $this->login
            && $server->get('PHP_AUTH_PW') === $this->password
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param string $filePath
     *
     * @return bool
     */
    public function authenticateByFile($filePath)
    {
        $server = $this->requestStack->getCurrentRequest()->server;

        $user = $server->get('PHP_AUTH_USER');
        $pass = crypt($server->get('PHP_AUTH_PW'), md5($server->get('PHP_AUTH_PW')));

        if (strstr($filePath, '.')) {
            return false;
        }

        $passFile = substr($filePath, 0, strrpos($filePath, '/')) . '/.htpasswd';

        try {
            $fileContent = $this->fileSystem->load($passFile);
            list($auth['user'], $auth['pass']) = explode(':', trim($fileContent));

            return $user == $auth['user'] && $pass == $auth['pass'];
        } catch (NotFoundException $e) {
            return false;
        }
    }
}
