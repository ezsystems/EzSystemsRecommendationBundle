<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Tests\eZ\Publish\Slot;

abstract class AbstractPersistenceAwareBaseTest extends AbstractSlotTest
{
    /** @var \eZ\Publish\SPI\Persistence\Handler|\PHPUnit_Framework_MockObject_MockObject */
    protected $persistenceHandler;

    public function setUp()
    {
        $this->persistenceHandler = $this->getMock('\eZ\Publish\SPI\Persistence\Handler');

        parent::setUp();
    }

    protected function createSlot()
    {
        $class = $this->getSlotClass();

        return new $class($this->client, $this->persistenceHandler);
    }
}
