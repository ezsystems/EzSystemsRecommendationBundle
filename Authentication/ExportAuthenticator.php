<?php

namespace EzSystems\RecommendationBundle\Authentication;

use Symfony\Component\HttpFoundation\RequestStack;

class ExportAuthenticator implements Authenticator
{
    /** @var \Symfony\Component\HttpFoundation\RequestStack */
    protected $requestStack;

    /** @var string */
    private $kernelRootDir;

    /** @var bool */
    private $method;

    /** @var string */
    private $login;

    /** @var string */
    private $password;

    /**
     * @param RequestStack $requestStack
     * @param string $kernelRootDir
     * @param bool $method
     * @param bool $login
     * @param bool $password
     */
    public function __construct(RequestStack $requestStack, $kernelRootDir, $method, $login, $password)
    {
        $this->requestStack = $requestStack;
        $this->kernelRootDir = $kernelRootDir;
        $this->method = $method;
        $this->login = $login;
        $this->password = $password;
    }

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

        /** TODO: implement user login using UserRepository */
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
     * @param $filePath
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

        $passFile = $this->kernelRootDir . '/../web/var/export/'
            . substr($filePath, 0, strrpos($filePath, '/'))
            . '/.htpasswd';

        if (!file_exists($passFile)) {
            return false;
        }

        list($auth['user'], $auth['pass']) = explode(':', file_get_contents($passFile));

        return $user == $auth['user'] && $pass == $auth['pass'];
    }
}
