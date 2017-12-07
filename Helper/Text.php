<?php
/**
 * This file is part of the EzSystemRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Helper;

use InvalidArgumentException;

class Text
{
    /**
     * Preparing array of integers based on comma separated integers in string or single integer in string.
     *
     * @param string $string list of integers separated by comma character
     *
     * @return array
     *
     * @throws InvalidArgumentException If incorrect $list value is given
     */
    public static function getIdListFromString($string)
    {
        if (filter_var($string, FILTER_VALIDATE_INT) !== false) {
            return array($string);
        }

        if (strpos($string, ',') === false) {
            throw new InvalidArgumentException('Integers in string should have a separator');
        }

        $array = explode(',', $string);

        foreach ($array as $item) {
            if (filter_var($item, FILTER_VALIDATE_INT) === false) {
                throw new InvalidArgumentException('String should be a list of Integers');
            }
        }

        return $array;
    }
}
