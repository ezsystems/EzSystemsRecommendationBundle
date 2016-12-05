<?php
/**
 * This file is part of the EzSystemRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Controller;

use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use EzSystems\RecommendationBundle\Rest\Values\ContentData as ContentDataValue;
use Symfony\Component\HttpFoundation\Request;

/**
 * Recommendation REST ContentType controller.
 */
class ContentTypeController extends ContentController
{
    /** @var array */
    private $pageSizes = [
        'http' => 10,
        'export' => 1000,
    ];

    /**
     * Prepares content for ContentDataValue class.
     *
     * @param string $contentTypeIdList
     * @param string $responseType
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \EzSystems\RecommendationBundle\Rest\Values\ContentData ContentDataValue
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException if the content, version with the given id and languages or content type does not exist
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the user has no access to read content and in case of un-published content: read versions
     */
    public function getContentType($contentTypeIdList, $responseType, Request $request)
    {
        $contentTypeIds = explode(',', $contentTypeIdList);

        $content = $this->prepareContentByContentTypeIds($contentTypeIds, $responseType, $request);

        return new ContentDataValue($content, [
            'responseType' => $responseType,
            'chunkSize' => $request->get('page_size', $this->getDeafultPageSize($responseType)),
            'documentRoot' => $request->server->get('DOCUMENT_ROOT'),
            'webHook' => $request->get('webHook'),
            'transaction' => $request->get('transaction', date('YmdHis', time())),
            'lang' => $request->get('lang'),
            'host' => $request->getSchemeAndHttpHost(),
            'customerId' => $this->customerId,
            'licenseKey' => $this->licenseKey,
            'contentTypeIds' => $contentTypeIds,
        ]);
    }

    /**
     * Returns paged content based on ContentType ids.
     *
     * @param array $contentTypeIds
     * @param string $responseType
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array
     */
    protected function prepareContentByContentTypeIds($contentTypeIds, $responseType, Request $request)
    {
        $pageSize = (int)$request->get('page_size', $this->getDeafultPageSize($responseType));
        $page = $request->get('page', 1);
        $offset = $page * $pageSize - $pageSize;
        $path = $request->get('path');
        $hidden = $request->get('hidden');
        $contentItems = array();

        foreach ($contentTypeIds as $contentTypeId) {
            $criteria = array(new Criterion\ContentTypeId($contentTypeId));

            if ($path) {
                $criteria[] = new Criterion\Subtree($path);
            }

            if (!$hidden) {
                $criteria[] = new Criterion\Visibility(Criterion\Visibility::VISIBLE);
            }

            $siteAccess = $request->get('sa', $this->siteAccess->name);
            $rootLocationId = $this->configResolver->getParameter('content.tree_root.location_id', null, $siteAccess);
            $criteria[] = new Criterion\Subtree($this->locationService->loadLocation($rootLocationId)->pathString);

            $query = new Query();
            $query->query = new Criterion\LogicalAnd($criteria);

            if ($responseType != 'export') {
                $query->limit = $pageSize;
                $query->offset = $offset;
            }

            $contentItems[$contentTypeId] = $this->searchService->findContent($query)->searchHits;
        }

        return $this->prepareContent($contentItems, $request);
    }

    /**
     * @param string $responseType
     *
     * @return mixed
     */
    private function getDeafultPageSize($responseType)
    {
        if (isset($this->pageSizes[$responseType])) {
            return $this->pageSizes[$responseType];
        }

        return $this->pageSizes['http'];
    }
}
