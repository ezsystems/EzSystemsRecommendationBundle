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

class Recover extends PersistenceAwareBase
{
    public function receive(Signal $signal)
    {
        if (!$signal instanceof Signal\TrashService\RecoverSignal) {
            return;
        }

        $contentIdArray = $this->persistenceHandler->locationHandler()->loadSubtreeIds($signal->newLocationId);
        foreach ($contentIdArray as $contentId) {
            $this->client->updateContent($contentId);
        }
    }
}
