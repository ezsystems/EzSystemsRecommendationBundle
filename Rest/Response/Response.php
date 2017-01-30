<?php
/**
 * This file is part of the EzSystemRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Response;

use EzSystems\RecommendationBundle\Rest\ValueObjectVisitor\ContentListElementGenerator;

abstract class Response implements ResponseInterface
{
    /** @var \EzSystems\RecommendationBundle\Rest\ValueObjectVisitor\ContentListElementGenerator */
    public $contentListElementGenerator;

    /** @var bool */
    protected $authenticationMethod;

    /** @var string */
    protected $authenticationLogin;

    /** @var string */
    protected $authenticationPassword;

    /**
     * @param \EzSystems\RecommendationBundle\Rest\ValueObjectVisitor\ContentListElementGenerator $contentListElementGenerator
     * @param bool $authenticationMethod
     * @param string $authenticationLogin
     * @param string $authenticationPassword
     */
    public function __construct(
        ContentListElementGenerator $contentListElementGenerator,
        $authenticationMethod,
        $authenticationLogin,
        $authenticationPassword
    ) {
        $this->contentListElementGenerator = $contentListElementGenerator;
        $this->authenticationMethod = $authenticationMethod;
        $this->authenticationLogin = $authenticationLogin;
        $this->authenticationPassword = $authenticationPassword;
    }
}
