<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Tests\eZ\Publish\Slot;

use eZ\Publish\Core\SignalSlot\Signal\TrashService\TrashSignal;
use eZ\Publish\SPI\Persistence\Content\Relation;

class TrashTest extends AbstractPersistenceAwareBaseTest
{
    const CONTENT_ID = 100;

    public function testReceiveSignal()
    {
        $contentHandler = $this->getMockBuilder('\eZ\Publish\SPI\Persistence\Content\Handler')->getMock();
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
            ->expects($this->once())
            ->method('contentHandler')
            ->willReturn($contentHandler);

        $this->assertRecommendationServiceIsNotified([
            'deleteContent' => [self::CONTENT_ID],
            'updateContent' => $this->getReverseRelationsIds(),
        ]);

        $this->slot->receive($this->createSignal());
    }

    protected function createSignal()
    {
        return new TrashSignal([
            'contentId' => self::CONTENT_ID,
        ]);
    }

    protected function getSlotClass()
    {
        return 'EzSystems\RecommendationBundle\eZ\Publish\Slot\Trash';
    }

    protected function getReceivedSignalClasses()
    {
        return ['eZ\Publish\Core\SignalSlot\Signal\TrashService\TrashSignal'];
    }

    private function getReverseRelationsIds()
    {
        return [101, 105, 107];
    }
}
