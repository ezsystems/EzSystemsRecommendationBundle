<?php
/**
 * This file is part of the EzSystemRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Controller;

use eZ\Publish\Core\REST\Common\Exceptions\NotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\HttpKernel\Kernel;

class ExportController extends Controller
{
    /** @var \Symfony\Component\HttpKernel\Kernel */
    private $kernel;

    /** @var bool */
    private $authenticationMethod;

    /** @var string */
    private $authenticationLogin;

    /** @var string */
    private $authenticationPassword;

    /**
     * @param \Symfony\Component\HttpKernel\Kernel $kernel
     * @param bool $authenticationMethod
     * @param bool $authenticationLogin
     * @param bool $authenticationPassword
     */
    public function __construct(Kernel $kernel, $authenticationMethod, $authenticationLogin, $authenticationPassword)
    {
        $this->kernel = $kernel;
        $this->authenticationMethod = $authenticationMethod;
        $this->authenticationLogin = $authenticationLogin;
        $this->authenticationPassword = $authenticationPassword;
    }

    /**
     * @param string $filePath
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    public function downloadAction($filePath, Request $request)
    {
        $response = new Response();

        if (!$this->authenticate($filePath, $request->server) || strstr($filePath, '.')) {
            return $response->setStatusCode(401);
        }

        $path = $this->kernel->getRootDir() . '/../web/var/export/';

        if (!file_exists($path . $filePath)) {
            throw new NotFoundException('File not found.');
        }

        $content = file_get_contents($path . $filePath);

        $response->headers->set('Content-Type', 'mime/type');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filePath);

        $response->setContent($content);

        return $response;
    }

    /**
     * @param string $filePath
     * @param \Symfony\Component\HttpFoundation\ServerBag $server
     *
     * @return bool
     */
    private function authenticate($filePath, ServerBag $server)
    {
        if ($this->authenticationMethod == 'none' || $this->authenticationMethod == 'user') {
            return true;
        }

        $user = $server->get('PHP_AUTH_USER');
        $pass = crypt($server->get('PHP_AUTH_PW'), md5($server->get('PHP_AUTH_PW')));

        $passFile = $this->kernel->getRootDir() . '/../web/var/export/'
            . substr($filePath, 0, strrpos($filePath, '/'))
            . '/.htpasswd';

        list($auth['user'], $auth['pass']) = explode(':', file_get_contents($passFile));

        return $user == $auth['user'] && $pass == $auth['pass'];
    }
}
