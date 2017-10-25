<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Controller;

use eZ\Publish\Core\REST\Server\Controller;
use EzSystems\RecommendationBundle\Authentication\Authenticator;
use EzSystems\RecommendationBundle\Rest\Content\ContentType;
use Symfony\Component\HttpFoundation\Request;

abstract class BaseController extends Controller
{
    /** @var \EzSystems\RecommendationBundle\Rest\Content\ContentType */
    protected $contentType;

    /** @var \EzSystems\RecommendationBundle\Authentication\Authenticator */
    protected $authenticator;

    /**
     * @param \EzSystems\RecommendationBundle\Rest\Content\ContentType $contentType
     * @param \EzSystems\RecommendationBundle\Authentication\Authenticator $authenticator
     */
    public function __construct(
        ContentType $contentType,
        Authenticator $authenticator
    ) {
        $this->contentType = $contentType;
        $this->authenticator = $authenticator;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $responseType
     *
     * @return array
     */
    protected function parseRequest(Request $request, $responseType = 'http')
    {
        $query = $request->query;

        $requestContentType = $request->headers->get('accept');
        $path = $query->get('path');
        $hidden = (int)$query->get('hidden', 0);
        $image = $query->get('image');
        $siteAccess = $query->get('siteAccess');
        $webHook = $query->get('webHook');
        $transaction = $query->get('transaction');
        $fields = $query->get('fields');

        if (strpos($requestContentType, ',')) {
            list($requestContentType) = explode(',', $requestContentType);
        }

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

        return array(
            'responseType' => $responseType,
            'pageSize' => (int)$query->get('pageSize', $this->contentType->getDefaultPageSize($responseType)),
            'page' => (int)$query->get('page', 1),
            'path' => $path,
            'hidden' => $hidden,
            'image' => $image,
            'siteAccess' => $siteAccess,
            'documentRoot' => $request->server->get('DOCUMENT_ROOT'),
            'schemeAndHttpHost' => $request->getSchemeAndHttpHost(),
            'host' => $request->getSchemeAndHttpHost(),
            'webHook' => $webHook,
            'transaction' => $transaction,
            'lang' => preg_replace('/[^a-zA-Z0-9_-]+/', '', $query->get('lang')),
            'customerId' => $this->contentType->getCustomerId(),
            'licenseKey' => $this->contentType->getLicenseKey(),
            'fields' => $fields,
            'mandatorId' => (int)$query->get('mandatorId', 0),
            'requestContentType' => preg_replace('/[^a-zA-Z0-9_-]+/', '', $requestContentType),
        );
    }
}
