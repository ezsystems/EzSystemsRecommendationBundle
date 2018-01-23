<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Authentication;

interface Authenticator
{
    /**
     * @return bool
     */
    public function authenticate();

    /**
     * @param string $filePath
     *
     * @return bool
     */
    public function authenticateByFile($filePath);

    /**
     * Returns credentials data.
     *
     * @return array
     */
    public function getCredentials();
}
