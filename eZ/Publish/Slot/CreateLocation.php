<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace EzSystems\RecommendationBundle\eZ\Publish\Slot;

use eZ\Publish\Core\SignalSlot\Signal;

/**
 * A Solr slot handling CreateLocationSignal.
 */
class CreateLocation extends Base
{
    /**
     * Receive the given $signal and react on it
     *
     * @param \eZ\Publish\Core\SignalSlot\Signal $signal
     */
    public function receive( Signal $signal )
    {
        if (!$signal instanceof Signal\LocationService\CreateLocationSignal) {
            return;
        }

        $this->client->updateContent($signal->contentId);
    }
}
