<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\ValueObjectVisitor;

use eZ\Publish\Core\REST\Common\Output\ValueObjectVisitor;
use eZ\Publish\Core\REST\Common\Output\Generator;
use eZ\Publish\Core\REST\Common\Output\Visitor;

/**
 * ContentData converter for REST output.
 */
class ContentData extends ValueObjectVisitor
{
    /**
     * @param \eZ\Publish\Core\REST\Common\Output\Visitor $visitor
     * @param \eZ\Publish\Core\REST\Common\Output\Generator $generator
     * @param mixed $data
     */
    public function visit(Visitor $visitor, Generator $generator, $data)
    {
        $visitor->setHeader('Content-Type', $generator->getMediaType('ContentList'));

        if (empty($data->contents)) {
            $visitor->setStatus(204);

            return;
        }

        $generator->startObjectElement('contentList');
        $generator->startList('content');

        foreach ($data->contents as $content) {
            $generator->startObjectElement('content');

            $generator->startValueElement('contentId', $content['contentId']);
            $generator->endValueElement('contentId');

            $generator->startValueElement('contentTypeId', $content['contentTypeId']);
            $generator->endValueElement('contentTypeId');

            $generator->startValueElement('identifier', $content['identifier']);
            $generator->endValueElement('identifier');

            $generator->startValueElement('language', $content['language']);
            $generator->endValueElement('language');

            $generator->startValueElement('publishedDate', $content['publishedDate']);
            $generator->endValueElement('publishedDate');

            $generator->startValueElement('author', $content['author']);
            $generator->endValueElement('author');

            $generator->startValueElement('uri', $content['uri']);
            $generator->endValueElement('uri');

            $generator->startValueElement('categoryPath', $content['categoryPath']);
            $generator->endValueElement('categoryPath');

            $generator->startObjectElement('mainLocation');
            $generator->startAttribute('href', $content['mainLocation']['href']);
            $generator->endAttribute('href');
            $generator->endObjectElement('mainLocation');

            $generator->startObjectElement('locations');
            $generator->startAttribute('href', $content['locations']['href']);
            $generator->endAttribute('href');
            $generator->endObjectElement('locations');

            $generator->startList('fields');

            foreach ($content['fields'] as $field) {
                $generator->startHashElement('fields');
                $generator->startValueElement('key', $field['key']);
                $generator->endValueElement('key');
                $generator->startValueElement('value', $field['value']);
                $generator->endValueElement('value');
                $generator->endHashElement('fields');
            }

            $generator->endList('fields');

            $generator->endObjectElement('content');
        }
        $generator->endList('content');
        $generator->endObjectElement('contentList');
    }
}
