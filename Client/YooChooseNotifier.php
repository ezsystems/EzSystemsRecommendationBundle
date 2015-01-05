<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributd with this source code.
 */
namespace EzSystems\RecommendationBundle\Client;

use GuzzleHttp\ClientInterface as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A recommendation client that sends notifications to a YooChoose server.
 */
class YooChooseNotifier implements RecommendationClient
{
    /** @var string */
    protected $options;

    /** @var \GuzzleHttp\ClientInterface */
    private $guzzle;

    /** @var \Psr\Log\LoggerInterface|null */
    private $logger;

    /**
     * Constructs a YooChooseNotifier Recommendation Client.
     *
     * @param \GuzzleHttp\ClientInterface $guzzle
     * @param array $options
     *     Keys (all required):
     *     - customer-id: the yoochoose customer ID, e.g. 12345
     *     - license-key: yoochoose license key, e.g. 1234-5678-9012-3456-7890
     *     - api-endpoint: yoochoose http api endpoint
     *     - server-uri: the site's REST API base URI (without the prefix), e.g. http://api.example.com
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(GuzzleClient $guzzle, array $options, LoggerInterface $logger = null)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
        $this->guzzle = $guzzle;
        $this->logger = $logger;
    }

    public function setCustomerId($value)
    {
        $this->options['customer-id'] = $value;
        $this->guzzle->setDefaultOption('auth', $this->options['customer-id'], $this->options['license-key']);
    }

    public function setLicenseKey($value)
    {
        $this->options['license-key'] = $value;
        $this->guzzle->setDefaultOption('auth', $this->options['customer-id'], $this->options['license-key']);
    }

    public function setServerUri($value)
    {
        $this->options['server-uri'] = $value;
    }

    public function updateContent($contentId)
    {
        if (isset($this->logger)) {
            $this->logger->info("Notifying YooChoose: updateContent($contentId)");
        }
        try {
            $this->notify(array( array( 'action' => 'update', 'uri' => $this->getContentUri($contentId) ) ));
        } catch (RequestException $e) {
            if (isset($this->logger)) {
                $this->logger->error("YooChoose Post notification error: ".$e->getMessage());
            }
        }
    }

    public function deleteContent($contentId)
    {
        if (isset($this->logger)) {
            $this->logger->info("Notifying YooChoose: delete($contentId)");
        }
        try {
            $this->notify(array( array( 'action' => 'delete', 'uri' => $this->getContentUri($contentId) ) ));
        } catch (RequestException $e) {
            if (isset($this->logger)) {
                $this->logger->error("YooChoose Post notification error: ".$e->getMessage());
            }
        }
    }

    /**
     * Generates the REST URI of content $contentId
     *
     * @param $contentId
     *
     * @return string
     */
    protected function getContentUri($contentId)
    {
        return sprintf(
            '%s/api/ezp/v2/content/objects/%s',
            // @todo normalize in configuration
            $this->options['server-uri'],
            $contentId
        );
    }

    /**
     * Notifies the YooChoose API of one or more repository events.
     *
     * A repository event is defined as an array with two keys:
     * - action: the event name (update, delete)
     * - uri: the event's target, as an absolute HTTP URI to the REST resource.
     *
     * @param array $events
     *
     * @throws \GuzzleHttp\Exception\RequestException if a request error occurs
     */
    protected function notify(array $events)
    {
        foreach ($events as $event) {
            if (array_keys($event) != array( 'action', 'uri' )) {
                throw new InvalidArgumentException('Invalid action keys');
            }
        }

        if (isset($this->logger)) {
            $this->logger->debug("POST notification to YooChoose:".json_encode($events, true));
        }

        $response = $this->guzzle->post(
            $this->getNotificationEndpoint(),
            array( 'json' => array( 'transaction' => null, 'events' => $events ) )
        );

        if (isset($this->logger)) {
            $this->logger->debug("Got ".$response->getStatusCode()." from YooChoose notification POST");
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $options = array( 'customer-id', 'license-key', 'api-endpoint', 'server-uri' );
        // Could use setDefined() with symfony ~2.6
        $resolver->setOptional($options);
        $resolver->setDefault('customer-id', null);
        $resolver->setDefault('license-key', null);
        $resolver->setDefault('server-uri', null);
        $resolver->setDefault('api-endpoint', null);
    }

    /**
     * Returns the yoochoose notification endpoint
     *
     * @return string
     */
    private function getNotificationEndpoint()
    {
        return sprintf(
            '%s/api/v4/publisher/ez/%s/notifications',
            rtrim($this->options['api-endpoint'], '/'),
            $this->options['customer-id']
        );
    }
}
