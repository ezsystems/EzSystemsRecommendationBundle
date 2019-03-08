<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Controller;

use EzSystems\RecommendationBundle\Rest\Values\RecommendationMetadata;
use EzSystems\RecommendationBundle\Service\RecommendationServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;

/**
 * Class RecommendationController.
 */
class RecommendationController
{
    private const DEFAULT_TEMPLATE = 'EzSystemsRecommendationBundle::recommendations.html.twig';

    /** @var \EzSystems\RecommendationBundle\Service\RecommendationServiceInterface */
    private $recommendationService;

    /** @var \Symfony\Bundle\TwigBundle\TwigEngine */
    private $templateEngine;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    public function __construct(
        RecommendationServiceInterface $recommendationService,
        EngineInterface $templateEngine,
        LoggerInterface $logger
    ) {
        $this->recommendationService = $recommendationService;
        $this->templateEngine = $templateEngine;
        $this->logger = $logger;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Reques $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Twig\Error\Error
     */
    public function showRecommendationsAction(Request $request): Response
    {
        $response = $this->recommendationService->getRecommendations($request->attributes);

        if (!$response) {
            return new Response();
        }

        $template = $this->getTemplate($request->get('template'));

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            $this->logger->log('RecommendationApi: StatusCode: ' . $response->getStatusCode() . ' Message: ' . $response->getReasonPhrase());
        }

        $recommendations = $response->getBody()->getContents();

        $response = new Response();
        $response->setPrivate();

        $this->recommendationService->sendDeliveryFeedback($request->get(RecommendationMetadata::OUTPUT_TYPE_ID));

        $recommendationItems = json_decode($recommendations, true);

        return $this->templateEngine->renderResponse($template, [
            'recommendations' => $this->recommendationService->getRecommendationItems($recommendationItems['recommendationItems']),
            'templateId' => uniqid(),
            ],
            $response
        );
    }

    /**
     * @param null|string $template
     *
     * @return string
     */
    private function getTemplate(string $template = null): string
    {
        return $this->templateEngine->exists($template) ? $template : self::DEFAULT_TEMPLATE;
    }
}
