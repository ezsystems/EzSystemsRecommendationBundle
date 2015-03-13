<?php
/**
 * File containing the RecommendationController class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace EzSystems\RecommendationBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use EzSystems\RecommendationBundle\Client\YooChooseNotifier;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;

class RecommendationController extends Controller
{
    public function recommendationsAction( Request $request )
    {
        if ( !$request->isXmlHttpRequest() ) {
            throw new BadRequestHttpException();
        }

        $userId = $this->getRepository()->getCurrentUser()->id;
        $locationId = $request->query->get( 'locationId' );
        $limit = $request->query->get( 'limit' );
        $scenarioId = $request->query->get( 'scenarioId' );

        $notifier = $this->get( 'ez_recommendation.client.yoochoose_notifier' );

        $responseRecommendations = $notifier->getRecommendations(
            $userId, $scenarioId, $locationId, $limit
        );

        $recommendedContentIds = array();

        foreach ( $responseRecommendations[ 'recommendationResponseList' ] as $recommendation ) {
            $recommendedContentIds[] = $recommendation[ 'itemId' ];
        }

        $content = array();

        if ( count( $recommendedContentIds ) > 0 ) {

            $criterion = new Criterion\LogicalAnd( array(
                new Criterion\Visibility( Criterion\Visibility::VISIBLE ),
                new Criterion\ContentId( $recommendedContentIds ),
                new Criterion\ContentTypeIdentifier( array( 'article', 'blog_post' ) )
            ));

            $contentQuery = new LocationQuery();
            $contentQuery->criterion = $criterion;
            $contentQuery->sortClauses = array(
                new SortClause\ContentName()
            );

            $searchService = $this->getRepository()->getSearchService();
            $searchResults = $searchService->findLocations( $contentQuery );

            $language = $this->getRepository()->getContentLanguageService()->getDefaultLanguageCode();

            foreach ($searchResults->searchHits as $result) {
                $contentData = $this->getRepository()->getContentService()->loadContentByContentInfo( $result->valueObject->contentInfo );

                $content[] = array(
                    'name' => $result->valueObject->contentInfo->name,
                    'url' => $this->generateUrl( $result->valueObject ),
                    'image' => $contentData->getFieldValue( 'image', $language )->uri,
                    'intro' => $contentData->getFieldValue( 'intro', $language )->xml->textContent,
                    'timestamp' => $contentData->getVersionInfo()->creationDate->getTimestamp(),
                    'author' => (string) $contentData->getFieldValue( 'author', $language )
                );
            }
        }

        $status = count( $content ) > 0 ? 'success' : 'empty';

        return new JsonResponse( array(
            'status' => $status,
            'content' => $content
        ) );
    }
}
