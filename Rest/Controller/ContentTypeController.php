<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Controller;

use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\Core\REST\Server\Exceptions\AuthenticationFailedException;
use eZ\Publish\Core\REST\Server\Exceptions\BadRequestException;
use EzSystems\RecommendationBundle\Helper\Text;
use EzSystems\RecommendationBundle\Rest\Values\ContentData as ContentDataValue;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use InvalidArgumentException;

/**
 * Recommendation REST ContentType controller.
 */
class ContentTypeController extends ContentController
{
    const PAGE_SIZE = 10;

    /**
     * Prepares content for ContentDataValue class.
     *
     * @param string $contentTypeIdList
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return ContentDataValue
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException if the content, version with the given id and languages or content type does not exist
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the user has no access to read content and in case of un-published content: read versions
     * @throws \eZ\Publish\Core\REST\Server\Exceptions\BadRequestException If incorrect $contentTypeIdList value is given
     * @throws \eZ\Publish\Core\REST\Server\Exceptions\AuthenticationFailedException If credentials are wrong
     */
    public function getContentTypeAction($contentTypeIdList, Request $request)
    {
        if (!$this->authenticator->authenticate()) {
            throw new AuthenticationFailedException('Access denied: wrong credentials', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $contentTypeIds = Text::getIdListFromString($contentTypeIdList);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestException('Bad Request', Response::HTTP_BAD_REQUEST);
        }

        $content = $this->prepareContentByContentTypeIds($contentTypeIds, $request);

        return new ContentDataValue($content);
    }

    /**
     * Returns paged content based on ContentType ids.
     *
     * @param array $contentTypeIds
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array
     */
    protected function prepareContentByContentTypeIds($contentTypeIds, Request $request)
    {
        $options = $this->parseParameters($request->query, ['page_size', 'page', 'path', 'hidden', 'lang', 'sa', 'image']);

        $pageSize = (int)$options->get('page_size', self::PAGE_SIZE);
        $page = (int)$options->get('page', 1);
        $offset = $page * $pageSize - $pageSize;
        $path = $options->get('path');
        $hidden = $options->get('hidden');
        $lang = $options->get('lang');
        $siteAccess = $options->get('sa');

        $rootLocationPathString = $this->locationService->loadLocation(
            $this->siteAccessHelper->getRootLocationBySiteAccessName($siteAccess)
        )->pathString;

        $contentItems = array();

        foreach ($contentTypeIds as $contentTypeId) {
            $criteria = array(new Criterion\ContentTypeId($contentTypeId));

            if ($path) {
                $criteria[] = new Criterion\Subtree($path);
            }

            if (!$hidden) {
                $criteria[] = new Criterion\Visibility(Criterion\Visibility::VISIBLE);
            }

            $criteria[] = new Criterion\Subtree($rootLocationPathString);

            $query = new Query();
            $query->query = new Criterion\LogicalAnd($criteria);
            $query->limit = $pageSize;
            $query->offset = $offset;

            $contentItems[$contentTypeId] = $this->searchService->findContent(
                $query,
                (!empty($lang) ? array('languages' => array($lang)) : array())
            )->searchHits;
        }

        $contentOptions = $this->parseParameters($request->query, ['lang', 'fields', 'image']);

        return $this->content->prepareContent($contentItems, $contentOptions);
    }
}
