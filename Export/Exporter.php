<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Export;

use Exception;
use eZ\Publish\API\Repository\LocationService;
use EzSystems\RecommendationBundle\Rest\Content\Content;
use LogicException;
use eZ\Publish\Core\REST\Common\Output\Generator;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use EzSystems\RecommendationBundle\Client\ExportNotifier;
use EzSystems\RecommendationBundle\Helper\FileSystem;
use EzSystems\RecommendationBundle\Helper\Text;
use EzSystems\RecommendationBundle\Helper\SiteAccess;
use EzSystems\RecommendationBundle\Rest\ValueObjectVisitor\ContentListElementGenerator;
use EzSystems\RecommendationBundle\Rest\Values\ContentData;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class Exporter
{
    /** @var \eZ\Publish\Api\Repository\SearchService */
    protected $searchService;

    /** @var \eZ\Publish\Api\Repository\ContentTypeService */
    protected $contentTypeService;

    /** @var \eZ\Publish\Api\Repository\LocationService */
    protected $locationService;

    /** @var \EzSystems\RecommendationBundle\Helper\FileSystem */
    private $fileSystemHelper;

    /** @var \EzSystems\RecommendationBundle\Helper\SiteAccess */
    private $siteAccessHelper;

    /** @var \EzSystems\RecommendationBundle\Client\ExportNotifier */
    private $exportNotifier;

    /** @var \EzSystems\RecommendationBundle\Rest\Content\Content */
    private $content;

    /** @var \EzSystems\RecommendationBundle\Rest\ValueObjectVisitor\ContentListElementGenerator */
    private $contentListElementGenerator;

    /** @var \eZ\Publish\Core\REST\Common\Output\Generator */
    private $outputGenerator;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * @param \eZ\Publish\Api\Repository\SearchService $searchService
     * @param \eZ\Publish\Api\Repository\ContentTypeService $contentTypeService
     * @param \eZ\Publish\Api\Repository\LocationService $locationService
     * @param \EzSystems\RecommendationBundle\Helper\FileSystem $fileSystemHelper
     * @param \EzSystems\RecommendationBundle\Helper\SiteAccess $siteAccessHelper
     * @param \EzSystems\RecommendationBundle\Client\ExportNotifier $exportNotifier
     * @param \EzSystems\RecommendationBundle\Rest\Content\Content $content
     * @param \EzSystems\RecommendationBundle\Rest\ValueObjectVisitor\ContentListElementGenerator $contentListElementGenerator
     * @param \eZ\Publish\Core\REST\Common\Output\Generator $outputGenerator
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        SearchService $searchService,
        ContentTypeService $contentTypeService,
        LocationService $locationService,
        FileSystem $fileSystemHelper,
        SiteAccess $siteAccessHelper,
        ExportNotifier $exportNotifier,
        Content $content,
        ContentListElementGenerator $contentListElementGenerator,
        Generator $outputGenerator,
        LoggerInterface $logger
    ) {
        $this->searchService = $searchService;
        $this->contentTypeService = $contentTypeService;
        $this->locationService = $locationService;
        $this->fileSystemHelper = $fileSystemHelper;
        $this->siteAccessHelper = $siteAccessHelper;
        $this->exportNotifier = $exportNotifier;
        $this->content = $content;
        $this->contentListElementGenerator = $contentListElementGenerator;
        $this->outputGenerator = $outputGenerator;
        $this->logger = $logger;
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
        $options = $this->validate($options);

        $options['contentTypeIds'] = Text::getIdListFromString($options['contentTypeIdList']);
        $chunkDir = $this->fileSystemHelper->createChunkDir();
        $languages = $this->siteAccessHelper->getLanguages($options['mandatorId'], $options['siteAccess']);
        $options['languages'] = $languages;

        try {
            $this->fileSystemHelper->lock();
            $urls = $this->generateFiles($languages, $chunkDir, $options);
            $this->fileSystemHelper->unlock();

            $credentials = ['method' => 'basic'];
            $securedDirCredentials = $this->fileSystemHelper->secureDir($chunkDir, $credentials);

            $response = $this->exportNotifier->sendRecommendationResponse($urls, $options, $securedDirCredentials);
            $this->logger->info(sprintf('eZ Recommendation Response: %s', $response));
        } catch (Exception $e) {
            $this->logger->error(sprintf('Error while generating export: %s', $e->getMessage()));
            $this->fileSystemHelper->unlock();
        }
    }

    /**
     * Validates required options.
     *
     * @param array $options
     *
     * @return mixed
     */
    private function validate($options)
    {
        if (empty($options['contentTypeIdList'])) {
            throw new LogicException('contentTypeIdList is required');
        }

        if (empty($options['host'])) {
            throw new LogicException('host is required');
        }

        if (empty($options['siteAccess'])) {
            $options['siteAccess'] = $options['sa'];
        }

        if (empty($options['transaction'])) {
            $options['transaction'] = (new \DateTime())->format('YmdHis') . rand(111, 999);
        }

        if (empty($options['customerId']) || empty($options['licenseKey'])) {
            list($options['customerId'], $options['licenseKey']) =
                $this->siteAccessHelper->getRecommendationServiceCredentials();
        }

        return $options;
    }

    /**
     * Generate export files.
     *
     * @param array $languages
     * @param string $chunkDir
     * @param array $options
     *
     * @return array
     */
    private function generateFiles($languages, $chunkDir, array $options)
    {
        $urls = array();

        foreach ($options['contentTypeIds'] as $contentTypeId) {
            $count = $this->countContentItemsByContentTypeId($contentTypeId, $options);

            $this->logger->info(sprintf('fetching %s items of contentTypeId %s', $count, $contentTypeId));

            foreach ($languages as $lang) {
                $options['lang'] = $lang;
                $contentTypeName = $this->contentTypeService->loadContentType($contentTypeId)->getName($lang);

                for ($i = 1; $i <= ceil($count / $options['pageSize']); ++$i) {
                    $filename = sprintf('%d_%s_%d', $contentTypeId, $lang, $i);
                    $chunkPath = $chunkDir . $filename;
                    $options['page'] = $i;

                    $contentItems = $this->getContentItems($options, $contentTypeId);
                    $parameters = new ParameterBag($options);
                    $content = $this->content->prepareContent(array($contentTypeId => $contentItems), $parameters);

                    $this->generateFile($content, $chunkPath, $options);

                    $url = sprintf(
                        '%s/api/ezp/v2/ez_recommendation/v1/exportDownload/%s%s',
                        $options['host'], $chunkDir, $filename
                    );

                    $this->logger->info(sprintf('Generating url: %s', $url));

                    $urls[$contentTypeId][$lang]['urlList'][] = $url;
                    $urls[$contentTypeId][$lang]['contentTypeName'] = $contentTypeName;
                }
            }
        }

        return $urls;
    }

    /**
     * Returns total amount of content based on ContentType ids.
     *
     * @param int $contentTypeId
     * @param array $options
     *
     * @return int
     */
    private function countContentItemsByContentTypeId($contentTypeId, $options)
    {
        $criteria = array(
            new Criterion\ContentTypeId($contentTypeId),
        );

        if ($options['path']) {
            $criteria[] = new Criterion\Subtree($options['path']);
        }

        if (!$options['hidden']) {
            $criteria[] = new Criterion\Visibility(Criterion\Visibility::VISIBLE);
        }

        $query = new Query();
        $query->query = new Criterion\LogicalAnd($criteria);
        $query->limit = 0;

        return $this->searchService->findContent(
            $query,
            (!empty($options['lang']) ? array('languages' => array($options['lang'])) : array())
        )->totalCount;
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
     * Generating export file.
     *
     * @param array $content
     * @param string $chunkPath
     * @param array $options
     */
    private function generateFile($content, $chunkPath, array $options)
    {
        $data = new ContentData($content, $options);

        $this->outputGenerator->reset();
        $this->outputGenerator->startDocument($data);

        $contents = array();
        foreach ($data->contents as $contentTypes) {
            foreach ($contentTypes as $contentType) {
                $contents[] = $contentType;
            }
        }

        $this->contentListElementGenerator->generateElement($this->outputGenerator, $contents);

        $filePath = $this->fileSystemHelper->getDir() . $chunkPath;
        $this->fileSystemHelper->save($filePath, $this->outputGenerator->endDocument($data));

        $this->logger->info(sprintf('Generating file: %s', $filePath));
    }
}
