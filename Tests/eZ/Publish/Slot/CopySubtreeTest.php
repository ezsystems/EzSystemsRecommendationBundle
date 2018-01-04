<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Tests\eZ\Publish\Slot;

use eZ\Publish\Core\SignalSlot\Signal\LocationService\CopySubtreeSignal;

class CopySubtreeTest extends AbstractPersistenceAwareBaseTest
{
    public function testReceiveSignal()
    {
        $signal = $this->createSignal();

        $locationHandler = $this->getMock('\eZ\Publish\SPI\Persistence\Content\Location\Handler');
        $locationHandler
            ->expects($this->once())
            ->method('loadSubtreeIds')
            ->with($signal->targetNewSubtreeId)
            ->willReturn($this->getTargetNewSubtreeId());

        $this->persistenceHandler
            ->expects($this->any())
            ->method('locationHandler')
            ->willReturn($locationHandler);

        $this->assertRecommendationServiceIsNotified([
            'updateContent' => $this->getTargetNewSubtreeId(),
        ]);

        $this->slot->receive($this->createSignal());
    }

    protected function createSignal()
    {
        return new CopySubtreeSignal([
            'targetNewSubtreeId' => 100,
        ]);
    }

    protected function getSlotClass()
    {
        return '\EzSystems\RecommendationBundle\eZ\Publish\Slot\CopySubtree';
    }

    protected function getReceivedSignalClasses()
    {
        return ['eZ\Publish\Core\SignalSlot\Signal\LocationService\CopySubtreeSignal'];
    }

    private function getTargetNewSubtreeId()
    {
        return [2016, 2017, 2018];
    }
}
