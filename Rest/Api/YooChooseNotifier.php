<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Api;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\Repository;
use EzSystems\RecommendationBundle\Client\YooChooseClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A recommendation client that sends notifications to a recommendation service.
 */
class YooChooseNotifier extends AbstractApi
{
    const API_NAME = 'notifier';
    const ACTION_UPDATE = 'UPDATE';
    const ACTION_DELETE = 'DELETE';

    /** @var array */
    protected $options;

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
     * @param \EzSystems\RecommendationBundle\Client\YooChooseClientInterface
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
        YooChooseClientInterface $client,
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
        $this->repository = $repository;
        $this->contentService = $contentService;
        $this->locationService = $locationService;
        $this->contentTypeService = $contentTypeService;
        $this->logger = $logger;

        parent::__construct($client);
    }

    /** @return string */
    public function getRawEndPointUrl(): string
    {
        return '%s/api/%s/items';
    }

    /**
     * Sets `customer-id` option when service is created which allows to
     * inject parameter value according to siteaccess configuration.
     *
     * @param string $value
     */
    public function setCustomerId($value)
    {
        $this->client->setCustomerId($value);
    }

    /**
     * Sets `license-key` option when service is created which allows to
     * inject parameter value according to siteaccess configuration.
     *
     * @param string $value
     */
    public function setLicenseKey($value)
    {
        $this->client->setLicenseKey($value);
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

        $this->logger->debug(sprintf('YooChooseNotifier: Generating notification for updateContent(%s)', $content->id));

        $notifications = $this->generateNotifications(self::ACTION_UPDATE, $content, $versionNo);

        try {
            $this->notify($notifications);
        } catch (RequestException $e) {
            $this->logger->error(sprintf('YooChooseNotifier: notification error for updateContent: %s', $e->getMessage()));
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

        $this->logger->debug(sprintf('YooChooseNotifier: Generating notification for deleteContent(%s)', $content->id));

        $notifications = $this->generateNotifications(self::ACTION_DELETE, $content);

        try {
            $this->notify($notifications);
        } catch (RequestException $e) {
            $this->logger->error(sprintf('YooChooseNotifier: notification error for deleteContent: %s', $e->getMessage()));
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

        $this->logger->debug(sprintf('YooChooseNotifier: Generating notification for hide(%s)', $content->id));

        $notifications = $this->generateNotifications(self::ACTION_DELETE, $content);

        try {
            $this->notify($notifications);
        } catch (RequestException $e) {
            $this->logger->error(sprintf('YooChooseNotifier: notification error for hideLocation: %s', $e->getMessage()));
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

        $this->logger->debug(sprintf('YooChooseNotifier: Generating notification for unhide(%s)', $content->id));

        $notifications = $this->generateNotifications(self::ACTION_UPDATE, $content);

        try {
            $this->notify($notifications);
        } catch (RequestException $e) {
            $this->logger->error(sprintf('YooChooseNotifier: notification error for unhideLocation: %s', $e->getMessage()));
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
        $notification = [];
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
        $return = [
            'action' => $action,
            'format' => 'EZ',
            'uri' => $this->getContentUri($content, $lang),
            'itemId' => $content->id,
            'contentTypeId' => $content->contentInfo->contentTypeId,
        ];

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
            return $this->contentTypeService->loadContentType($content->contentInfo->contentTypeId);
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
        $customerId = $this->client->getCustomerId();

        $data = [
            'json' => [
                'transaction' => null,
                'events' => $events,
            ],
            'auth' => [
                $customerId,
                $this->client->getLicenseKey(),
            ],
        ];

        $endPoint = $this->buildEndPointUrl([
            rtrim($this->options['api-endpoint'], '/'),
            $customerId,
        ]);

        $this->client->sendRequest(Request::METHOD_POST, $endPoint, $data);
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $options = ['customer-id', 'license-key', 'api-endpoint', 'server-uri'];
        $resolver->setDefined($options);
        $resolver->setDefaults([
            'customer-id' => null,
            'license-key' => null,
            'server-uri' => null,
            'api-endpoint' => null,
        ]);
    }
}
