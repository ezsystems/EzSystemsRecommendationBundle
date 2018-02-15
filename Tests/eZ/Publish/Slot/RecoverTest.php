<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Tests\eZ\Publish\Slot;

use eZ\Publish\SPI\Persistence\Content\Relation;
use eZ\Publish\Core\SignalSlot\Signal\TrashService\RecoverSignal;

class RecoverTest extends AbstractPersistenceAwareBaseTest
{
    const CONTENT_ID = 100;
    const LOCATION_ID = 58;

    public function testReceiveSignal()
    {
        $signal = $this->createSignal();

        $locationHandler = $this->getMock('\eZ\Publish\SPI\Persistence\Content\Location\Handler');
        $locationHandler
            ->expects($this->once())
            ->method('loadSubtreeIds')
            ->with($signal->newLocationId)
            ->willReturn($this->getSubtreeIds());

        $contentHandler = $this->getMock('\eZ\Publish\SPI\Persistence\Content\Handler');
        $contentHandler
            ->expects($this->once())
            ->method('loadReverseRelations')
            ->with(self::CONTENT_ID)
            ->willReturn(array_map(function ($id) {
                return new Relation([
                    'destinationContentId' => $id,
                ]);
            }, $this->getReverseRelationsIds()));

        $this->persistenceHandler
            ->expects($this->any())
            ->method('contentHandler')
            ->willReturn($contentHandler);

        $this->persistenceHandler
            ->expects($this->any())
            ->method('locationHandler')
            ->willReturn($locationHandler);

        $this->assertRecommendationServiceIsNotified([
            'updateContent' => array_merge($this->getReverseRelationsIds(), $this->getSubtreeIds()),
        ]);

        $this->slot->receive($signal);
    }

    protected function createSignal()
    {
        return new RecoverSignal([
            'contentId' => self::CONTENT_ID,
            'newLocationId' => self::LOCATION_ID,
        ]);
    }

    protected function getSlotClass()
    {
        return 'EzSystems\RecommendationBundle\eZ\Publish\Slot\Recover';
    }

    protected function getReceivedSignalClasses()
    {
        return ['eZ\Publish\Core\SignalSlot\Signal\TrashService\RecoverSignal'];
    }

    private function getSubtreeIds()
    {
        return [2016, 2017, 2018];
    }

    private function getReverseRelationsIds()
    {
        return [101, 105, 107];
    }
}
