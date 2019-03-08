<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Tests\Helper;

use EzSystems\RecommendationBundle\Helper\Text;
use PHPUnit\Framework\TestCase;

class TextTest extends TestCase
{
    /**
     * @dataProvider stringLists
     */
    public function testGetIdListFromString($input, $expected)
    {
        $result = Text::getIdListFromString($input);

        $this->assertEquals($expected, $result);
        $this->assertInternalType('array', $result);
    }

    public function stringLists()
    {
        return [
            ['123', [123]],
            ['123,456', [123, 456]],
            ['12,34,56', [12, 34, 56]],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage String should be a list of Integers
     */
    public function testGetIdListFromStringWithoutSeparator()
    {
        Text::getIdListFromString('1abcd');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage String should be a list of Integers
     */
    public function testGetIdListFromStringWitInvalidArgument()
    {
        Text::getIdListFromString('123,abc,456');
    }

    public function getArrayFromStringDataProvider()
    {
        return [
            ['123', ['123']],
            ['123,456', ['123', '456']],
            ['12,34,56', ['12', '34', '56']],
            ['ab', ['ab']],
            ['ab,bc', ['ab', 'bc']],
        ];
    }

    /**
     * @dataProvider getArrayFromStringDataProvider
     */
    public function testGetArrayFromString($input, $expected)
    {
        $result = Text::getArrayFromString($input);

        $this->assertEquals($expected, $result);
        $this->assertInternalType('array', $result);
    }
}
