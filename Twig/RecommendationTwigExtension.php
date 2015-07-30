<?php

/**
 * This file is part of the EzSystemsRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig_Extension;
use Twig_SimpleFunction;
use Twig_Environment;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\Core\SignalSlot\Repository;
use eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverter;
use EzSystems\RecommendationBundle\Exception\InvalidArgumentException;

/**
 * YooChoose recommender Twig extension.
 */
class RecommendationTwigExtension extends Twig_Extension
{
    /** @var \Symfony\Component\HttpFoundation\RequestStack */
    protected $requestStack;

    /** @var \eZ\Publish\Core\SignalSlot\Repository */
    protected $repository;

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

    /** @var \Symfony\Component\HttpFoundation\Session\Session */
    protected $session;

    /** @var array */
    protected $options = array();

    /**
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     * @param \eZ\Publish\Core\SignalSlot\Repository $repository
     * @param \eZ\Publish\API\Repository\ContentTypeService $contentTypeService
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverter $localeConverter
     * @param \Symfony\Component\Security\Core\Authorization\AuthorizationChecker
     * @param \Symfony\Component\HttpFoundation\Session\Session $session
     * @param array $options
     */
    public function __construct(
        RequestStack $requestStack,
        Repository $repository,
        ContentTypeService $contentTypeService,
        ContentService $contentService,
        LocationService $locationService,
        LocaleConverter $localeConverter,
        AuthorizationChecker $authorizationChecker,
        Session $session,
        array $options
    ) {
        $this->requestStack = $requestStack;
        $this->repository = $repository;
        $this->contentTypeService = $contentTypeService;
        $this->contentService = $contentService;
        $this->locationService = $locationService;
        $this->localeConverter = $localeConverter;
        $this->authorizationChecker = $authorizationChecker;
        $this->session = $session;
        $this->options = $options;
    }

    public function setIncludedContentTypes($value)
    {
        $this->options['includedContentTypes'] = $value;
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
                'needs_environment' => true
            )),

            new Twig_SimpleFunction('yc_track_user', array($this, 'trackUser'), array(
                'is_safe' => array('html'),
                'needs_environment' => true
            ))
        );
    }

    /**
     * Renders simple tracking snippet code.
     *
     * @param \Twig_Environment $twigEnvironment
     * @param int|mixed $contentId
     * @return string
     */
    public function trackUser(Twig_Environment $twigEnvironment, $contentId)
    {
        if (!in_array($this->getContentIdentifier($contentId), $this->options['includedContentTypes']))
        {
            return '';
        }

        return $twigEnvironment->render(
            '@EzSystemsRecommendationBundle/Resources/public/views/track_user.html.twig',
            array(
                'contentId' => $contentId,
                'contentTypeId' => $this->getContentTypeId($this->getContentIdentifier($contentId)),
                'language' => $this->getCurrentLanguage(),
                'userId' => $this->getCurrentUserId(),
                'customerId' => $this->options['customerId'],
                'consumeTimeout' => ($this->options['consumeTimeout'] * 1000),
                'trackingScriptUrl' => $this->options['trackingScriptUrl']
            )
        );
    }

    /**
     * Returns ContentType identifier based on $contentId.
     *
     * @param int|mixed $contentId
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
     * @throws \EzSystems\RecommendationBundle\Exception\InvalidArgumentException when template is not found
     * @throws \EzSystems\RecommendationBundle\Exception\InvalidArgumentException when attributes are missing
     * @return string
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
        if (!file_exists(sprintf('%s/../Resources/public/views/%s.html.twig', __DIR__, $template))) {
            throw new InvalidArgumentException(sprintf('Template with the name `%s.html.twig` was not found under the `EzSystemsRecommendationBundle` views location', $template));
        }

        if (empty($fields)) {
            throw new InvalidArgumentException('Missing recommendation fields, at least one field is required');
        }

        return $twigEnvironment->render(
            sprintf('@EzSystemsRecommendationBundle/Resources/public/views/%s.html.twig', $template),
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
                'categoryPath' => $this->getLocationPathString($contentId)
            )
        );
    }

    /**
     * Returns location path string based on $contentId.
     *
     * @param int|mixed $contentId
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
        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') |
            $this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->repository->getCurrentUser()->id;
        } else {
            return $this->session->get('yc-session-id');
        }
    }
}
