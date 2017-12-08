<?php
/**
 * This file is part of the EzSystemRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Controller;

use EzSystems\RecommendationBundle\Authentication\Authenticator;
use EzSystems\RecommendationBundle\Helper\FileSystem;
use EzSystems\RecommendationBundle\Rest\Exception\ExportInProgressException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    /** @var \EzSystems\RecommendationBundle\Authentication\Authenticator */
    private $authenticator;

    /** @var \EzSystems\RecommendationBundle\Helper\FileSystem */
    private $fileSystem;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var string */
    private $kernelRootDir;

    /** @var string */
    private $kernelEnvironment;

    /**
     * @param \EzSystems\RecommendationBundle\Authentication\Authenticator $authenticator
     * @param \EzSystems\RecommendationBundle\Helper\FileSystem $fileSystem
     * @param \Psr\Log\LoggerInterface $logger
     * @param string $kernelRootDir
     * @param string $kernelEnvironment
     */
    public function __construct(
        Authenticator $authenticator,
        FileSystem $fileSystem,
        LoggerInterface $logger,
        $kernelRootDir,
        $kernelEnvironment
    ) {
        $this->authenticator = $authenticator;
        $this->fileSystem = $fileSystem;
        $this->logger = $logger;
        $this->kernelRootDir = $kernelRootDir;
        $this->kernelEnvironment = $kernelEnvironment;
    }

    /**
     * @param string $filePath
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    public function downloadAction($filePath)
    {
        $response = new Response();

        if (!$this->authenticate($filePath)) {
            return $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
        }

        $content = $this->fileSystem->load($filePath);

        $response->headers->set('Content-Type', 'mime/type');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filePath);

        $response->setContent($content);

        return $response;
    }

    /**
     * @param string $contentTypeIdList
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws ExportInProgressException
     */
    public function runExportAction($contentTypeIdList, Request $request)
    {
        $response = new JsonResponse();

        if (!$this->authenticator->authenticate()) {
            return $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
        }

        $options = $this->parseRequest($request);
        $documentRoot = $options['documentRoot'];
        unset($options['documentRoot']);

        if ($this->fileSystem->isLocked()) {
            $this->logger->warning('Export is running.');
            throw new ExportInProgressException('Export is running');
        }

        $options['contentTypeIdList'] = $contentTypeIdList;

        $optionString = '';
        foreach ($options as $key => $option) {
            $optionString .= !empty($option) ? ' --' . $key . '=' . $option : '';
        }

        $cmd = sprintf('%s/console ezreco:runexport %s --env=%s',
            $this->kernelRootDir,
            escapeshellcmd($optionString),
            $this->kernelEnvironment
        );

        $command = sprintf(
            '%s -d memory_limit=-1 %s > %s 2>&1 & echo $! > %s',
            PHP_BINARY,
            $cmd,
            $documentRoot . '/var/export/.log',
            $documentRoot . '/var/export/.pid'
        );

        $this->logger->info(sprintf('Running command: %s', $command));

        exec($command);

        return $response->setData([sprintf(
            'Export started at %s',
            date('Y-m-d H:i:s')
        )]);
    }

    /**
     * Authenticates the user by file or by configured method.
     *
     * @param string $filePath
     *
     * @return bool
     */
    private function authenticate($filePath)
    {
        return $this->authenticator->authenticateByFile($filePath) || $this->authenticator->authenticate();
    }

    /**
     * Parses the request values.
     *
     * @param Request $request
     *
     * @return array
     */
    private function parseRequest(Request $request)
    {
        $query = $request->query;

        $path = $query->get('path');
        $hidden = (int)$query->get('hidden', 0);
        $image = $query->get('image');
        $siteAccess = $query->get('siteAccess');
        $webHook = $query->get('webHook');
        $transaction = $query->get('transaction');
        $fields = $query->get('fields');
        $customerId = $query->get('customerId');
        $licenseKey = $query->get('licenseKey');

        if (preg_match('/^\/\d+(?:\/\d+)*\/$/', $path) !== 1) {
            $path = null;
        }

        if (preg_match('/^[a-zA-Z0-9\-\_]+$/', $image) !== 1) {
            $image = null;
        }

        if (preg_match('/^[a-zA-Z0-9_-]+$/', $siteAccess) !== 1) {
            $siteAccess = null;
        }

        if (preg_match('/((http|https)\:\/\/)?[a-zA-Z0-9\.\/\?\:@\-_=#]+\.([a-zA-Z0-9\&\.\/\?\:@\-_=#])*/', $webHook) !== 1) {
            $webHook = null;
        }

        if (preg_match('/^[0-9]+$/', $transaction) !== 1) {
            $transaction = (new \DateTime())->format('YmdHisv');
        }

        if (preg_match('/^[a-zA-Z0-9\-\_\,]+$/', $fields) !== 1) {
            $fields = null;
        }

        if (preg_match('/^[a-zA-Z0-9_-]+$/', $customerId) !== 1) {
            $customerId = null;
        }

        if (preg_match('/^[a-zA-Z0-9_-]+$/', $licenseKey) !== 1) {
            $licenseKey = null;
        }

        return array(
            'pageSize' => (int)$query->get('pageSize', null),
            'page' => (int)$query->get('page', 1),
            'path' => $path,
            'hidden' => $hidden,
            'image' => $image,
            'siteAccess' => $siteAccess,
            'documentRoot' => $request->server->get('DOCUMENT_ROOT'),
            'host' => $request->getSchemeAndHttpHost(),
            'webHook' => $webHook,
            'transaction' => $transaction,
            'lang' => preg_replace('/[^a-zA-Z0-9_-]+/', '', $query->get('lang')),
            'fields' => $fields,
            'mandatorId' => (int)$query->get('mandatorId', 0),
            'customerId' => $customerId,
            'licenseKey' => $licenseKey,
        );
    }
}
