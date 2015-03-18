<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace EzSystems\RecommendationBundle\Twig;

use eZ\Publish\API\Repository\Repository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;

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
            'yc_show_recommendations' => new \Twig_Function_Method($this, 'yc_show_recommendations', array(
                'is_safe' => array( 'html' )
            )),

            'yc_track_user' => new \Twig_Function_Method($this, 'yc_track_user', array(
                'is_safe' => array( 'html' )
            ))
        );
    }

    public function yc_track_user($locationId)
    {
        $response = new Response();

        $userId = $this->options[ 'userId' ];
        $customerId = $this->repository->getCurrentUser()->id;

        return $this->template->render(
            '@EzSystemsRecommendationBundle/Resources/public/views/track_user.html.twig',
            array(
                'locationId' => $locationId,
                'userId' => $userId,
                'customerId' => $customerId
            ),
            $response
        );
    }

    public function yc_show_recommendations($locationId, $options = null)
    {
        $response = new Response();

        if (empty($options[ 'scenario' ])) {
            $options[ 'scenario' ] = $this->options[ 'scenarioId' ];
        }

        if (empty($options[ 'limit' ])) {
            $options[ 'limit' ] = $this->options[ 'limit' ];
        }

        if (empty($options[ 'template' ])) {
            $options = $this->options['template'];
        }

        $template = file_get_contents(__DIR__ . '/../Resources/public/hbt/' . $options[ 'template' ]);

        return $this->template->render(
            '@EzSystemsRecommendationBundle/Resources/public/views/recommendations.html.twig',
            array(
                'recommendationId' => uniqid(),
                'locationId' => $locationId,
                'scenarioId' => $options[ 'scenario' ],
                'limit' => $options[ 'limit' ],
                'template' => $template
            ),
            $response
        );
    }
}
