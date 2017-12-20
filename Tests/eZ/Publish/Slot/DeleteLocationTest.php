<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Tests\eZ\Publish\Slot;

use eZ\Publish\Core\SignalSlot\Signal\LocationService\DeleteLocationSignal;

class DeleteLocationTest extends AbstractSlotTest
{
    const CONTENT_ID = 100;

    public function testReceiveSignal()
    {
        $signal = $this->createSignal();

        $this->assertRecommendationServiceIsNotified([
            'deleteContent' => [self::CONTENT_ID],
        ]);

        $this->slot->receive($signal);
    }

    protected function createSignal()
    {
        return new DeleteLocationSignal([
            'contentId' => self::CONTENT_ID,
        ]);
    }

    protected function getSlotClass()
    {
        return 'EzSystems\RecommendationBundle\eZ\Publish\Slot\DeleteLocation';
    }

    protected function getReceivedSignalClasses()
    {
        return ['eZ\Publish\Core\SignalSlot\Signal\LocationService\DeleteLocationSignal'];
    }
}
