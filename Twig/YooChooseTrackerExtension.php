<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributd with this source code.
 */
namespace EzSystems\RecommendationBundle\Twig;

use Twig_Environment;
use Twig_Extension;
use Twig_Function_Method;

/**
 * Twig function that adds JS tracking code (yc.js) for the YooChoose recommendation engine.
 */
class YooChooseTrackerExtension extends Twig_Extension
{
    protected $customerId;

    protected $template;

    public function __construct( $template )
    {
        $this->template = $template;
    }

    public function setCustomerId( $customerId )
    {
        $this->customerId = $customerId;
    }

    public function getFunctions()
    {
        return array(
            "ez_recommendation_tracker" => new Twig_Function_Method(
                $this, "getTrackerCode", array("is_safe" => array("html"))
            ),
        );
    }

    public function getTrackerCode(Twig_Environment $twig)
    {
        return '';//$twig->render($this->template, array('customerId' => $this->customerId));
    }

    public function getName()
    {
        return 'ez_recommendation_tracker';
    }
}
