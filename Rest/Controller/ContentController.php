<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Controller;

use eZ\Publish\Core\REST\Common\Output\ValueObjectVisitorDispatcher;
use eZ\Publish\Core\REST\Server\Controller as BaseController;
use EzSystems\RecommendationBundle\Rest\Content\ContentType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Recommendation REST Content controller.
 */
class ContentController extends BaseController
{
    /** @var \EzSystems\RecommendationBundle\Rest\Content\ContentType */
    protected $contentType;

    /** @var \eZ\Publish\Core\REST\Common\Output\ValueObjectVisitorDispatcher */
    protected $valueObjectVisitorDispatcher;

    /**
     * @param \EzSystems\RecommendationBundle\Rest\Content\ContentType $contentType
     * @param \eZ\Publish\Core\REST\Common\Output\ValueObjectVisitorDispatcher $valueObjectVisitorDispatcher
     */
    public function __construct(
        ContentType $contentType,
        ValueObjectVisitorDispatcher $valueObjectVisitorDispatcher
    ) {
        $this->contentType = $contentType;
        $this->valueObjectVisitorDispatcher = $valueObjectVisitorDispatcher;
    }

    /**
     * Prepares content for ContentDataValue class.
     *
     * @param string $contentIdList
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \EzSystems\RecommendationBundle\Rest\Values\ContentData
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException if the content, version with the given id and languages or content type does not exist
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the user has no access to read content and in case of un-published content: read versions
     */
    public function getContent($contentIdList, Request $request)
    {
        $options = $this->parseRequest($request);
        $options['contentIdList'] = explode(',', $contentIdList);

        return $this->contentType->get($options);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $responseType
     *
     * @return array
     */
    protected function parseRequest(Request $request, $responseType = 'http')
    {
        $requestContentType = $request->headers->get('accept');

        return array(
            'responseType' => $responseType,
            'pageSize' => (int)$request->get('pageSize', $this->contentType->getDeafultPageSize($responseType)),
            'page' => $request->get('page', 1),
            'path' => $request->get('path'),
            'hidden' => $request->get('hidden', 0),
            'image' => $request->get('image'),
            'siteAccess' => $request->get('sa', $this->contentType->getSiteAccess()->name),
            'documentRoot' => $request->server->get('DOCUMENT_ROOT'),
            'schemeAndHttpHost' => $request->getSchemeAndHttpHost(),
            'webHook' => $request->get('webHook'),
            'transaction' => $request->get('transaction', date('YmdHis', time())),
            'lang' => $request->get('lang'),
            'host' => $request->getSchemeAndHttpHost(),
            'customerId' => $this->contentType->getCustomerId(),
            'licenseKey' => $this->contentType->getLicenseKey(),
            'requestedFields' => $request->get('fields'),
            'requestContentType' => substr($requestContentType, strpos($requestContentType, '+') + 1),
        );
    }
}
