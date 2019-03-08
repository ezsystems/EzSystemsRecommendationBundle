<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace EzSystems\RecommendationBundle\eZ\Publish\Slot;

use eZ\Publish\Core\SignalSlot\Slot as BaseSlot;
use EzSystems\RecommendationBundle\Rest\Api\YooChooseNotifier;

abstract class Base extends BaseSlot
{
    /** @var \EzSystems\RecommendationBundle\Rest\Api\YooChooseNotifier */
    protected $client;

    /**
     * @param \EzSystems\RecommendationBundle\Rest\Api\YooChooseNotifier $client
     */
    public function __construct(YooChooseNotifier $client)
    {
        $this->client = $client;
    }
}
