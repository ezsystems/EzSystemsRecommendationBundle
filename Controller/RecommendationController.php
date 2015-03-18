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
use \EzSystems\RecommendationBundle\Helper\CriteriaHelper;
use \EzSystems\RecommendationBundle\Helper\LocationHelper;

class RecommendationController
{
    /** @var \eZ\Publish\API\Repository\Repository */
    protected $repository;

    /** @var \EzSystems\RecommendationBundle\Client\RecommendationRequestClient */
    protected $recommender;

    /** @var \eZ\Publish\Core\MVC\Symfony\Routing\ChainRouter */
    protected $router;

    /** @var \EzSystems\RecommendationBundle\Helper\CriteriaHelper */
    protected $criteriaHelper;

    /** @var \EzSystems\RecommendationBundle\Helper\LocationHelper */
    protected $locationHelper;

    public function __construct(RecommendationRequestClient $recommender, Repository $repository, ChainRouter $router, CriteriaHelper $criteriaHelper, LocationHelper $locationHelper)
    {
        $this->recommender = $recommender;
        $this->repository = $repository;
        $this->router = $router;
        $this->criteriaHelper = $criteriaHelper;
        $this->locationHelper = $locationHelper;
    }

    public function recommendationsAction(Request $request, $scenarioId, $locationId, $limit)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $userId = $this->repository->getCurrentUser()->id;

        $recommendationsCollection = $this->recommender->getRecommendations(
            $userId, $scenarioId, $locationId, $limit
        );

        $content = array();

        if (!$recommendationsCollection->isEmpty()) {
            $locationQuery = $this->criteriaHelper->generateContentTypeCriterion($recommendationsCollection->getKeys());
            $searchService = $this->repository->getSearchService();
            $searchResults = $searchService->findLocations($locationQuery);

            $language = $this->repository->getContentLanguageService()->getDefaultLanguageCode();

            foreach ($searchResults->searchHits as $result) {
                $content[] = $this->locationHelper->mapLocationToArray($result, $language);
            }
        }

        $status = count($content) > 0 ? 'success' : 'empty';

        return new JsonResponse(array(
            'status' => $status,
            'content' => $content
        ));
    }
}
