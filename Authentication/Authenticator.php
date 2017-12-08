<?php

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
