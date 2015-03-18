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

    public function __construct( \EzSystems\RecommendationBundle\Client\RecommendationRequestClient $recommender,
                                 \eZ\Publish\API\Repository\Repository $repository,
                                 \eZ\Publish\Core\MVC\Symfony\Routing\ChainRouter $router,
                                 \EzSystems\RecommendationBundle\Helper\CriteriaHelper $criteriaHelper,
                                 \EzSystems\RecommendationBundle\Helper\LocationHelper $locationHelper )
    {
        $this->recommender = $recommender;
        $this->repository = $repository;
        $this->router = $router;
        $this->criteriaHelper = $criteriaHelper;
        $this->locationHelper = $locationHelper;
    }

    public function recommendationsAction( Request $request, $scenarioId, $locationId, $limit )
    {
        if ( !$request->isXmlHttpRequest() )
        {
            throw new BadRequestHttpException();
        }

        $userId = $this->repository->getCurrentUser()->id;

        $recommendationsCollection = $this->recommender->getRecommendations(
            $userId, $scenarioId, $locationId, $limit
        );

        $content = array();

        if ( !$recommendationsCollection->isEmpty() )
        {
            $contentQuery = $this->criteriaHelper->generateContentTypeCriterion( $recommendationsCollection->getKeys() );
            $searchService = $this->repository->getSearchService();
            $searchResults = $searchService->findLocations( $contentQuery );

            $language = $this->repository->getContentLanguageService()->getDefaultLanguageCode();

            foreach ($searchResults->searchHits as $result)
            {
                $content[] = $this->locationHelper->mapLocationToArray( $result, $language );
            }
        }

        $status = count( $content ) > 0 ? 'success' : 'empty';

        return new JsonResponse( array(
            'status' => $status,
            'content' => $content
        ) );
    }
}
