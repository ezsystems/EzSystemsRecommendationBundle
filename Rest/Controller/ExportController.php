<?php
/**
 * This file is part of the EzSystemRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Controller;

use eZ\Publish\Core\REST\Common\Exceptions\NotFoundException;
use EzSystems\RecommendationBundle\Authentication\Authenticator;
use EzSystems\RecommendationBundle\Rest\Content\ContentType;
use EzSystems\RecommendationBundle\Rest\Exception\ExportInProgressException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends BaseController
{
    /** @var string */
    private $kernelRootDir;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $kernelEnvironment;

    /**
     * @param \EzSystems\RecommendationBundle\Rest\Content\ContentType $contentType
     * @param \EzSystems\RecommendationBundle\Authentication\Authenticator $authenticator
     * @param \Psr\Log\LoggerInterface $logger
     * @param string $kernelRootDir
     * @param $kernelEnvironment
     */
    public function __construct(
        ContentType $contentType,
        Authenticator $authenticator,
        LoggerInterface $logger,
        $kernelRootDir,
        $kernelEnvironment
    ) {
        parent::__construct($contentType, $authenticator);

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
            return $response->setStatusCode(401);
        }

        $path = $this->kernelRootDir . '/../web/var/export/';

        if (!file_exists($path . $filePath) || is_dir($path . $filePath)) {
            throw new NotFoundException('File not found.');
        }

        $content = file_get_contents($path . $filePath);

        $response->headers->set('Content-Type', 'mime/type');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filePath);

        $response->setContent($content);

        return $response;
    }

    /**
     * @param string $contentTypeIdList
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *
     * @throws ExportInProgressException
     */
    public function exportContentType($contentTypeIdList, Request $request)
    {
        $response = new JsonResponse();

        if (!$this->authenticator->authenticate()) {
            return $response->setStatusCode(401);
        }

        $options = $this->parseRequest($request, 'export');

        if (file_exists($options['documentRoot'] . '/var/export/.lock')) {
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
            $options['documentRoot'] . '/var/export/.log',
            $options['documentRoot'] . '/var/export/.pid'
        );

        $this->logger->info(sprintf('Running command: %s', $command));

        exec($command);

        return $response->setData([sprintf(
            'Export started at %s',
            date('Y-m-d H:i:s')
        )]);
    }

    private function authenticate($filePath)
    {
        return $this->authenticator->authenticateByFile($filePath) || $this->authenticator->authenticate();
    }
}
