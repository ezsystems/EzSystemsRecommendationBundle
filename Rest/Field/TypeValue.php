<?php
/**
 * This file is part of the EzSystemsRecommendationBundle package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Field;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver;
use eZ\Bundle\EzPublishCoreBundle\Imagine\AliasGenerator as ImageVariationService;
use eZ\Publish\Core\MVC\Exception\SourceImageNotFoundException;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Publish\Core\FieldType\XmlText\Converter\Html5;
use Symfony\Component\HttpFoundation\RequestStack;

class TypeValue
{
    /** @var \Symfony\Component\HttpFoundation\RequestStack */
    private $request;

    /** @var \eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver */
    private $configResolver;

    /** @var \eZ\Publish\Core\FieldType\XmlText\Converter\Html5 */
    private $html5Converter;

    /** @var \eZ\Bundle\EzPublishCoreBundle\Imagine\AliasGenerator */
    protected $imageVariationService;

    /**
     * @param \Symfony\Component\HttpFoundation\RequestStack $request
     * @param \eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver $configResolver
     * @param \eZ\Publish\Core\FieldType\XmlText\Converter\Html5 $html5Converter
     * @param \eZ\Bundle\EzPublishCoreBundle\Imagine\AliasGenerator $imageVariationService
     */
    public function __construct(
        RequestStack $request,
        ConfigResolver $configResolver,
        Html5 $html5Converter,
        ImageVariationService $imageVariationService
    ) {
        $this->request = $request;
        $this->configResolver = $configResolver;
        $this->html5Converter = $html5Converter;
        $this->imageVariationService = $imageVariationService;
    }

    /**
     * Default field value parsing.
     *
     * @param string $fieldName
     * @param mixed $args
     *
     * @return string
     */
    public function __call($fieldName, $args)
    {
        $field = array_shift($args);

        return (string) $field->value;
    }

    /**
     * Method for parsing ezxmltext field.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Field $field
     *
     * @return string
     */
    public function ezxmltext(Field $field)
    {
        return  $this->html5Converter->convert($field->value->xml);
    }

    /**
     * Method for parsing ezimage field.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Field $field
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     *
     * @return string
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidVariationException
     * @throws \eZ\Publish\Core\MVC\Exception\SourceImageNotFoundException
     */
    public function ezimage(Field $field, Content $content)
    {
        if (!isset($field->value->id)) {
            return '';
        }

        $variations = $this->configResolver->getParameter('image_variations');
        $variation = 'original';
        $requestedVariation = $this->request->getCurrentRequest()->get('image');

        if ((null !== $requestedVariation) || in_array($requestedVariation, array_keys($variations))) {
            $variation = $requestedVariation;
        }

        try {
            return $this->imageVariationService->getVariation($field, $content->versionInfo, $variation)->uri;
        } catch (SourceImageNotFoundException $exception) {
            return '';
        }
    }

    /**
     * Method for parsing ezobjectrelation field.
     * For now related fields refer to images.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Field $field
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     * @param string $language
     * @param string $imageFieldIdentifier
     *
     * @return string
     */
    public function ezobjectrelation(Field $field, Content $content, $language, $imageFieldIdentifier)
    {
        $fields = $content->getFieldsByLanguage($language);
        foreach ($fields as $type => $field) {
            if ($type == $imageFieldIdentifier) {
                return $this->ezimage($field, $content);
            }
        }

        return '';
    }
}
