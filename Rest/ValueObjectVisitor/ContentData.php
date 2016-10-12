<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\ValueObjectVisitor;

use eZ\Publish\Core\REST\Common\Output\ValueObjectVisitor;
use eZ\Publish\Core\REST\Common\Output\Generator;
use eZ\Publish\Core\REST\Common\Output\Visitor;
use EzSystems\RecommendationBundle\Rest\Exception\ResponseClassNotImplementedException;

/**
 * ContentData converter for REST output.
 */
class ContentData extends ValueObjectVisitor
{
    private $responseRenderers = [];

    /**
     * @param array $responseRenderers
     */
    public function setResponseRendereres($responseRenderers)
    {
        $this->responseRenderers = $responseRenderers;
    }

    /**
     * @param \eZ\Publish\Core\REST\Common\Output\Visitor $visitor
     * @param \eZ\Publish\Core\REST\Common\Output\Generator $generator
     * @param mixed $data
     * @return mixed
     * @throws \EzSystems\RecommendationBundle\Rest\Exception\ResponseClassNotImplementedException
     */
    public function visit(Visitor $visitor, Generator $generator, $data)
    {
        $visitor->setHeader('Content-Type', $generator->getMediaType('ContentList'));

        if (empty($data->contents)) {
            $visitor->setStatus(204);

            return;
        }

        if (!isset($this->responseRenderers[$data->options['responseType']])) {
            throw new ResponseClassNotImplementedException(sprintf('Renderer for %s response not implemented.', $data->options['responseType']));
        }

        return $this->responseRenderers[$data->options['responseType']]->render($generator, $data);
    }
}
