<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Client;

interface RecommendationClient
{
    /**
     * Notifies YooChoose about content update with specified $contentId.
     *
     * @param mixed $contentId
     */
    public function updateContent($contentId);

    /**
     * Notifies YooChoose about content deletion with specified $contentId.
     *
     * @param mixed $contentId
     */
    public function deleteContent($contentId);
}
