<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Twig_Extension;
use Twig_SimpleFunction;
use Twig_Environment;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverter;
use EzSystems\RecommendationBundle\Exception\InvalidArgumentException;

/**
 * YooChoose recommender Twig extension.
 */
class RecommendationTwigExtension extends Twig_Extension
{
    /** @var \Symfony\Component\HttpFoundation\RequestStack */
    protected $requestStack;

    /** @var \eZ\Publish\API\Repository\ContentTypeService */
    protected $contentTypeService;

    /** @var \eZ\Publish\API\Repository\ContentService */
    protected $contentService;

    /** @var \eZ\Publish\API\Repository\LocationService */
    protected $locationService;

    /** @var \eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverter */
    protected $localeConverter;

    /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationChecker */
    protected $authorizationChecker;

    /** @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage */
    protected $tokenStorage;

    /** @var \Symfony\Component\HttpFoundation\Session\Session */
    protected $session;

    /** @var array */
    protected $options = array();

    /**
     * Constructs EzSystemsRecommendationBundle Twig extension.
     *
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     * @param \eZ\Publish\API\Repository\ContentTypeService $contentTypeService
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverter $localeConverter
     * @param \Symfony\Component\Security\Core\Authorization\AuthorizationChecker
     * @param \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage
     * @param \Symfony\Component\HttpFoundation\Session\Session $session
     * @param array $options
     */
    public function __construct(
        RequestStack $requestStack,
        ContentTypeService $contentTypeService,
        ContentService $contentService,
        LocationService $locationService,
        LocaleConverter $localeConverter,
        AuthorizationChecker $authorizationChecker,
        TokenStorage $tokenStorage,
        Session $session,
        array $options
    ) {
        $this->requestStack = $requestStack;
        $this->contentTypeService = $contentTypeService;
        $this->contentService = $contentService;
        $this->locationService = $locationService;
        $this->localeConverter = $localeConverter;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->session = $session;
        $this->options = $options;
    }

    /**
     * Sets `includedContentTypes` option when service is created which allows to
     * inject parameter value according to siteaccess configuration.
     *
     * @param array $value
     */
    public function setIncludedContentTypes($value)
    {
        $this->options['includedContentTypes'] = $value;
    }

    /**
     * Sets `customerId` option when service is created which allows to
     * inject parameter value according to siteaccess configuration.
     *
     * @param string $value
     */
    public function setCustomerId($value)
    {
        $this->options['customerId'] = $value;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string
     */
    public function getName()
    {
        return 'ez_recommendation_extension';
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction('yc_show_recommendations', array($this, 'showRecommendations'), array(
                'is_safe' => array('html'),
                'needs_environment' => true,
            )),

            new Twig_SimpleFunction('yc_track_user', array($this, 'trackUser'), array(
                'is_safe' => array('html'),
                'needs_environment' => true,
            )),
        );
    }

    /**
     * Renders simple tracking snippet code.
     *
     * @param \Twig_Environment $twigEnvironment
     * @param int|mixed $contentId
     *
     * @return string
     */
    public function trackUser(Twig_Environment $twigEnvironment, $contentId)
    {
        if (!in_array($this->getContentIdentifier($contentId), $this->options['includedContentTypes'])) {
            return '';
        }

        return $twigEnvironment->render(
            'EzSystemsRecommendationBundle::track_user.html.twig',
            array(
                'contentId' => $contentId,
                'contentTypeId' => $this->getContentTypeId($this->getContentIdentifier($contentId)),
                'language' => $this->getCurrentLanguage(),
                'userId' => $this->getCurrentUserId(),
                'customerId' => $this->options['customerId'],
                'consumeTimeout' => ($this->options['consumeTimeout'] * 1000),
                'trackingScriptUrl' => $this->options['trackingScriptUrl'],
            )
        );
    }

    /**
     * Returns ContentType identifier based on $contentId.
     *
     * @param int|mixed $contentId
     *
     * @return string
     */
    private function getContentIdentifier($contentId)
    {
        $contentType = $this->contentTypeService->loadContentType(
            $this->contentService
                ->loadContent($contentId)
                ->contentInfo
                ->contentTypeId
        );

        return $contentType->identifier;
    }

    /**
     * Returns ContentType ID based on $contentType name.
     *
     * @param string $contentType
     *
     * @return int
     */
    private function getContentTypeId($contentType)
    {
        return $this->contentTypeService->loadContentTypeByIdentifier($contentType)->id;
    }

    /**
     * Renders recommendations snippet code.
     *
     * @param \Twig_Environment $twigEnvironment
     * @param int|mixed $contentId
     * @param string $scenario
     * @param int $limit
     * @param string $contentType
     * @param string $template
     * @param array $fields
     *
     * @return string
     *
     * @throws \EzSystems\RecommendationBundle\Exception\InvalidArgumentException when attributes are missing
     */
    public function showRecommendations(
        Twig_Environment $twigEnvironment,
        $contentId,
        $scenario,
        $limit,
        $contentType,
        $template,
        array $fields
    ) {
        if (empty($fields)) {
            throw new InvalidArgumentException('Missing recommendation fields, at least one field is required');
        }

        return $twigEnvironment->render(
            sprintf('EzSystemsRecommendationBundle::%s.html.twig', $template),
            array(
                'contentId' => $contentId,
                'language' => $this->getCurrentLanguage(),
                'scenario' => $scenario,
                'limit' => $limit,
                'templateId' => uniqid(),
                'fields' => $fields,
                'endpointUrl' => $this->getEndPointUrl(),
                'feedbackUrl' => $this->getFeedbackUrl($this->getContentTypeId($contentType)),
                'contentType' => $this->getContentTypeId($this->getContentIdentifier($contentId)),
                'outputTypeId' => $this->getContentTypeId($contentType),
                'categoryPath' => $this->getLocationPathString($contentId),
            )
        );
    }

    /**
     * Returns location path string based on $contentId.
     *
     * @param int|mixed $contentId
     *
     * @return string
     */
    private function getLocationPathString($contentId)
    {
        $content = $this->contentService->loadContent($contentId);
        $location = $this->locationService->loadLocation($content->contentInfo->mainLocationId);

        return $location->pathString;
    }

    /**
     * Returns current language.
     *
     * @return string
     */
    private function getCurrentLanguage()
    {
        $request = $this->requestStack->getCurrentRequest();

        return $this->localeConverter->convertToEz($request->get('_locale'));
    }

    /**
     * Returns YooChoose recommender end-point address.
     *
     * @return string
     */
    private function getEndPointUrl()
    {
        return sprintf('%s/api/%d/%s/',
            $this->options['recommenderEndPoint'],
            $this->options['customerId'],
            $this->getCurrentUserId()
        );
    }

    /**
     * Returns YooChoose feedback end-point address used to report
     * that recommendations were successfully fetched and displayed.
     *
     * @param int $outputContentTypeId ContentType ID for which recommendations should be delivered
     *
     * @return string
     */
    private function getFeedbackUrl($outputContentTypeId)
    {
        return sprintf('%s/api/%d/rendered/%s/%d/',
            $this->options['trackingEndPoint'],
            $this->options['customerId'],
            $this->getCurrentUserId(),
            $outputContentTypeId
        );
    }

    /**
     * Returns logged-in userId or anonymous sessionId.
     *
     * @return int|string
     */
    private function getCurrentUserId()
    {
        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') || // user has just logged in
            $this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) { // user has logged in using remember_me cookie
            return $this->tokenStorage->getToken()->getUsername();
        } else {
            return $this->session->get('yc-session-id');
        }
    }
}
