<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Helper;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class SessionHelper.
 */
class SessionHelper
{
    /** @var \Symfony\Component\HttpFoundation\RequestStack */
    private $requestStack;

    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface */
    private $session;

    public function __construct(
        RequestStack $requestStack,
        SessionInterface $session
    ) {
        $this->requestStack = $requestStack;
        $this->session = $session;
    }

    /**
     * @param string $sessionKey
     *
     * @return mixed
     */
    public function getAnonymousSessionId(string $sessionKey)
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        $request = $this->requestStack->getMasterRequest();

        if (!$request->cookies->has($sessionKey)) {
            $request->cookies->set($sessionKey, $this->session->getId());
        }

        return $request->cookies->get($sessionKey);
    }
}
