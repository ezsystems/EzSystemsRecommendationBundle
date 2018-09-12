<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\EventListener;

use eZ\Publish\API\Repository\UserService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
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

    /** @var \eZ\Publish\API\Repository\UserService */
    private $userService;

    /** @var \Psr\Log\LoggerInterface|null */
    private $logger;

    /**
     * Constructs a Login event listener.
     *
     * @param \Symfony\Component\Security\Core\Authorization\AuthorizationChecker $authorizationChecker
     * @param \Symfony\Component\HttpFoundation\Session\Session $session
     * @param \GuzzleHttp\ClientInterface $guzzleClient
     * @param array $options
     * @param \eZ\Publish\API\Repository\UserService $userService
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        AuthorizationChecker $authorizationChecker,
        Session $session,
        GuzzleClient $guzzleClient,
        $options = array(),
        UserService $userService,
        LoggerInterface $logger = null
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->session = $session;
        $this->guzzleClient = $guzzleClient;
        $this->options = $options;
        $this->userService = $userService;
        $this->logger = $logger;
    }

    /**
     * Sets `customerId` option when service is created which allows to
     * inject parameter value according to siteaccess configuration.
     *
     * @param string $value
     */
    public function setCustomerId($value)
    {
        $this->options['customerId'] = $value;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') // user has just logged in
            || !$this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED') // user has logged in using remember_me cookie
        ) {
            return;
        }

        if (!$event->getRequest()->cookies->has('yc-session-id')) {
            if (!$this->session->isStarted()) {
                $this->session->start();
            }
            $event->getRequest()->cookies->set('yc-session-id', $this->session->getId());
        }

        $notificationUri = sprintf($this->getNotificationEndpoint() . '%s/%s/%s',
            'login',
            $event->getRequest()->cookies->get('yc-session-id'),
            $this->getUser($event->getAuthenticationToken())
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

    /**
     * Returns current username or ApiUser id.
     *
     * @param TokenInterface $authenticationToken
     *
     * @return int|string
     */
    private function getUser(TokenInterface $authenticationToken)
    {
        $user = $authenticationToken->getUser();

        if (is_string($user)) {
            return $user;
        } elseif (method_exists($user, 'getAPIUser')) {
            return $user->getAPIUser()->id;
        }

        return $authenticationToken->getUsername();
    }
}
