<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace EzSystems\RecommendationBundle\Twig;

use eZ\Publish\API\Repository\Repository;
use Twig_Extension;
use Twig_SimpleFunction;
use Twig_Environment;
use Exception;

/**
 * Recommendation Twig helper which renders necessary snippet codes.
 */
class RecommendationTwigExtension extends Twig_Extension
{
    protected $repository, $options;

    public function __construct(Repository $repository, array $options)
    {
        $this->repository = $repository;
        $this->options = $options;
    }

    public function getName()
    {
        return 'ez_recommendation_extension';
    }

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
     * Return content type identifier based on $contentId
     *
     * @param $contentId int
     * @return string content identifier
     */
    public function getContentIdentifier($contentId)
    {
        $contentTypeService = $this->repository->getContentTypeService();
        $contentType = $contentTypeService->loadContentType($this->repository->getContentService()
            ->loadContent($contentId)->contentInfo->contentTypeId);
        return $contentType->identifier;
    }

    /**
     * Render user tracking snippet code.
     *
     * @param Twig_Environment $twigEnvironment
     * @param int $contentId
     * @return string
     */
    public function trackUser(Twig_Environment $twigEnvironment, $contentId)
    {
        // display only for specified content type's
        if (!in_array($this->getContentIdentifier($contentId), $this->options['includedContentTypes']))
        {
            return '';
        }

        $userId = $this->options['userId'];
        $customerId = $this->repository->getCurrentUser()->id;

        return $twigEnvironment->render(
            '@EzSystemsRecommendationBundle/Resources/public/views/track_user.html.twig',
            array(
                'contentId' => $contentId,
                'userId' => $userId,
                'customerId' => $customerId
            )
        );
    }

    /**
     * Render recommendations snippet code.
     *
     * @param Twig_Environment $twigEnvironment
     * @param int $contentId
     * @param array $options
     * @return string
     * @throws \Exception if HandleBars template was not found
     */
    public function showRecommendations(Twig_Environment $twigEnvironment, $contentId, $options = null)
    {
        if (empty($options['scenario'])) {
            $options['scenario'] = $this->options['scenarioId'];
        }

        if (empty($options['limit'])) {
            $options['limit'] = $this->options['limit'];
        }

        if (empty($options['template'])) {
            $options = $this->options['template'];
        }

        $templatePath = __DIR__.'/../Resources/public/hbt/'.$options['template'];
        if (file_exists($templatePath))
        {
            $template = file_get_contents($templatePath);
        }
        else
        {
            throw new Exception(sprintf('Handlebars template `%s` not found', $templatePath));
        }

        return $twigEnvironment->render(
            '@EzSystemsRecommendationBundle/Resources/public/views/recommendations.html.twig',
            array(
                'recommendationId' => uniqid(),
                'contentId' => $contentId,
                'scenarioId' => $options['scenario'],
                'limit' => $options['limit'],
                'template' => $template
            )
        );
    }
}
