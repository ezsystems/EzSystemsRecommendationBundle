<?php
/**
 * This file is part of the EzSystemRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Recommendation REST Content controller.
 */
class ContentController extends BaseController
{
    /**
     * Prepares content for ContentDataValue class.
     *
     * @param string $contentIdList
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \EzSystems\RecommendationBundle\Rest\Values\ContentData|\Symfony\Component\HttpFoundation\JsonResponse
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException if the content, version with the given id and languages or content type does not exist
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the user has no access to read content and in case of un-published content: read versions
     */
    public function getContent($contentIdList, Request $request)
    {
        if (!$this->authenticator->authenticate()) {
            return new JsonResponse(['Access denied: wrong credentials'], 401);
        }

        $options = $this->parseRequest($request);
        $options['contentIdList'] = $this->contentType->getIdListFromString($contentIdList);

        return $this->contentType->get($options);
    }
}
