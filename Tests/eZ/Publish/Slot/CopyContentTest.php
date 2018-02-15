<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Tests\eZ\Publish\Slot;

use eZ\Publish\Core\SignalSlot\Signal\ContentService\CopyContentSignal;

class CopyContentTest extends AbstractSlotTest
{
    const DST_CONTENT_ID = 100;

    public function testReceiveSignal()
    {
        $this->assertRecommendationServiceIsNotified([
            'updateContent' => [self::DST_CONTENT_ID],
        ]);

        $this->slot->receive($this->createSignal());
    }

    protected function createSignal()
    {
        return new CopyContentSignal([
            'dstContentId' => self::DST_CONTENT_ID,
        ]);
    }

    protected function getSlotClass()
    {
        return 'EzSystems\RecommendationBundle\eZ\Publish\Slot\CopyContent';
    }

    protected function getReceivedSignalClasses()
    {
        return ['eZ\Publish\Core\SignalSlot\Signal\ContentService\CopyContentSignal'];
    }
}
