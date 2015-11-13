<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\EventListener;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Creates backup of sessionId in case of sessionId change.
 */
class SessionBackup
{
    /** @var \Symfony\Component\HttpFoundation\Session\Session */
    protected $session;

    /**
     * Constructs onKernelRequest event listener.
     *
     * @param \Symfony\Component\HttpFoundation\Session\Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Creates a backup of current sessionId in case of sessionId change,
     * we need this value to identify user on YooChoose side.
     * Be aware that session is automatically destroyed when user logs off,
     * in this case the new sessionId will be set. This issue can be treated
     * as a later improvement as it's not required by YooChoose to work correctly.
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($this->session->get('yc-session-id', null) == null) {
            $this->session->set('yc-session-id', $this->session->getId());
        }
    }
}
