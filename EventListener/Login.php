<?php

/**
 * This file is part of the EzSystemsRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\EventListener;

use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpFoundation\Session\Session;
use GuzzleHttp\ClientInterface as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Sends notification to YooChoose servers when user is logged in.
 */
class Login
{
    /** @var array */
    private $options = array();

    /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationChecker */
    private $authorizationChecker;

    /** @var \Symfony\Component\HttpFoundation\Session\Session */
    private $session;

    /** @var \GuzzleHttp\ClientInterface */
    private $guzzleClient;

    /** @var \Psr\Log\LoggerInterface|null */
    private $logger;

    /**
     * Constructs a Login event listener.
     *
     * @param \Symfony\Component\Security\Core\Authorization\AuthorizationChecker $authorizationChecker
     * @param \Symfony\Component\HttpFoundation\Session\Session $session
     * @param \GuzzleHttp\ClientInterface $guzzleClient
     * @param array $options
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        AuthorizationChecker $authorizationChecker,
        Session $session,
        GuzzleClient $guzzleClient,
        $options = array(),
        LoggerInterface $logger = null
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->session = $session;
        $this->guzzleClient = $guzzleClient;
        $this->options = $options;
        $this->logger = $logger;
    }

    public function setCustomerId($value)
    {
        $this->options['customerId'] = $value;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') || // user has just logged in
            $this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) { // user has logged in using remember_me cookie

            $notificationUri = sprintf($this->getNotificationEndpoint() . '%s/%s/%s',
                'login',
                $this->session->get('yc-session-id'),
                $event->getAuthenticationToken()->getUser()->getUsername()
            );

            if (isset($this->logger)) {
                $this->logger->debug(sprintf('Send login event notification to YooChoose: %s', $notificationUri));
            }

            try {
                $response = $this->guzzleClient->get($notificationUri);

                if (isset($this->logger)) {
                    $this->logger->debug(sprintf('Got %s from YooChoose login event notification', $response->getStatusCode()));
                }
            } catch (RequestException $e) {
                if (isset($this->logger)) {
                    $this->logger->error(sprintf('YooChoose login event notification error: %s', $e->getMessage()));
                }
            }
        }
    }

    /**
     * Returns notification API end-point.
     *
     * @return string
     */
    private function getNotificationEndpoint()
    {
        return sprintf(
            '%s/api/%s/',
            $this->options['trackingEndPoint'],
            $this->options['customerId']
        );
    }
}
