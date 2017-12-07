<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Controller;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\Core\REST\Server\Controller as BaseController;
use eZ\Publish\Core\REST\Server\Exceptions\BadRequestException;
use EzSystems\RecommendationBundle\Helper\Text;
use EzSystems\RecommendationBundle\Helper\SiteAccess;
use EzSystems\RecommendationBundle\Rest\Content\Content;
use EzSystems\RecommendationBundle\Rest\Values\ContentData as ContentDataValue;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use InvalidArgumentException;

/**
 * Recommendation REST Content controller.
 */
class ContentController extends BaseController
{
    /** @var \eZ\Publish\Core\Repository\LocationService */
    protected $locationService;

    /** @var \eZ\Publish\Core\Repository\SearchService */
    protected $searchService;

    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    protected $configResolver;

    /** @var \EzSystems\RecommendationBundle\Rest\Content\Content */
    protected $content;

    /** @var \EzSystems\RecommendationBundle\Helper\SiteAccess */
    protected $siteAccessHelper;

    /**
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \eZ\Publish\API\Repository\SearchService $searchService
     * @param \EzSystems\RecommendationBundle\Rest\Content\Content $content
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     * @param \EzSystems\RecommendationBundle\Helper\SiteAccess $siteAccessHelper
     */
    public function __construct(
        LocationService $locationService,
        SearchService $searchService,
        ConfigResolverInterface $configResolver,
        Content $content,
        SiteAccess $siteAccessHelper
    ) {
        $this->locationService = $locationService;
        $this->searchService = $searchService;
        $this->configResolver = $configResolver;
        $this->content = $content;
        $this->siteAccessHelper = $siteAccessHelper;
    }

    /**
     * Prepares content for ContentDataValue class.
     *
     * @param string $contentIdList
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return ContentDataValue
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException if the content, version with the given id and languages or content type does not exist
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException If the user has no access to read content and in case of un-published content: read versions
     * @throws \eZ\Publish\Core\REST\Server\Exceptions\BadRequestException If incorrect $contentTypeIdList value is given
     */
    public function getContentAction($contentIdList, Request $request)
    {
        try {
            $contentIds = Text::getIdListFromString($contentIdList);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestException('Bad Request', 400);
        }

        $options = $this->parseParameters($request->query, ['lang', 'hidden']);
        $lang = $options->get('lang');

        $criteria = array(new Criterion\ContentId($contentIds));

        if (!$options->get('hidden')) {
            $criteria[] = new Criterion\Visibility(Criterion\Visibility::VISIBLE);
        }

        if ($lang) {
            $criteria[] = new Criterion\LanguageCode($lang);
        }

        $query = new Query();
        $query->query = new Criterion\LogicalAnd($criteria);

        $contentItems = $this->searchService->findContent(
            $query,
            (!empty($lang) ? array('languages' => array($lang)) : array())
        )->searchHits;

        $contentOptions = $this->parseParameters($request->query, ['lang', 'fields', 'image']);
        $content = $this->content->prepareContent(array($contentItems), $contentOptions);

        return new ContentDataValue($content);
    }

    /**
     * Returns parameters specified in $allowedParameters.
     *
     * @param ParameterBag $parameters
     * @param array $allowedParameters
     * @return ParameterBag
     */
    protected function parseParameters(ParameterBag $parameters, array $allowedParameters)
    {
        $parsedParameters = new ParameterBag();

        foreach ($allowedParameters as $parameter) {
            if ($parameters->has($parameter)) {
                $parsedParameters->set($parameter, $parameters->get($parameter));
            }
        }

        return $parsedParameters;
    }
}
