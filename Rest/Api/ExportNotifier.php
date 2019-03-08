<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Api;

use EzSystems\RecommendationBundle\Client\YooChooseClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;

/**
 * A recommendation client that sends notifications to a YooChoose server.
 */
class ExportNotifier
{
    const API_NAME = 'exporter';
    /** @var \EzSystems\RecommendationBundle\Client\YooChooseClientInterface */
    private $client;

    /** @var LoggerInterface */
    private $logger;

    /** @var bool */
    private $debug;

    /**
     * ExportNotifier constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger $logger
     * @param $debug
     */
    public function __construct(
        YooChooseClientInterface $client,
        LoggerInterface $logger,
        $debug
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->debug = $debug;
    }

    /**
     * @param array $urls
     * @param array $options
     * @param array $securedDirCredentials
     *
     * @return \Psr\Http\Message\StreamInterface|string
     *
     * @throws \Exception
     */
    public function sendRecommendationResponse(array $urls, $options, $securedDirCredentials)
    {
        $guzzle = new Client(array(
            'base_uri' => $options['webHook'],
        ));

        $events = array();

        foreach ($urls as $contentTypeId => $languages) {
            foreach ($languages as $lang => $contentTypeInfo) {
                $event = array(
                    'action' => 'FULL',
                    'format' => 'EZ',
                    'contentTypeId' => $contentTypeId,
                    'contentTypeName' => $contentTypeInfo['contentTypeName'],
                    'lang' => $lang,
                    'uri' => $contentTypeInfo['urlList'],
                    'credentials' => !empty($securedDirCredentials) ? $securedDirCredentials : null,
                );

                $events[] = $event;

                $this->logger->debug(sprintf(
                    'Event for eZ Recommendation server created: %s',
                    var_export($event, true)
                ));
            }
        }

        try {
            $req = new Request(
                'POST',
                '',
                array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . base64_encode($options['customerId'] . ':' . $options['licenseKey']),
                ),
                json_encode(array(
                    'transaction' => $options['transaction'],
                    'events' => $events,
                ))
            );

            $response = $this->client->getHttpClient()->send($req, [
                    'debug' => $this->debug,
                ]
            )->getBody();

            $this->logger->info(sprintf(
                'Request has been sent to recommendation server with transaction: %s and %s events',
                $options['transaction'], count($events)
            ));
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                'Error while sending data to eZ Recommendation server %s %s %s %s',
                $e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine()
            ));

            throw $e;
        }

        return $response;
    }
}
