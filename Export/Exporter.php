<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Export;

use Exception;
use eZ\Publish\API\Repository\LocationService;
use EzSystems\RecommendationBundle\Authentication\Authenticator;
use EzSystems\RecommendationBundle\Rest\Content\Content;
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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Exporter
{
    /** @var \eZ\Publish\Api\Repository\SearchService */
    private $searchService;

    /** @var \eZ\Publish\Api\Repository\ContentTypeService */
    private $contentTypeService;

    /** @var \eZ\Publish\Api\Repository\LocationService */
    private $locationService;

    /** @var \EzSystems\RecommendationBundle\Authentication\Authenticator */
    private $authenticator;

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
     * @param \EzSystems\RecommendationBundle\Authentication\Authenticator $authenticator
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
        Authenticator $authenticator,
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
        $this->authenticator = $authenticator;
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
     * @param OutputInterface $output
     *
     * @return \EzSystems\RecommendationBundle\Rest\Values\ContentData|void
     *
     * @throws \EzSystems\RecommendationBundle\Rest\Exception\ExportInProgressException
     * @throws \Exception
     */
    public function runExport(array $options, OutputInterface $output)
    {
        $options = $this->validate($options);

        $options['contentTypeIds'] = Text::getIdListFromString($options['contentTypeIdList']);
        $chunkDir = $this->fileSystemHelper->createChunkDir();

        $languages = $this->getLanguages($options);
        $options['languages'] = $languages;

        try {
            $this->fileSystemHelper->lock();
            $urls = $this->generateFiles($languages, $chunkDir, $options, $output);
            $this->fileSystemHelper->unlock();

            $credentials = $this->authenticator->getCredentials();
            $securedDirCredentials = $this->fileSystemHelper->secureDir($chunkDir, $credentials);

            $response = $this->exportNotifier->sendRecommendationResponse($urls, $options, $securedDirCredentials);
            $this->logger->info(sprintf('eZ Recommendation Response: %s', $response));
            $output->writeln('Done');
        } catch (Exception $e) {
            $this->logger->error(sprintf('Error while generating export: %s', $e->getMessage()));
            $this->fileSystemHelper->unlock();

            throw $e;
        }
    }

    /**
     * Validates required options.
     *
     * @param array $options
     *
     * @return array
     */
    private function validate(array $options)
    {
        if (array_key_exists('mandatorId', $options)) {
            $options['mandatorId'] = (int) $options['mandatorId'];
        }

        list($customerId, $licenseKey) =
            $this->siteAccessHelper->getRecommendationServiceCredentials($options['mandatorId'], $options['siteaccess']);

        $options = array_filter($options, function ($val) {
            return $val !== null;
        });

        $resolver = new OptionsResolver();
        $resolver->setRequired(['contentTypeIdList', 'host', 'webHook', 'transaction']);
        $resolver->setDefined(array_keys($options));
        $resolver->setDefaults([
            'transaction' => (new \DateTime())->format('YmdHis') . rand(111, 999),
            'customerId' => $customerId,
            'licenseKey' => $licenseKey,
            'mandatorId' => null,
            'siteaccess' => null,
            'lang' => null,
        ]);

        return $resolver->resolve($options);
    }

    /**
     * Returns languages list.
     *
     * @param array $options
     *
     * @return array
     */
    private function getLanguages($options)
    {
        if (!empty($options['lang'])) {
            return Text::getArrayFromString($options['lang']);
        }

        return $this->siteAccessHelper->getLanguages($options['mandatorId'], $options['siteaccess']);
    }

    /**
     * Generate export files.
     *
     * @param array $languages
     * @param string $chunkDir
     * @param array $options
     * @param OutputInterface $output
     *
     * @return array
     */
    private function generateFiles($languages, $chunkDir, array $options, OutputInterface $output)
    {
        $urls = array();

        $output->writeln(sprintf('Exporting %s content types', count($options['contentTypeIds'])));

        foreach ($options['contentTypeIds'] as $contentTypeId) {
            $contentTypeCurrentName = null;

            foreach ($languages as $lang) {
                $options['lang'] = $lang;

                $count = $this->countContentItemsByContentTypeId($contentTypeId, $options);

                $info = sprintf('Fetching %s items of contentTypeId %s (language: %s)', $count, $contentTypeId, $lang);
                $output->writeln($info);
                $this->logger->info($info);

                $contentTypeName = $this->contentTypeService->loadContentType($contentTypeId)->getName($lang);

                if ($contentTypeName !== null) {
                    $contentTypeCurrentName = $contentTypeName;
                }

                for ($i = 1; $i <= ceil($count / $options['pageSize']); ++$i) {
                    $filename = sprintf('%d_%s_%d', $contentTypeId, $lang, $i);
                    $chunkPath = $chunkDir . $filename;
                    $options['page'] = $i;

                    $output->writeln(sprintf(
                        'Fetching content from database for contentTypeId: %s, language: %s, chunk: #%s',
                        $contentTypeId,
                        $lang,
                        $i
                    ));

                    $contentItems = $this->getContentItems($contentTypeId, $options);
                    $parameters = new ParameterBag($options);

                    $output->writeln(sprintf(
                        'Preparing content for contentTypeId: %s, language: %s, amount: %s, chunk: #%s',
                        $contentTypeId,
                        $lang,
                        count($contentItems),
                        $i
                    ));

                    $content = $this->content->prepareContent(array($contentTypeId => $contentItems), $parameters, $output);

                    unset($contentItems);

                    $output->writeln(sprintf(
                        'Generating file for contentTypeId: %s, language: %s, chunk: #%s',
                        $contentTypeId,
                        $lang,
                        $i
                    ));

                    $this->generateFile($content, $chunkPath, $options);

                    unset($content);

                    $url = sprintf(
                        '%s/api/ezp/v2/ez_recommendation/v1/exportDownload/%s%s',
                        $options['host'], $chunkDir, $filename
                    );

                    $info = sprintf('Generating url: %s', $url);
                    $output->writeln($info);
                    $this->logger->info($info);

                    $urls[$contentTypeId][$lang]['urlList'][] = $url;
                    $urls[$contentTypeId][$lang]['contentTypeName'] = $contentTypeCurrentName;
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
        $criteria = $this->generateCriteria($contentTypeId, $options);

        $query = new Query();
        $query->query = new Criterion\LogicalAnd($criteria);
        $query->limit = 0;

        return $this->searchService->findContent(
            $query,
            (!empty($options['lang']) ? array('languages' => array($options['lang'])) : array())
        )->totalCount;
    }

    /**
     * @param int $contentTypeId
     * @param array $options
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Search\SearchHit[]
     */
    private function getContentItems($contentTypeId, array $options)
    {
        $offset = $options['page'] * $options['pageSize'] - $options['pageSize'];
        $criteria = $this->generateCriteria($contentTypeId, $options);

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
     * Generates criteria for search query.
     *
     * @param int $contentTypeId
     * @param array $options
     *
     * @return array
     */
    private function generateCriteria($contentTypeId, array $options)
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

        $criteria[] = $this->generateSubtreeCriteria($options['mandatorId'], $options['siteaccess']);

        return $criteria;
    }

    /**
     * Generates Criterions based on mandatorId or requested siteAccess.
     *
     * @param null|int $mandatorId
     * @param null|string $siteAccess
     *
     * @return Criterion\LogicalOr
     */
    private function generateSubtreeCriteria($mandatorId = null, $siteAccess = null)
    {
        $siteAccesses = $this->siteAccessHelper->getSiteAccesses($mandatorId, $siteAccess);

        $subtreeCriteria = [];
        $rootLocations = $this->siteAccessHelper->getRootLocationsBySiteAccesses($siteAccesses);
        foreach ($rootLocations as $rootLocationId) {
            $subtreeCriteria[] = new Criterion\Subtree($this->locationService->loadLocation($rootLocationId)->pathString);
        }

        return new Criterion\LogicalOr($subtreeCriteria);
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

        unset($contents);

        $filePath = $this->fileSystemHelper->getDir() . $chunkPath;
        $this->fileSystemHelper->save($filePath, $this->outputGenerator->endDocument($data));

        unset($data);

        $this->logger->info(sprintf('Generating file: %s', $filePath));
    }
}
