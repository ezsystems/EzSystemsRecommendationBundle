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
        if ( $request->isXmlHttpRequest() ) {

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
                $itemId = $recommendation[ 'itemId' ];
                $recommendedContentIds[] = $itemId;
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

                    // get content name
                    $name = $result->valueObject->contentInfo->name;

                    // get content shortened body
                    $intro = $contentData->getFieldValue( 'intro', $language )->xml->textContent;

                    // get creation date
                    $timestamp = $contentData->getVersionInfo()->creationDate->getTimestamp();

                    // get url
                    $url = $this->generateUrl( $result->valueObject );

                    // get authors
                    $authorData = $contentData->getFieldValue( 'author', $language )->authors;
                    $author = '';
                    foreach ($authorData as $authorId) {
                        $author .= $authorId->name . ', ';
                    }
                    $author = substr( $author, 0, strlen( $author ) - 2 );

                    // get content image
                    $image = $contentData->getFieldValue( 'image', $language )->uri;

                    $content[] = array(
                        'name' => $name,
                        'url' => $url,
                        'image' => $image,
                        'intro' => $intro,
                        'timestamp' => $timestamp,
                        'author' => $author
                    );
                }
            }

            if ( count( $content ) > 0 )
            {
                $status = 'success';
            }
            else
            {
                $status = 'empty';
            }

            return new JsonResponse( array(
                'status' => $status,
                'content' => $content
            ) );

        }
        else
        {
            throw new BadRequestHttpException();
        }
    }
}
