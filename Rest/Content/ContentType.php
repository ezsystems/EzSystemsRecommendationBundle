<?php
/**
 * This file is part of the EzSystemRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Content;

use Exception;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use EzSystems\RecommendationBundle\Rest\Values\ContentData as ContentDataValue;

class ContentType extends Content
{
    /** @var array */
    private $pageSizes = array(
        'http' => 10,
        'export' => 1000,
    );

    /**
     * @param array $options
     *
     * @return array
     */
    public function prepareContentByContentTypeIds($options)
    {
        $contentItems = array();

        foreach ($options['contentTypeIds'] as $contentTypeId) {
            $contentItems[$contentTypeId] = $this->getContentItems($options, $contentTypeId);
        }

        return $this->prepareContentTypes($contentItems, $options);
    }

    /**
     * @param int $contentTypeId
     * @param array $options
     *
     * @return array
     */
    public function prepareContentByContentTypeId($contentTypeId, $options)
    {
        return $this->prepareContent($this->getContentItems($options, $contentTypeId), $options);
    }

    /**
     * @param array $options
     * @param int $contentTypeId
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Search\SearchHit[]
     */
    private function getContentItems($options, $contentTypeId)
    {
        $offset = $options['page'] * $options['pageSize'] - $options['pageSize'];

        $criteria = array(
            new Criterion\ContentTypeId($contentTypeId),
        );

        if ($options['path']) {
            $criteria[] = new Criterion\Subtree($options['path']);
        }

        if (!$options['hidden']) {
            $criteria[] = new Criterion\Visibility(Criterion\Visibility::VISIBLE);
        }

        $rootLocationId = $this->configResolver->getParameter('content.tree_root.location_id', null, $options['siteAccess']);

        $criteria[] = new Criterion\Subtree($this->locationService->loadLocation($rootLocationId)->pathString);

        $query = new Query();
        $query->query = new Criterion\LogicalAnd($criteria);
        $query->limit = (int)$options['pageSize'];
        $query->offset = $offset;

        return $this->searchService->findContent($query, array('languages' => array($options['lang'])))->searchHits;
    }

    /**
     * Returns paged content based on ContentType ids.
     *
     * @param int $contentTypeId
     * @param array $options
     *
     * @return array
     */
    public function countContentByContentTypeId($contentTypeId, $options)
    {
        $criteria = array(new Criterion\ContentTypeId($contentTypeId));

        if ($options['path']) {
            $criteria[] = new Criterion\Subtree($options['path']);
        }

        if ($options['hidden']) {
            $criteria[] = new Criterion\Visibility(Criterion\Visibility::VISIBLE);
        }

        $rootLocationId = $this->configResolver->getParameter('content.tree_root.location_id', null, $options['siteAccess']);

        $criteria[] = new Criterion\Subtree($this->locationService->loadLocation($rootLocationId)->pathString);

        $query = new Query();
        $query->query = new Criterion\LogicalAnd($criteria);
        $query->limit = 0;

        return $this->searchService->findContent($query)->totalCount;
    }

    /**
     * @param string $responseType
     *
     * @return mixed
     */
    public function getDeafultPageSize($responseType)
    {
        if (isset($this->pageSizes[$responseType])) {
            return $this->pageSizes[$responseType];
        }

        return $this->pageSizes['http'];
    }

    /**
     * @param array $options
     *
     * @return \EzSystems\RecommendationBundle\Rest\Values\ContentData|void
     *
     * @throws \EzSystems\RecommendationBundle\Rest\Exception\ExportInProgressException
     */
    public function runExport(array $options)
    {
        $this->requestContext->setScheme('http');
        if (strstr($options['schemeAndHttpHost'], 'https')) {
            $this->requestContext->setScheme('https://');
        }

        $this->requestContext->setHost(preg_replace('#^https?://#', '', $options['schemeAndHttpHost']));

        $generatorService = sprintf('ezpublish_rest.output.generator.%s', $options['requestContentType']);
        if ($this->container->has($generatorService)) {
            $generator = $this->container->get($generatorService);
        } else {
            $generator = $this->container->get('ezpublish_rest.output.generator.json');
        }

        $export = $this->container->get('ez_recommendation.rest.response.export');

        $options['contentTypeIds'] = explode(',', $options['contentTypeIdList']);
        $chunkDir = $export->createChunkDir($options['documentRoot']);
        $chunkDirPath = $options['documentRoot'] . '/var/export' . $chunkDir;
        $siteAccess = isset($options['siteAccess']) ? $options['siteAccess'] : $this->getSiteAccess($options['requestedMandatorId']);
        $languages = isset($options['lang']) ? array($options['lang']) : $this->configResolver->getParameter('languages', '', $siteAccess);
        $options['languages'] = $languages;

        touch($options['documentRoot'] . '/var/export/.lock');

        $urls = array();
        try {
            foreach ($options['contentTypeIds'] as $contentTypeId) {
                $count = $this->countContentByContentTypeId($contentTypeId, $options);
                foreach ($languages as $lang) {
                    $options['lang'] = $lang;
                    for ($i = 1; $i <= ceil($count / $options['pageSize']); ++$i) {
                        $filename = sprintf('%d_%s_%d', $contentTypeId, $lang, $i);
                        $options['page'] = $i;

                        $content = $this->prepareContentByContentTypeId($contentTypeId, $options);

                        $data = new ContentDataValue($content, $options);

                        $generator->reset();
                        $generator->startDocument($data);

                        $export->contentListElementGenerator->generateElement($generator, $content);
                        $chunkPath = $chunkDirPath . $filename;
                        file_put_contents($chunkPath, $generator->endDocument($data));
                        $urls[$contentTypeId][$lang][] = sprintf('%s/api/ezp/v2/ez_recommendation/v1/exportDownload%s%s', $options['host'], $chunkDir, $filename);
                        unset($content);
                        unset($data);
                    }
                }
            }
        } catch (Exception $e) {
            unlink($options['documentRoot'] . '/var/export/.lock');

            return;
        }
        unlink($options['documentRoot'] . '/var/export/.lock');

        $export->sendYCResponse($urls, $options, $chunkDirPath);
    }
}
