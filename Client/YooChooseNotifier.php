<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributd with this source code.
 */
namespace EzSystems\RecommendationBundle\Client;

use GuzzleHttp\ClientInterface as GuzzleClient;
use InvalidArgumentException;
use RuntimeException;
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

    /**
     * Constructs a YooChooseNotifier Recommendation Client.
     *
     * @param \GuzzleHttp\ClientInterface $guzzle
     * @param array $options
     *        Keys (all required):
     *        - customer-id: the yoochoose customer ID, e.g. 12345
     *        - license-key: yoochoose license key, e.g. 1234-5678-9012-3456-7890
     *        - api-endpoint: yoochoose http api endpoint
     *        - server-uri: the site's REST API base URI (without the prefix), e.g. http://api.example.com
     */
    public function __construct(GuzzleClient $guzzle, array $options)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
        $this->guzzle = $guzzle;
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
        $this->notify(array( array( 'action' => 'update', 'uri' => $this->getContentUri($contentId) ) ));
    }

    public function deleteContent($contentId)
    {
        $this->notify(array( array( 'action' => 'delete', 'uri' => $this->getContentUri($contentId) ) ));
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
     * @throws \RuntimeException if the API request doesn't return the expected HTTP status code (202)
     */
    protected function notify(array $events)
    {
        foreach ($events as $event) {
            if (array_keys($event) != array( 'action', 'uri' )) {
                throw new InvalidArgumentException('Invalid action keys');
            }
        }

        $response = $this->guzzle->post(
            $this->getNotificationEndpoint(),
            array( 'json' => array( 'transaction' => null, 'events' => $events ) )
        );

        if ($response->getStatusCode() != 202) {
            throw new RuntimeException('Unexpected status code '.$response->getStatusCode());
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $options = array( 'customer-id', 'license-key', 'api-endpoint', 'server-uri' );
        $resolver->setDefined($options);
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
            '%s/api/v1/publisher/ez/%s/notifications',
            rtrim($this->options['api-endpoint'], '/'),
            $this->options['customer-id']
        );
    }
}
