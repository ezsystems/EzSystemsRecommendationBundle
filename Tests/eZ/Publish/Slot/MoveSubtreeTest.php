<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Tests\eZ\Publish\Slot;

use eZ\Publish\Core\SignalSlot\Signal\LocationService\MoveSubtreeSignal;

class MoveSubtreeTest extends AbstractPersistenceAwareBaseTest
{
    const LOCATION_ID = 100;

    public function testReceiveSignal()
    {
        $signal = $this->createSignal();

        $locationHandler = $this->getMockBuilder('\eZ\Publish\SPI\Persistence\Content\Location\Handler')->getMock();
        $locationHandler
            ->expects($this->once())
            ->method('loadSubtreeIds')
            ->with($signal->locationId)
            ->willReturn($this->getSubtreeIds());

        $this->persistenceHandler
            ->expects($this->any())
            ->method('locationHandler')
            ->willReturn($locationHandler);

        $this->assertRecommendationServiceIsNotified([
            'updateContent' => $this->getSubtreeIds(),
        ]);

        $this->slot->receive($this->createSignal());
    }

    protected function createSignal()
    {
        return new MoveSubtreeSignal([
            'locationId' => self::LOCATION_ID,
        ]);
    }

    protected function getSlotClass()
    {
        return '\EzSystems\RecommendationBundle\eZ\Publish\Slot\MoveSubtree';
    }

    protected function getReceivedSignalClasses()
    {
        return ['eZ\Publish\Core\SignalSlot\Signal\LocationService\MoveSubtreeSignal'];
    }

    private function getSubtreeIds()
    {
        return [2016, 2017, 2018];
    }
}
