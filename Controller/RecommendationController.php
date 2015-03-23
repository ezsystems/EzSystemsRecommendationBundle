<?php
/**
 * File containing the RecommendationController class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace EzSystems\RecommendationBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use \EzSystems\RecommendationBundle\Client\RecommendationRequestClient;
use \eZ\Publish\API\Repository\Repository;
use \eZ\Publish\Core\MVC\Symfony\Routing\ChainRouter;
use \EzSystems\RecommendationBundle\Criteria\Content;
use \EzSystems\RecommendationBundle\Converter\LocationConverter;

class RecommendationController
{
    /** @var \eZ\Publish\API\Repository\Repository */
    protected $repository;

    /** @var \EzSystems\RecommendationBundle\Client\RecommendationRequestClient */
    protected $recommender;

    /** @var \eZ\Publish\Core\MVC\Symfony\Routing\ChainRouter */
    protected $router;

    /** @var \EzSystems\RecommendationBundle\Criteria\Content */
    protected $contentCriteria;

    /** @var \EzSystems\RecommendationBundle\Converter\LocationConverter */
    protected $locationConverter;

    public function __construct(RecommendationRequestClient $recommender, Repository $repository, ChainRouter $router, Content $contentCriteria, LocationConverter $locationConverter)
    {
        $this->recommender = $recommender;
        $this->repository = $repository;
        $this->router = $router;
        $this->locationConverter = $locationConverter;
        $this->contentCriteria = $contentCriteria;
    }

    public function recommendationsAction(Request $request, $scenarioId, $contentId, $limit)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $userId = $this->repository->getCurrentUser()->id;

        $recommendationsCollection = $this->recommender->getRecommendations(
            $userId, $scenarioId, $contentId, $limit
        );

        $content = array();

        if (!$recommendationsCollection->isEmpty()) {
            $locationQuery = $this->contentCriteria->generate($recommendationsCollection->getKeys());
            $searchService = $this->repository->getSearchService();
            $searchResults = $searchService->findLocations($locationQuery);

            $language = $this->repository->getContentLanguageService()->getDefaultLanguageCode();

            foreach ($searchResults->searchHits as $result) {
                $content[] = $this->locationConverter->toArray($result, $language);
            }
        }

        $status = count($content) > 0 ? 'success' : 'empty';

        return new JsonResponse(array(
            'status' => $status,
            'content' => $content
        ));
    }
}
