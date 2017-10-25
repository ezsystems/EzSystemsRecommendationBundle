<?php
/**
 * This file is part of the EzSystemRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Content;

use Exception;
use EzSystems\RecommendationBundle\Client\ExportNotifier;
use EzSystems\RecommendationBundle\Helper\FileSystem;
use EzSystems\RecommendationBundle\Helper\SiteAccess;
use EzSystems\RecommendationBundle\Rest\ValueObjectVisitor\ContentListElementGenerator;
use LogicException;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use EzSystems\RecommendationBundle\Rest\Values\ContentData as ContentDataValue;
use Psr\Log\LoggerInterface;

class ContentType extends Content
{
    /** @var array */
    private $pageSizes = array(
        'http' => 10,
        'export' => 1000,
    );

    /** @var LoggerInterface */
    private $logger;

    /** @var ExportNotifier */
    protected $exportNotifier;

    /** @var \EzSystems\RecommendationBundle\Rest\ValueObjectVisitor\ContentListElementGenerator */
    protected $contentListElementGenerator;

    /** @var \EzSystems\RecommendationBundle\Helper\SiteAccess */
    protected $siteAccessHelper;

    /** @var \EzSystems\RecommendationBundle\Helper\FileSystem */
    protected $fileSystemHelper;

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param \EzSystems\RecommendationBundle\Client\ExportNotifier $exportNotifier
     */
    public function setExportNotifier(ExportNotifier $exportNotifier)
    {
        $this->exportNotifier = $exportNotifier;
    }

    /**
     * @param \EzSystems\RecommendationBundle\Rest\ValueObjectVisitor\ContentListElementGenerator $contentListElementGenerator
     */
    public function setContentListElementGenerator(ContentListElementGenerator $contentListElementGenerator)
    {
        $this->contentListElementGenerator = $contentListElementGenerator;
    }

    /**
     * @param \EzSystems\RecommendationBundle\Helper\SiteAccess $siteAccessHelper
     */
    public function setSiteAccessHelper(SiteAccess $siteAccessHelper)
    {
        $this->siteAccessHelper = $siteAccessHelper;
    }

    /**
     * @param \EzSystems\RecommendationBundle\Helper\FileSystem $fileSystemHelper
     */
    public function setFileSystemHelper(FileSystem $fileSystemHelper)
    {
        $this->fileSystemHelper = $fileSystemHelper;
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
        $options['contentTypeIds'] = $this->getIdListFromString($options['contentTypeIdList']);

        $chunkDir = $this->fileSystemHelper->createChunkDir($options['documentRoot']);

        $languages = $this->getLanguages($options);
        $options['languages'] = $languages;

        try {
            $this->fileSystemHelper->lock($options['documentRoot']);

            $urls = $this->generateFiles($languages, $chunkDir, $options);

            $this->fileSystemHelper->unlock($options['documentRoot']);
            $chunkDirPath = $options['documentRoot'] . '/var/export' . $chunkDir;

            $securedDir = $this->fileSystemHelper->secureDir($chunkDirPath);

            $response = $this->exportNotifier->sendYCResponse($urls, $options, $securedDir);
            $this->logger->info(sprintf('YC Response: %s', $response));
        } catch (Exception $e) {
            $this->logger->error(sprintf('Error while generating export: %s', $e->getMessage()));
            $this->fileSystemHelper->unlock($options['documentRoot']);
        }
    }

    /**
     * Preparing array of integers based on comma separated integers in string or single integer in string.
     *
     * @param string $string list of integers separated by comma character

     * @return array
     *
     * @throws LogicException When incorrect $list value is given
     */
    public function getIdListFromString($string)
    {
        if (is_numeric($string)) {
            return array($string);
        }

        if (strpos($string, ',') === false) {
            throw new LogicException('Integers in %s should have a separator');
        }

        $array = explode(',', $string);

        foreach ($array as $item) {
            if (!is_numeric($item)) {
                throw new LogicException('%s should be a list of Integers');
            }
        }

        return $array;
    }

    /**
     * @param string $responseType
     *
     * @return mixed
     */
    public function getDefaultPageSize($responseType)
    {
        if (isset($this->pageSizes[$responseType])) {
            return $this->pageSizes[$responseType];
        }

        return $this->pageSizes['http'];
    }

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

        $criteria[] = $this->generateSubtreeCriteria($options);

        $query = new Query();
        $query->query = new Criterion\LogicalAnd($criteria);
        $query->limit = (int)$options['pageSize'];
        $query->offset = $offset;

        return $this->searchService->findContent(
            $query,
            (!empty($options['lang']) ? array('languages' => array($options['lang'])) : array())
        )->searchHits;
    }

    /**
     * Returns paged content based on ContentType ids.
     *
     * @param int $contentTypeId
     * @param array $options
     *
     * @return int
     */
    private function countContentByContentTypeId($contentTypeId, $options)
    {
        $criteria = array(new Criterion\ContentTypeId($contentTypeId));

        if ($options['path']) {
            $criteria[] = new Criterion\Subtree($options['path']);
        }

        if (!$options['hidden']) {
            $criteria[] = new Criterion\Visibility(Criterion\Visibility::VISIBLE);
        }

        $criteria[] = $this->generateSubtreeCriteria($options);

        $query = new Query();
        $query->query = new Criterion\LogicalAnd($criteria);
        $query->limit = 0;

        return $this->searchService->findContent($query)->totalCount;
    }

    private function generateSubtreeCriteria($options)
    {
        if (!empty($options['mandatorId'])) {
            $siteaccesses = $this->siteAccessHelper->getSiteaccessesByMandatorId($options['mandatorId']);
        } elseif (!empty($options['siteAccess'])) {
            $siteaccesses = array($options['siteAccess']);
        } else {
            $siteaccesses = array($this->siteAccess->name);
        }

        $subtreeCriteria = [];
        $rootLocations = $this->siteAccessHelper->getRootLocationsBySiteaccesses($siteaccesses);
        foreach ($rootLocations as $rootLocationId) {
            $subtreeCriteria[] = new Criterion\Subtree($this->locationService->loadLocation($rootLocationId)->pathString);
        }

        return new Criterion\LogicalOr($subtreeCriteria);
    }

    /**
     * Returns languages based on mandatorId or siteaccess.
     *
     * @param array $options
     *
     * @return array
     *
     * @throws LogicException When languages cannot be fetched from siteAccess or mandatorId.
     */
    private function getLanguages(array $options)
    {
        if (!empty($options['mandatorId'])) {
            $languages = $this->siteAccessHelper->getMainLanguagesBySiteaccesses(
                $this->siteAccessHelper->getSiteaccessesByMandatorId($options['mandatorId'])
            );
        } elseif (!empty($options['siteAccess'])) {
            $languages = $this->configResolver->getParameter('languages', '', $options['siteAccess']);
        } else {
            $languages = $this->configResolver->getParameter('languages');
        }

        if (empty($languages)) {
            throw new LogicException(sprintf('No languages found using siteAccess or mandatorId'));
        }

        return $languages;
    }

    /**
     * Generate export files.
     *
     * @param $languages
     * @param $chunkDir
     * @param array $options
     *
     * @return array
     */
    private function generateFiles($languages, $chunkDir, array $options)
    {
        $urls = array();

        foreach ($options['contentTypeIds'] as $contentTypeId) {
            $count = $this->countContentByContentTypeId($contentTypeId, $options);

            $this->logger->info(sprintf('fetching %s items of contentTypeId %s', $count, $contentTypeId));

            foreach ($languages as $lang) {
                $options['lang'] = $lang;

                for ($i = 1; $i <= ceil($count / $options['pageSize']); ++$i) {
                    $filename = sprintf('%d_%s_%d', $contentTypeId, $lang, $i);
                    $options['page'] = $i;

                    $content = $this->prepareContent(
                        $this->getContentItems($options, $contentTypeId),
                        $options
                    );

                    $chunkPath = $options['documentRoot'] . '/var/export' . $chunkDir . $filename;

                    $this->generateFile($content, $chunkPath, $options);

                    $url = sprintf(
                        '%s/api/ezp/v2/ez_recommendation/v1/exportDownload%s%s',
                        $options['host'], $chunkDir, $filename
                    );

                    $this->logger->info(sprintf('Generating url: %s', $url));

                    $urls[$contentTypeId][$lang][] = $url;
                }
            }
        }

        return $urls;
    }

    /**
     * Generating export file.
     *
     * @param $content
     * @param $chunkPath
     * @param array $options
     */
    private function generateFile($content, $chunkPath, array $options)
    {
        $generator = $this->getGeneratorService($options);

        $data = new ContentDataValue($content, $options);

        $generator->reset();
        $generator->startDocument($data);

        $this->contentListElementGenerator->generateElement($generator, $content);

        $this->fileSystemHelper->save($chunkPath, $generator->endDocument($data));

        $this->logger->info(sprintf('Generating file: %s', $chunkPath));
    }

    /**
     * Returns output generator service.
     *
     * @param array $options
     *
     * @return \eZ\Publish\Core\REST\Common\Output\Generator
     */
    private function getGeneratorService(array $options)
    {
        $generatorService = sprintf('ezpublish_rest.output.generator.%s', $options['requestContentType']);
        if ($this->container->has($generatorService)) {
            $generator = $this->container->get($generatorService);
        } else {
            $generator = $this->container->get('ezpublish_rest.output.generator.json');
        }

        return $generator;
    }
}
