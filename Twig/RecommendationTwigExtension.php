<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace EzSystems\RecommendationBundle\Twig;

use eZ\Publish\API\Repository\Repository;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;

/**
 * Recommendation Twig helper which renders necessary snippet codes.
 */
class RecommendationTwigExtension extends \Twig_Extension
{
    protected $template, $repository, $options;

    public function __construct(DelegatingEngine $template, Repository $repository, array $options)
    {
        $this->template = $template;
        $this->repository = $repository;
        $this->options = $options;
    }

    public function getName()
    {
        return 'recommendation_extension';
    }

    public function getFunctions()
    {
        return array(
            'yc_show_recommendations' => new \Twig_Function_Method($this, 'showRecommendations', array(
                'is_safe' => array( 'html' )
            )),

            'yc_track_user' => new \Twig_Function_Method($this, 'trackUser', array(
                'is_safe' => array( 'html' )
            ))
        );
    }

    /**
     * Render user tracking snippet code
     *
     * @param int $contentId
     * @return string
     */
    public function trackUser($contentId)
    {
        $userId = $this->options[ 'userId' ];
        $customerId = $this->repository->getCurrentUser()->id;

        return $this->template->render(
            '@EzSystemsRecommendationBundle/Resources/public/views/track_user.html.twig',
            array(
                'contentId' => $contentId,
                'userId' => $userId,
                'customerId' => $customerId
            )
        );
    }

    /**
     * Render recommendations snippet code
     *
     * @param int $contentId
     * @param array $options
     * @return string
     * @throws \Exception if HandleBars template was not found
     */
    public function showRecommendations($contentId, $options = null)
    {
        if (empty($options[ 'scenario' ])) {
            $options[ 'scenario' ] = $this->options[ 'scenarioId' ];
        }

        if (empty($options[ 'limit' ])) {
            $options[ 'limit' ] = $this->options[ 'limit' ];
        }

        if (empty($options[ 'template' ])) {
            $options = $this->options['template'];
        }

        $templatePath = __DIR__ . '/../Resources/public/hbt/' . $options[ 'template' ];
        if (file_exists($templatePath))
            $template = file_get_contents($templatePath);
        else
            throw new \Exception(sprintf('Handlebars template `%s` not found', $templatePath));

        return $this->template->render(
            '@EzSystemsRecommendationBundle/Resources/public/views/recommendations.html.twig',
            array(
                'recommendationId' => uniqid(),
                'contentId' => $contentId,
                'scenarioId' => $options[ 'scenario' ],
                'limit' => $options[ 'limit' ],
                'template' => $template
            )
        );
    }
}
