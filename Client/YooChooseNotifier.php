<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Client;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\Values\Content\Content;
use GuzzleHttp\ClientInterface as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use eZ\Publish\Core\SignalSlot\Repository;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;

/**
 * A recommendation client that sends notifications to a YooChoose server.
 */
class YooChooseNotifier implements RecommendationClient
{
    const ACTION_UPDATE = 'UPDATE';
    const ACTION_DELETE = 'DELETE';

    /** @var string */
    protected $options;

    /** @var \GuzzleHttp\ClientInterface */
    private $guzzle;

    /** @var \Psr\Log\LoggerInterface|null */
    private $logger;

    /** @var \eZ\Publish\Core\SignalSlot\Repository */
    private $repository;

    /** @var \eZ\Publish\API\Repository\ContentService */
    private $contentService;

    /** @var \eZ\Publish\API\Repository\LocationService */
    private $locationService;

    /** @var \eZ\Publish\API\Repository\ContentTypeService */
    private $contentTypeService;

    /**
     * Constructs a YooChooseNotifier Recommendation Client.
     *
     * @param \GuzzleHttp\ClientInterface $guzzle
     * @param \eZ\Publish\Core\SignalSlot\Repository $repository
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \eZ\Publish\API\Repository\ContentTypeService $contentTypeService
     * @param array $options
     *     Keys (all required):
     *     - customer-id: the YooChoose customer ID, e.g. 12345
     *     - license-key: YooChoose license key, e.g. 1234-5678-9012-3456-7890
     *     - api-endpoint: YooChoose http api endpoint
     *     - server-uri: the site's REST API base URI (without the prefix), e.g. http://api.example.com
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(
        GuzzleClient $guzzle,
        Repository $repository,
        ContentService $contentService,
        LocationService $locationService,
        ContentTypeService $contentTypeService,
        array $options,
        LoggerInterface $logger = null
    ) {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
        $this->guzzle = $guzzle;
        $this->repository = $repository;
        $this->contentService = $contentService;
        $this->locationService = $locationService;
        $this->contentTypeService = $contentTypeService;
        $this->logger = $logger;
    }

    /**
     * Sets `customer-id` option when service is created which allows to
     * inject parameter value according to siteaccess configuration.
     *
     * @param string $value
     */
    public function setCustomerId($value)
    {
        $this->options['customer-id'] = $value;
    }

    /**
     * Sets `license-key` option when service is created which allows to
     * inject parameter value according to siteaccess configuration.
     *
     * @param string $value
     */
    public function setLicenseKey($value)
    {
        $this->options['license-key'] = $value;
    }

    /**
     * Sets `server-uri` option when service is created which allows to
     * inject parameter value according to siteaccess configuration.
     *
     * @param string $value
     */
    public function setServerUri($value)
    {
        $this->options['server-uri'] = $value;
    }

    /**
     * Sets `included-content-types` option when service is created which allows to
     * inject parameter value according to siteaccess configuration.
     *
     * @param array $value
     */
    public function setIncludedContentTypes($value)
    {
        $this->options['included-content-types'] = $value;
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $options = array('customer-id', 'license-key', 'api-endpoint', 'server-uri');
        $resolver->setDefined($options);
        $resolver->setDefaults(
            array(
                'customer-id' => null,
                'license-key' => null,
                'server-uri' => null,
                'api-endpoint' => null,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function updateContent($contentId, $versionNo = null)
    {
        $content = $this->contentService->loadContent($contentId, null, $versionNo);

        try {
            if (!in_array($this->getContentTypeIdentifier($content), $this->options['included-content-types'])) {
                return;
            }
        } catch (NotFoundException $e) {
            // this is most likely a internal draft, or otherwise invalid, ignoring
            return;
        }

        $this->log(sprintf('Notifying YooChoose: updateContent(%s)', $content->id));

        $notification = array();
        foreach ($this->getLangs($content, $versionNo) as $lang) {
            $notification[] = $this->getNotificationContent(self::ACTION_UPDATE, $content, $lang);
        }

        try {
            $this->notify($notification);
        } catch (RequestException $e) {
            $this->log(sprintf('YooChoose Post notification error: %s', $e->getMessage()), 'error');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteContent($contentId)
    {
        $content = $this->contentService->loadContent($contentId);

        try {
            if (!in_array($this->getContentTypeIdentifier($content), $this->options['included-content-types'])) {
                return;
            }
        } catch (NotFoundException $e) {
            // this is most likely a internal draft, or otherwise invalid, ignoring
            return;
        }

        $this->log(sprintf('Notifying YooChoose: delete(%s)', $content->id));

        $notification = array();
        foreach ($this->getLangs($content) as $lang) {
            $notification[] = $this->getNotificationContent(self::ACTION_DELETE, $content, $lang);
        }

        try {
            $this->notify($notification);
        } catch (RequestException $e) {
            $this->log(sprintf('YooChoose Post notification error: %s', $e->getMessage()), 'error');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hideLocation($locationId, $isChild = false)
    {
        $location = $this->locationService->loadLocation($locationId);
        $content = $this->contentService->loadContent($location->contentId);

        $children = $this->locationService->loadLocationChildren($location)->locations;
        foreach ($children as $child) {
            $this->hideLocation($child->id, true);
        }

        if (!in_array($this->getContentTypeIdentifier($content), $this->options['included-content-types'])) {
            return;
        }

        if (!$isChild) {
            $contentLocations = $this->locationService->loadLocations($content->contentInfo);
            foreach ($contentLocations as $contentLocation) {
                if (!$contentLocation->hidden) {
                    return;
                }
            }
        }

        $this->log(sprintf('Notifying YooChoose: hide(%s)', $content->id));

        $notification = array();
        foreach ($this->getLangs($content) as $lang) {
            $notification[] = $this->getNotificationContent(self::ACTION_DELETE, $content, $lang);
        }

        try {
            $this->notify($notification);
        } catch (RequestException $e) {
            $this->log(sprintf('YooChoose Post notification error: %s', $e->getMessage()), 'error');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function unhideLocation($locationId)
    {
        $location = $this->locationService->loadLocation($locationId);
        $content = $this->contentService->loadContent($location->contentId);

        $children = $this->locationService->loadLocationChildren($location)->locations;
        foreach ($children as $child) {
            $this->unhideLocation($child->id);
        }

        if (!in_array($this->getContentTypeIdentifier($content), $this->options['included-content-types'])) {
            return;
        }

        $this->log(sprintf('Notifying YooChoose: unhide(%s)', $content->id));

        $notification = array();
        foreach ($this->getLangs($content) as $lang) {
            $notification[] = $this->getNotificationContent(self::ACTION_UPDATE, $content, $lang);
        }

        try {
            $this->notify($notification);
        } catch (RequestException $e) {
            $this->log(sprintf('YooChoose Post notification error: %s', $e->getMessage()), 'error');
        }
    }

    /**
     * @param string $action
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     * @param string|null $lang
     *
     * @return array
     */
    protected function getNotificationContent($action, Content $content, $lang = null)
    {
        $return = array(
            'action' => $action,
            'format' => 'EZ',
            'uri' => $this->getContentUri($content, $lang),
            'itemId' => $content->id,
            'contentTypeId' => $content->contentInfo->contentTypeId,
        );

        if (null !== $lang) {
            $return['lang'] = $lang;
        }

        return $return;
    }

    /**
     * Returns ContentType identifier based on $contentId.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     *
     * @return string
     */
    private function getContentTypeIdentifier(Content $content)
    {
        $contentType = $this->repository->sudo(function () use ($content) {
            $contentType = $this->contentTypeService->loadContentType($content->contentInfo->contentTypeId);

            return $contentType;
        });

        return $contentType->identifier;
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     * @param int|null $versionNo
     *
     * @return array
     */
    protected function getLangs($content, $versionNo = null)
    {
        $version = $this->contentService->loadVersionInfo($content->contentInfo, $versionNo);

        return $version->languageCodes;
    }

    /**
     * Generates the REST URI of content $contentId.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     * @param $lang
     *
     * @return string
     */
    protected function getContentUri(Content $content, $lang = null)
    {
        return sprintf(
            '%s/api/ezp/v2/ez_recommendation/v1/content/%s%s',
            $this->options['server-uri'],
            $content->id,
            isset($lang) ? '?lang=' . $lang : ''
        );
    }

    /**
     * Returns the YooChoose notification endpoint.
     *
     * @return string
     */
    private function getNotificationEndpoint()
    {
        return sprintf(
            '%s/api/%s/items',
            rtrim($this->options['api-endpoint'], '/'),
            $this->options['customer-id']
        );
    }

    /**
     * Notifies the YooChoose API of one or more repository events.
     *
     * A repository event is defined as an array with three keys:
     * - action: the event name (update, delete)
     * - uri: the event's target, as an absolute HTTP URI to the REST resource
     * - contentTypeId: currently processed ContentType ID
     *
     * @param array $events
     *
     * @throws \InvalidArgumentException If provided $events seems to be of wrong type
     * @throws \GuzzleHttp\Exception\RequestException if a request error occurs
     */
    protected function notify(array $events)
    {
        $this->log(sprintf('POST notification to YooChoose: %s', json_encode($events, true)), 'debug');

        if (method_exists($this->guzzle, 'post')) {
            $this->notifyGuzzle5($events);
        } else {
            $this->notifyGuzzle6($events);
        }
    }

    /**
     * Notifies the YooChoose API using Guzzle 5 (for PHP 5.4 support).
     *
     * @param array $events
     */
    private function notifyGuzzle5(array $events)
    {
        $response = $this->guzzle->post(
            $this->getNotificationEndpoint(),
            array(
                'json' => array(
                    'transaction' => null,
                    'events' => $events,
                ),
                'auth' => array(
                    $this->options['customer-id'],
                    $this->options['license-key'],
                ),
            )
        );

        $this->log(sprintf('Got %s from YooChoose notification POST', $response->getStatusCode()), 'debug');
    }

    /**
     * Notifies the YooChoose API using Guzzle 6 asynchronously.
     *
     * @param array $events
     */
    private function notifyGuzzle6(array $events)
    {
        $promise = $this->guzzle->requestAsync(
            'POST',
            $this->getNotificationEndpoint(),
            array(
                'json' => array(
                    'transaction' => null,
                    'events' => $events,
                ),
                'auth' => array(
                    $this->options['customer-id'],
                    $this->options['license-key'],
                ),
            )
        );

        $promise->wait(false);

        $promise->then(function (ResponseInterface $response) {
            if (isset($this->logger)) {
                $this->logger->debug(sprintf('Got asynchronously %s from YooChoose notification POST', $response->getStatusCode()));
            }
        });
    }

    /**
     * @param string $message
     * @param string $level
     */
    private function log($message, $level = 'info')
    {
        if (isset($this->logger) && method_exists($this->logger, $level)) {
            $this->logger->$level($message);
        }
    }
}
