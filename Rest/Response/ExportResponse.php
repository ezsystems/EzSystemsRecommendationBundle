<?php
/**
 * This file is part of the EzSystemRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Response;

use eZ\Publish\Core\REST\Common\Output\Generator;
use EzSystems\RecommendationBundle\Rest\Exception\ExportInProgressException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class ExportResponse extends Response
{
    /** @var string */
    private $apiEndpoint;

    /**
     * @param string $apiEndpoint
     */
    public function setApiEndpoint($apiEndpoint)
    {
        $this->apiEndpoint = $apiEndpoint;
    }

    public function render(Generator $generator, $data)
    {
        ini_set('max_execution_time', 0);
        if (file_exists($data->options['documentRoot'] . '/var/export/.lock')) {
            throw new ExportInProgressException('Export is running');
        }

        $urls = [];
        $chunkDirPath = $data->options['documentRoot'] . '/var/export' . $data->options['chunkDir'];

        foreach ($data->contents as $contentTypeId => $items) {
            $chunks = array_chunk($items, $data->options['pageSize']);

            touch($data->options['documentRoot'] . '/var/export/.lock');

            foreach ($chunks as $id => $chunk) {
                $chunkPath = $chunkDirPath . $contentTypeId . $id;

                $generator->reset();
                $generator->startDocument($chunk);

                $this->contentListElementGenerator->generateElement($generator, $chunk);

                file_put_contents($chunkPath, $generator->endDocument($chunk));
                $urls[$contentTypeId][] = sprintf('%s/api/ezp/v2/ez_recommendation/v1/exportDownload%s%s', $data->options['host'], $data->options['chunkDir'], $contentTypeId . $id);
            }
        }

        unlink($data->options['documentRoot'] . '/var/export/.lock');

        echo $this->sendYCResponse($urls, $data->options, $chunkDirPath);

        $generator->reset();

        return $generator;
    }

    /**
     * @param string $documentRoot
     *
     * @return false|string
     */
    public function createChunkDir($documentRoot)
    {
        $chunkDir = date('/Y/m/d/H/i/', time());

        $dir = $documentRoot . '/var/export' . $chunkDir;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        return $chunkDir;
    }

    /**
     * @param array $urls
     * @param array $options
     * @param string $chunkDirPath
     *
     * @return \Psr\Http\Message\StreamInterface|string
     */
    public function sendYCResponse(array $urls, $options, $chunkDirPath)
    {
        $guzzle = new Client(array(
            'base_uri' => $options['webHook'],
        ));

        $events = array();

        foreach ($urls as $contentTypeId => $urlList) {
            $events[] = array(
                'action' => 'FULL',
                'format' => 'EZ',
                'contentTypeId' => $contentTypeId,
                'lang' => $options['lang'],
                'uri' => $urlList,
                'credentials' => $this->secureDir($chunkDirPath),
            );
        }

        try {
            $response = $guzzle->send(
                new Request(
                    'POST',
                    '',
                    array(
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Basic ' . base64_encode($options['customerId'] . ':' . $options['licenseKey']),
                    ),
                    json_encode(array(
                        'transaction' => $options['transaction'],
                        'events' => $events,
                    ))
                )
            )->getBody();
        } catch (\Exception $e) {
            return new JsonResponse(array(
                $e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(),
            ));
        }

        return $response;
    }

    /**
     * @param string $dir
     *
     * @return string
     */
    private function secureDir($dir)
    {
        if ($this->authenticationMethod == 'none') {
            return array('none');
        } elseif ($this->authenticationMethod == 'user') {
            return array(
                'login' => $this->authenticationLogin,
                'password' => $this->authenticationPassword,
            );
        }

        $user = 'yc';
        $password = substr(md5(microtime()), 0, 10);

        file_put_contents($dir . '.htpasswd', sprintf('%s:%s', $user, crypt($password, md5($password))));

        return array(
            'login' => $user,
            'password' => $password,
        );
    }
}
