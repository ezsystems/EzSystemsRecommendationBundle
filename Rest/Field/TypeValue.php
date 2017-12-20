<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\RecommendationBundle\Rest\Field;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver;
use eZ\Bundle\EzPublishCoreBundle\Imagine\AliasGenerator as ImageVariationService;
use eZ\Publish\Core\MVC\Exception\SourceImageNotFoundException;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Bundle\EzPublishCoreBundle\FieldType\RichText\Converter\Html5 as RichHtml5;
use eZ\Publish\Core\FieldType\XmlText\Converter\Html5 as XmlHtml5;
use LogicException;

class TypeValue
{
    /** @var \eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver */
    private $configResolver;

    /** @var \eZ\Bundle\EzPublishCoreBundle\Imagine\AliasGenerator */
    protected $imageVariationService;

    /** @var \eZ\Bundle\EzPublishCoreBundle\FieldType\RichText\Converter\Html5 */
    private $richHtml5Converter;

    /** @var \eZ\Publish\Core\FieldType\XmlText\Converter\Html5 */
    private $xmlHtml5Converter;

    /**
     * @param \eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver $configResolver
     * @param \eZ\Bundle\EzPublishCoreBundle\Imagine\AliasGenerator $imageVariationService
     * @param \eZ\Bundle\EzPublishCoreBundle\FieldType\RichText\Converter\Html5 $richHtml5Converter
     * @param \eZ\Publish\Core\FieldType\XmlText\Converter\Html5 $xmlHtml5Converter
     */
    public function __construct(
        ConfigResolver $configResolver,
        ImageVariationService $imageVariationService,
        RichHtml5 $richHtml5Converter,
        XmlHtml5 $xmlHtml5Converter = null
    ) {
        $this->configResolver = $configResolver;
        $this->imageVariationService = $imageVariationService;
        $this->richHtml5Converter = $richHtml5Converter;
        $this->xmlHtml5Converter = $xmlHtml5Converter;
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
        try {
            $xml = $this->xmlHtml5Converter->convert($field->value->xml);
        } catch (LogicException $e) {
            $xml = $field->value->xml->saveHTML();
        }

        return '<![CDATA[' . $xml . ']]>';
    }

    /**
     * Method for parsing ezrichtext field.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Field $field
     *
     * @return string
     */
    public function ezrichtext(Field $field)
    {
        return '<![CDATA[' . $this->richHtml5Converter->convert($field->value->xml)->saveHTML() . ']]>';
    }

    /**
     * Method for parsing ezimage field.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Field $field
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     * @param string $language
     * @param string $imageFieldIdentifier
     * @param array $options
     *
     * @return string
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidVariationException
     * @throws \eZ\Publish\Core\MVC\Exception\SourceImageNotFoundException
     */
    public function ezimage(Field $field, Content $content, $language, $imageFieldIdentifier, $options = [])
    {
        if (!isset($field->value->id)) {
            return '';
        }

        $variations = $this->configResolver->getParameter('image_variations');
        $variation = 'original';

        if ((!empty($options['image'])) && in_array($options['image'], array_keys($variations))) {
            $variation = $options['image'];
        }

        try {
            $imageVariation = $this->imageVariationService->getVariation($field, $content->versionInfo, $variation);

            return parse_url($imageVariation->uri, PHP_URL_PATH);
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
     * @param array $options
     *
     * @return string
     */
    public function ezobjectrelation(Field $field, Content $content, $language, $imageFieldIdentifier, $options = [])
    {
        $fields = $content->getFieldsByLanguage($language);
        foreach ($fields as $type => $field) {
            if ($type == $imageFieldIdentifier) {
                return $this->ezimage($field, $content, $language, $imageFieldIdentifier, $options);
            }
        }

        return '';
    }
}
