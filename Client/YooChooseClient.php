<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Client;

use EzSystems\RecommendationBundle\Factory\YooChooseApiFactory;
use EzSystems\RecommendationBundle\Rest\Api\AbstractApi;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

/**
 * Class YooChooseClient.
 */
class YooChooseClient implements YooChooseClientInterface
{
    private const DEBUG_MESSAGE = 'YooChooseClientDebug: ';
    private const ERROR_MESSAGE = 'YooChooseClientError: ';
    private const MESSAGE_SEPARATOR = ' | ';

    /** @var \GuzzleHttp\ClientInterface */
    private $client;

    /** @var \EzSystems\RecommendationBundle\Factory\YooChooseApiFactory */
    private $yooChooseApiFactory;

    /** @var int */
    private $customerId;

    /** @var string */
    private $licenseKey;

    /** @var int|string */
    private $userIdentifier;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    public function __construct(
        ClientInterface $client,
        YooChooseApiFactory $apiFactory,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->yooChooseApiFactory = $apiFactory;
        $this->logger = $logger;
    }

    /**
     * @param int $customerId
     *
     * @return YooChooseClientInterface
     */
    public function setCustomerId(int $customerId): YooChooseClientInterface
    {
        $this->customerId = $customerId;

        return $this;
    }

    /** @return int|null */
    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    /**
     * @param string $licenseKey
     *
     * @return YooChooseClientInterface
     */
    public function setLicenseKey(string $licenseKey): YooChooseClientInterface
    {
        $this->licenseKey = $licenseKey;

        return $this;
    }

    /** @return string|null */
    public function getLicenseKey(): ?string
    {
        return $this->licenseKey;
    }

    /**
     * @param string $userIdentifier
     *
     * @return YooChooseClientInterface
     */
    public function setUserIdentifier(string $userIdentifier): YooChooseClientInterface
    {
        $this->userIdentifier = $userIdentifier;

        return $this;
    }

    /** @return string|null */
    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    /**
     * @param string $method
     * @param \Psr\Http\Message\UriInterface $uri
     * @param array $option
     *
     * @return \Psr\Http\Message\ResponseInterface|null
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendRequest(string $method, UriInterface $uri, array $option = []): ?ResponseInterface
    {
        try {
            if (!$this->hasCredentials()) {
                $this->logger->warning(self::ERROR_MESSAGE . 'YooChoose credentials are not set', []);

                return null;
            }

            $container = [];
            $history = Middleware::history($container);
            $stack = HandlerStack::create();
            $stack->push($history);

            $response = $this->getHttpClient()->request($method, $uri, array_merge($option, [
                'handler' => $stack,
            ]));

            foreach ($container as $transaction) {
                $this->logger->debug(self::DEBUG_MESSAGE . $this->getRequestLogMessage($transaction));
            }

            return $response;
        } catch (\Exception $exception) {
            $this->logger->error(
                sprintf(
                    self::ERROR_MESSAGE . 'Error while sending data: %s %s %s %s',
                    $exception->getMessage(), $exception->getCode(), $exception->getFile(), $exception->getLine()
                ));

            return null;
        }
    }

    /**
     * @param \Psr\Http\Message\UriInterface $uri
     *
     * @return string
     */
    public function getAbsoluteUri(UriInterface $uri): string
    {
        return $uri->getScheme() . '://' . $uri->getHost() . $uri->getPath() . '?' . $uri->getQuery();
    }

    /**
     * @param array $headers
     *
     * @return string
     */
    public function getHeadersAsString(array $headers): string
    {
        $headersAsString = '';

        foreach ($headers as $headerKey => $headerValue) {
            if (isset($headerValue[0])) {
                $headersAsString .= $headerKey . ': ' . $headerValue[0];
            }

            if (next($headers)) {
                $headersAsString .= self::MESSAGE_SEPARATOR;
            }
        }

        return $headersAsString;
    }

    /** @return \GuzzleHttp\ClientInterface */
    public function getHttpClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return \EzSystems\RecommendationBundle\Rest\Api\AbstractApi
     */
    public function __call($name, $arguments): AbstractApi
    {
        try {
            return $this->yooChooseApiFactory->buildApi($name, $this);
        } catch (\Exception $exception) {
            $this->logger->error(self::ERROR_MESSAGE . $exception->getMessage());
        }
    }

    /**
     * Checks if notifier has configuration.
     *
     * @return bool
     */
    private function hasCredentials(): bool
    {
        return !empty($this->getCustomerId()) && !empty($this->getLicenseKey());
    }

    /**
     * @param array $transaction
     *
     * @return string
     */
    private function getRequestLogMessage(array $transaction): string
    {
        $message = '';

        if (isset($transaction['request']) && $transaction['request'] instanceof RequestInterface) {
            $requestUri = $this->getAbsoluteUri($transaction['request']->getUri());
            $method = 'Method: ' . $transaction['request']->getMethod();
            $requestHeaders = $this->getHeadersAsString($transaction['request']->getheaders());

            $message .= 'RequestUri: ' . $requestUri . self::MESSAGE_SEPARATOR . $method . self::MESSAGE_SEPARATOR . $requestHeaders;
        }

        if (isset($transaction['response']) && $transaction['response'] instanceof ResponseInterface) {
            $responseHeaders = $this->getHeadersAsString($transaction['response']->getHeaders());

            $message .= self::MESSAGE_SEPARATOR . $responseHeaders;
        }

        return $message;
    }
}
