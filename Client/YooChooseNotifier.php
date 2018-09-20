<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Client;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\Repository;
use GuzzleHttp\ClientInterface as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A recommendation client that sends notifications to a recommendation service.
 */
class YooChooseNotifier implements RecommendationClient
{
    const ACTION_UPDATE = 'UPDATE';
    const ACTION_DELETE = 'DELETE';

    /** @var array */
    protected $options;

    /** @var \GuzzleHttp\ClientInterface */
    private $guzzle;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var \eZ\Publish\API\Repository\Repository */
    private $repository;

    /** @var \eZ\Publish\API\Repository\ContentService */
    private $contentService;

    /** @var \eZ\Publish\API\Repository\LocationService */
    private $locationService;

    /** @var \eZ\Publish\API\Repository\ContentTypeService */
    private $contentTypeService;

    /**
     * @param \GuzzleHttp\ClientInterface $guzzle
     * @param \eZ\Publish\API\Repository\Repository $repository
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \eZ\Publish\API\Repository\ContentTypeService $contentTypeService
     * @param array $options
     *     Keys (all required):
     *     - customer-id: the Recommendation Service customer ID, e.g. 12345
     *     - license-key: Recommendation Service license key, e.g. 1234-5678-9012-3456-7890
     *     - api-endpoint: Recommendation Service http api endpoint
     *     - server-uri: the site's REST API base URI (without the prefix), e.g. http://api.example.com
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        GuzzleClient $guzzle,
        Repository $repository,
        ContentService $contentService,
        LocationService $locationService,
        ContentTypeService $contentTypeService,
        array $options,
        LoggerInterface $logger
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
     * {@inheritdoc}
     */
    public function updateContent($contentId, $versionNo = null)
    {
        try {
            $content = $this->contentService->loadContent($contentId, null, $versionNo);

            if ($this->isContentTypeExcluded($content)) {
                return;
            }
        } catch (NotFoundException $e) {
            // this is most likely a internal draft, or otherwise invalid, ignoring
            return;
        }

        $this->logger->info(sprintf('Notifying Recommendation Service: updateContent(%s)', $content->id));

        $notifications = $this->generateNotifications(self::ACTION_UPDATE, $content, $versionNo);

        try {
            $this->notify($notifications);
        } catch (RequestException $e) {
            $this->logger->error(sprintf('Recommendation Service Post notification error for updateContent: %s', $e->getMessage()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteContent($contentId)
    {
        try {
            $content = $this->contentService->loadContent($contentId);

            if ($this->isContentTypeExcluded($content)) {
                return;
            }
        } catch (NotFoundException $e) {
            // this is most likely a internal draft, or otherwise invalid, ignoring
            return;
        }

        $this->logger->info(sprintf('Notifying Recommendation Service: deleteContent(%s)', $content->id));

        $notifications = $this->generateNotifications(self::ACTION_DELETE, $content);

        try {
            $this->notify($notifications);
        } catch (RequestException $e) {
            $this->logger->error(sprintf('Recommendation Service Post notification error for deleteContent: %s', $e->getMessage()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hideLocation($locationId, $isChild = false)
    {
        $location = $this->locationService->loadLocation($locationId);
        $children = $this->locationService->loadLocationChildren($location)->locations;

        foreach ($children as $child) {
            $this->hideLocation($child->id, true);
        }

        $content = $this->contentService->loadContent($location->contentId);

        if ($this->isContentTypeExcluded($content)) {
            return;
        }

        if (!$isChild) {
            // do not send the notification if one of the locations is still visible, to prevent deleting content
            $contentLocations = $this->locationService->loadLocations($content->contentInfo);
            foreach ($contentLocations as $contentLocation) {
                if (!$contentLocation->hidden) {
                    return;
                }
            }
        }

        $this->logger->info(sprintf('Notifying Recommendation Service: hide(%s)', $content->id));

        $notifications = $this->generateNotifications(self::ACTION_DELETE, $content);

        try {
            $this->notify($notifications);
        } catch (RequestException $e) {
            $this->logger->error(sprintf('Recommendation Service Post notification error for hideLocation: %s', $e->getMessage()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function unhideLocation($locationId)
    {
        $location = $this->locationService->loadLocation($locationId);
        $children = $this->locationService->loadLocationChildren($location)->locations;

        foreach ($children as $child) {
            $this->unhideLocation($child->id);
        }

        $content = $this->contentService->loadContent($location->contentId);

        if ($this->isContentTypeExcluded($content)) {
            return;
        }

        $this->logger->info(sprintf('Notifying Recommendation Service: unhide(%s)', $content->id));

        $notifications = $this->generateNotifications(self::ACTION_UPDATE, $content);

        try {
            $this->notify($notifications);
        } catch (RequestException $e) {
            $this->logger->error(sprintf('Recommendation Service Post notification error for unhideLocation: %s', $e->getMessage()));
        }
    }

    /**
     * @param string $action
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     * @param int|null $versionNo
     * @return array
     */
    private function generateNotifications($action, Content $content, $versionNo = null)
    {
        $notification = array();
        foreach ($this->getLanguageCodes($content, $versionNo) as $lang) {
            $notification[] = $this->getNotificationContent($action, $content, $lang);
        }

        return $notification;
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
     * Checks if content is excluded from supported content types.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     *
     * @return bool
     */
    private function isContentTypeExcluded(Content $content)
    {
        $contentType = $this->repository->sudo(function () use ($content) {
            $contentType = $this->contentTypeService->loadContentType($content->contentInfo->contentTypeId);

            return $contentType;
        });

        return !in_array($contentType->identifier, $this->options['included-content-types']);
    }

    /**
     * Gets languageCodes based on $content.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     * @param int|null $versionNo
     *
     * @return array
     */
    protected function getLanguageCodes(Content $content, $versionNo = null)
    {
        $version = $this->contentService->loadVersionInfo($content->contentInfo, $versionNo);

        return $version->languageCodes;
    }

    /**
     * Generates the REST URI of content $contentId.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     * @param string $lang
     *
     * @return string
     */
    protected function getContentUri(Content $content, $lang = null)
    {
        return sprintf(
            '%s/api/ezp/v2/ez_recommendation/v1/content/%s%s',
            // @todo normalize in configuration
            $this->options['server-uri'],
            $content->id,
            isset($lang) ? '?lang=' . $lang : ''
        );
    }

    /**
     * Returns the Recommendation Service notification endpoint.
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
     * Notifies the Recommendation Service API of one or more repository events.
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
        $this->logger->debug(sprintf('POST notification to Recommendation Service: %s', json_encode($events, true)));

        $data = array(
            'json' => array(
                'transaction' => null,
                'events' => $events,
            ),
            'auth' => array(
                $this->options['customer-id'],
                $this->options['license-key'],
            ),
        );

        if (method_exists($this->guzzle, 'post')) {
            $this->notifyGuzzle5($data);
        } else {
            $this->notifyGuzzle6($data);
        }
    }

    /**
     * Notifies the Recommendation Service API using Guzzle 5 (for PHP 5.4 support).
     *
     * @param array $data
     */
    private function notifyGuzzle5(array $data)
    {
        $response = $this->guzzle->post($this->getNotificationEndpoint(), $data);

        $this->logger->debug(sprintf('Got %s from Recommendation Service notification POST (guzzle v5)', $response->getStatusCode()));
    }

    /**
     * Notifies the Recommendation Service API using Guzzle 6 synchronously.
     *
     * @param array $data
     */
    private function notifyGuzzle6(array $data)
    {
        $response = $this->guzzle->request('POST', $this->getNotificationEndpoint(), $data);

        $this->logger->debug(sprintf('Got %s from Recommendation Service notification POST (guzzle v6)', $response->getStatusCode()));
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
}
