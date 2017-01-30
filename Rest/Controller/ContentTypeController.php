<?php
/**
 * This file is part of the EzSystemRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Controller;

use EzSystems\RecommendationBundle\Rest\Exception\ExportInProgressException;
use EzSystems\RecommendationBundle\Rest\Values\ContentData as ContentDataValue;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Recommendation REST ContentType controller.
 */
class ContentTypeController extends ContentController
{
    /**
     * Prepares content for ContentDataValue class.
     *
     * @param string $contentTypeIdList
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \EzSystems\RecommendationBundle\Rest\Values\ContentData ContentDataValue
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException if the content, version with the given id and languages or content type does not exist
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the user has no access to read content and in case of un-published content: read versions
     */
    public function getContentType($contentTypeIdList, Request $request)
    {
        $options = $this->parseRequest($request);
        $options['contentTypeIds'] = explode(',', $contentTypeIdList);

        $content = $this->contentType->prepareContentByContentTypeIds($options);

        return new ContentDataValue($content, $options);
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
        $options = $this->parseRequest($request, 'export');

        if (file_exists($options['documentRoot'] . '/var/export/.lock')) {
            throw new ExportInProgressException('Export is running');
        }

        $options['contentTypeIdList'] = $contentTypeIdList;

        $optionString = '';
        foreach ($options as $key => $option) {
            $optionString .= !empty($option) ? ' --' . $key . '=' . $option : '';
        }

        $cmd = sprintf('%s/console ezreco:runexport %s --env=prod',
            $this->container->getParameter('kernel.root_dir'),
            $optionString
        );

        exec(
            sprintf(
                '/usr/bin/php -d memory_limit=-1 %s > %s 2>&1 & echo $! > %s',
                $cmd,
                $options['documentRoot'] . '/var/export/.log',
                $options['documentRoot'] . '/var/export/.pid'
            )
        );

        return new JsonResponse([sprintf('Export started at %s', date('Y-m-d H:i:s'))]);
    }
}
