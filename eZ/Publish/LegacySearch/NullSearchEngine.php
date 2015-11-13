<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\eZ\Publish\LegacySearch;

use ezpSearchEngine;

/**
 * Recommendation legacy search engine mockup.
 *
 * This class was created to imitate working of `ezpSearchEngine` which is
 * responsible for breaking left tree menu in eZ Publish administration panel.
 *
 * @link https://jira.ez.no/browse/EZP-23860
 */
class NullSearchEngine implements ezpSearchEngine
{
    public function needCommit()
    {
        return false;
    }

    public function needRemoveWithUpdate()
    {
        return false;
    }

    public function addObject($contentObject, $commit = true)
    {
        return false;
    }

    public function removeObject($contentObject, $commit = null)
    {
        return false;
    }

    public function removeObjectById($contentObjectId, $commit = null)
    {
        return false;
    }

    public function search($searchText, $params = array(), $searchTypes = array())
    {
        return array();
    }

    public function supportedSearchTypes()
    {
        return array();
    }

    public function commit()
    {
    }
}
