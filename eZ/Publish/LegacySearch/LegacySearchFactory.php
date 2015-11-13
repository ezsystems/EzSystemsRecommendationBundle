<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\eZ\Publish\LegacySearch;

class LegacySearchFactory
{
    public static function build(\Closure $legacyKernelClosure)
    {
        try {
            $searchEngine = $legacyKernelClosure()->runCallback(
                function () {
                    return \eZSearch::getEngine();
                }
            );
        } catch (\Exception $e) {
            $searchEngine = new NullSearchEngine();
        }

        return $searchEngine;
    }
}
