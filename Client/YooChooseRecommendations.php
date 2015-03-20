<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace EzSystems\RecommendationBundle\Client;

use GuzzleHttp\ClientInterface as GuzzleClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use EzSystems\RecommendationBundle\Values\RecommendationsCollection;
use EzSystems\RecommendationBundle\Values\Recommendation;
use Guzzle\Http\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

/**
 * A recommendation client that fetches recommendations from YooChoose server.
 */
class YooChooseRecommendations implements RecommendationRequestClient
{
    /** @var string */
    protected $options;

    /** @var \GuzzleHttp\ClientInterface */
    private $guzzle;

    /** @var \Psr\Log\LoggerInterface|null */
    private $logger;

    /**
     * Constructs a YooChooseRecommendations request Client.
     *
     * @param \GuzzleHttp\ClientInterface $guzzle
     * @param array $options
     *     Keys (all required):
     *     - customer-id: the YooChoose customer ID, e.g. 12345
     *     - license-key: YooChoose license key, e.g. 1234-5678-9012-3456-7890
     *     - api-endpoint: YooChoose request http api endpoint
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
        $this->guzzle->setDefaultOption('auth', array(
            $this->options['customer-id'],
            $this->options['license-key']
        ));
    }

    public function setLicenseKey($value)
    {
        $this->options['license-key'] = $value;
        $this->guzzle->setDefaultOption('auth', array(
            $this->options['customer-id'],
            $this->options['license-key']
        ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $options = array( 'customer-id', 'license-key', 'api-endpoint' );
        // Could use setDefined() with symfony ~2.6
        $resolver->setOptional($options);
        $resolver->setDefaults(
            array(
                'customer-id' => null,
                'license-key' => null,
                'api-endpoint' => null
            )
        );
    }

    /**
     * Returns the YooChoose recommendation endpoint
     *
     * @return string
     */
    private function getRecommendationEndpoint()
    {
        return sprintf(
            '%s/api/%s',
            rtrim($this->options['api-endpoint'], '/'),
            $this->options['customer-id']
        );
    }

    /**
     * Returns the YooChoose recommendation endpoint for specified $userId and $scenarioId
     *
     * @param int $userId
     * @param string $scenarioId
     * @param int $limit
     * @param string $format
     * @return string
     */
    private function getRecommendationUserEndpoint($userId, $scenarioId, $limit, $format = 'json')
    {
        return $this->getRecommendationEndpoint().sprintf('/%s/%s.%s?numrecs=%d', $userId, $scenarioId, $format, $limit);
    }

    /**
     * Returns $limit recommendations for a $locationId and a $userId based on a $scenarioId
     *
     * @param int $userId
     * @param string $scenarioId
     * @param int $locationId
     * @param int $limit
     * @return \EzSystems\RecommendationBundle\Values\YooChooseRecommendationsCollection
     */
    public function getRecommendations($userId, $scenarioId, $locationId, $limit)
    {
        $uri = $this->getRecommendationUserEndpoint($userId, $scenarioId, $limit);

        if (isset($this->logger)) {
            $this->logger->info(sprintf('Requesting YooChoose: fetching recommendations content (API call: %s)', $uri));
        }

        $recommendationsCollection = new RecommendationsCollection();

        try {
            $response = $this->guzzle->get($uri);
            $jsonResponse = $response->json();

            if (isset($this->logger)) {
                $this->logger->info(sprintf('YooChoose response: fetched %d recommendations (API call: %s)', count($jsonResponse[ 'recommendationResponseList' ]), $uri));
            }

            foreach ($jsonResponse[ 'recommendationResponseList' ] as $jsonData) {
                $recommendationsCollection->add(new Recommendation(
                    $jsonData[ 'itemId' ],
                    $jsonData[ 'itemType' ],
                    $jsonData[ 'relevance' ],
                    $jsonData[ 'reason' ]
                ));
            }
        } catch (RequestException $e) {
            if (isset($this->logger)) {
                $this->logger->error(sprintf('YooChoose request error: %s (API call: %s)', $e->getMessage(), $uri));
            }
        } catch (ClientException $e) {
            if (isset($this->logger)) {
                $this->logger->error(sprintf('YooChoose client response error: %s (API call: %s)', $e->getMessage(), $uri));
            }
        }

        return $recommendationsCollection;
    }
}
