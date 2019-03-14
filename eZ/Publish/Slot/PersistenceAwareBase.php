<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace EzSystems\RecommendationBundle\eZ\Publish\Slot;

use eZ\Publish\SPI\Persistence\Handler as PersistenceHandler;
use EzSystems\RecommendationBundle\Rest\Api\YooChooseNotifier;

/*
 * A persistence aware base slot
 */
abstract class PersistenceAwareBase extends Base
{
    /** @var \eZ\Publish\SPI\Persistence\Handler */
    protected $persistenceHandler;

    public function __construct(YooChooseNotifier $client, PersistenceHandler $persistenceHandler)
    {
        parent::__construct($client);
        $this->persistenceHandler = $persistenceHandler;
    }
}
