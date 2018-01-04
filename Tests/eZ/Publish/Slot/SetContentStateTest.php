<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Tests\eZ\Publish\Slot;

use eZ\Publish\Core\SignalSlot\Signal\ObjectStateService\SetContentStateSignal;

class SetContentStateTest extends AbstractSlotTest
{
    const CONTENT_ID = 100;

    public function testReceiveSignal()
    {
        $signal = $this->createSignal();

        $this->assertRecommendationServiceIsNotified([
            'updateContent' => [self::CONTENT_ID],
        ]);

        $this->slot->receive($signal);
    }

    protected function createSignal()
    {
        return new SetContentStateSignal([
            'contentId' => self::CONTENT_ID,
        ]);
    }

    protected function getSlotClass()
    {
        return 'EzSystems\RecommendationBundle\eZ\Publish\Slot\SetContentState';
    }

    protected function getReceivedSignalClasses()
    {
        return ['eZ\Publish\Core\SignalSlot\Signal\ObjectStateService\SetContentStateSignal'];
    }
}
