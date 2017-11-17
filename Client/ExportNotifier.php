<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;

/**
 * A recommendation client that sends notifications to a YooChoose server.
 */
class ExportNotifier
{
    /** @var LoggerInterface */
    private $logger;

    /** @var bool */
    private $debug;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param string $debug
     */
    public function __construct(
        LoggerInterface $logger,
        $debug
    ) {
        $this->logger = $logger;
        $this->debug = $debug;
    }

    /**
     * @param array $urls
     * @param array $options
     * @param array $securedDir
     *
     * @return \Psr\Http\Message\StreamInterface|string
     *
     * @throws \Exception
     */
    public function sendYCResponse(array $urls, $options, $securedDir)
    {
        $guzzle = new Client(array(
            'base_uri' => $options['webHook'],
        ));

        $events = array();

        foreach ($urls as $contentTypeId => $languages) {
            foreach ($languages as $lang => $urlList) {
                $event = array(
                    'action' => 'FULL',
                    'format' => 'EZ',
                    'contentTypeId' => $contentTypeId,
                    'lang' => $lang,
                    'uri' => $urlList,
                    'credentials' => !empty($securedDir) ? $securedDir : null,
                );

                $events[] = $event;

                $this->logger->debug(sprintf(
                    'Event for YC created: %s',
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

            $response = $guzzle->send(
                $req, [
                    'debug' => $this->debug,
                ]
            )->getBody();

            $this->logger->info(sprintf(
                'Request has been sent to YC with transaction: %s and %s events',
                $options['transaction'], count($events)
            ));
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                'Error while sending data to YC %s %s %s %s',
                $e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine()
            ));

            throw $e;
        }

        return $response;
    }
}
