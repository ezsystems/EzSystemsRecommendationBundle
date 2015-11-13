# Recommendation Bundle
This bundle integrates Recommendation services into eZ Platform. It supports the [YOOCHOOSE](https://yoochoose.com/) recommender service.

**Support** Stable versions of this bundle is officially supported by eZ as of version 1.0.0, for users with a eZ Enterprise and YOOCHOOSE subscription.

## Requirements

- PHP 5.4.4 *or higher PHP 5.x version*
- Symfony 2.6 *or higher Symfony 2.x version*
- eZ Publish 5.4.1+ or eZ Platform/Studio 2015.01 or above, with the REST API configured to use sessions and publicly open to the YOOCHOOSE servers.
- A YOOCHOOSE subscription

This bundle is independent from legacy's ezrecommendation extension, and doesn't require it.

## Installation

This package is available via composer, so the instructions below are similar to how you install any other open source Symfony Bundle.

Run the following from your eZ Publish installation root *(here with most recent 1.0.x release)*:
```bash
php composer.phar require ezsystems/recommendation-bundle:~1.0.0
```

Enable the bundle in `ezpublish/EzPublishKernel.php`:
```php
$bundles = array(
    // existing bundles
    new EzSystems\RecommendationBundle\EzSystemsRecommendationBundle()
);

```
Import additional routing by adding following lines to your `routing.yml` file:

```yaml
recommendationBundleRestRoutes:
    resource: "@EzSystemsRecommendationBundle/Resources/config/routing_rest.yml"
    prefix:   %ezpublish_rest.path_prefix%
```
Keep in mind, that legacy support is disabled by default. To enable legacy search engine (requires `ezpublish-kernel` bundle) uncomment these lines in bundle `services.yml`:
```yaml
#    ez_recommendation.legacy.search_engine:
#        class: ezpSearchEngine
#        factory_class: EzSystems\RecommendationBundle\eZ\Publish\LegacySearch\LegacySearchFactory
#        factory_method: build
#        arguments: [@ezpublish_legacy.kernel]
#
#    ez_recommendation.legacy.recommendation_search_engine:
#        class: EzSystems\RecommendationBundle\eZ\Publish\LegacySearch\RecommendationLegacySearchEngine
#        arguments:
#            - @ez_recommendation.client.yoochoose_notifier
#            - @ez_recommendation.legacy.search_engine
#
#    ez_recommendation.legacy.search_configuration_mapper:
#        class: EzSystems\RecommendationBundle\eZ\Publish\LegacySearch\ConfigurationMapper
#        arguments:
#            - @ez_recommendation.legacy.recommendation_search_engine
#            - @ezpublish.siteaccess
#        tags:
#            - { name: kernel.event_subscriber }
```

## Configuration

The bundle's configuration is siteaccess aware. This is an example of settings *(config.yml)*:
```yaml
ez_recommendation:
    system:
        default:
            yoochoose:
                customer_id: "12345"
                license_key: "1234-5678-9012-3456-7890"
            server_uri: "http://example.com"
            recommender:
                included_content_types: ["blog", "article"]
```

Parameter                          | Description
-----------------------------------|----------------------------
yoochoose.customer_id              | Your YOOCHOOSE customer ID.
yoochoose.license_key              | Your YOOCHOOSE license key.
server_uri                         | The URI your site's REST API can be accessed from.
recommender.included_content_types | Content Types on which the tracking script will be shown. See Usage/Tracking further below for more information.

### Custom author and image field mapping

If content's author or image are stored in different field, you can specify it in __parameters.yml__

Format for `ez_recommendation.field_identifiers`:

```yaml
    ez_recommendation.field_identifiers:
        {field fetched by controller (image or author)}
             {content type}: {field with value}
```

Actual example:

```yaml
    ez_recommendation.field_identifiers:
         author:
             article: authors
         image:
             article: thumbnail
             blog_post: main_image
```

### Advanced configuration

You can select advanced options for YOOCHOOSE backend using the following settings:

```yaml
ez_recommendation:
    api_endpoint: 'https://admin.yoochoose.net'
    recommender:
        api_endpoint: 'http://reco.yoochoose.net'
        consume_timeout: 20
    tracking:
        api_endpoint: 'http://event.yoochoose.net'
        script_url: 'cdn.yoochoose.net/yct.js'
```

Changing any of these parameters without a valid reason will break all calls to YOOCHOOSE. It can be useful to test the API by mocking the service, or if you have a hosted version of YOOCHOOSE Recommendation service.

## Usage

### Initial setup

Your content structure must be mapped to the YOOCHOOSE domain model. This must be done in collaboration with YOOCHOOSE.

### Indexing

**Public** content is automatically indexed. When necessary, eZ Publish will notify YOOCHOOSE of changes to content. Initial import is to be managed with your YOOCHOOSE sales representative. Note that your server's REST API will need to be open to the YOOCHOOSE servers for indexing to be possible.

### Tracking

Events from the site needs to be sent to YOOCHOOSE so that recommendations can be adapted to visitors. Tracking can be setup in multiple ways, depending on anyone's constraints.

`EzRecommendationBundle` delivers Twig extension which helps integrate tracking functionality into your site. All you need to do is place small snippet code somewhere in the HEAD section of your header template (if your bundle is built on top of the DemoBundle this is `page_head.html.twig`):

```twig
{% if content is defined %}
    {{ yc_track_user(content.id) }}
{% endif %}
```

Next step is to configure settings under the `recommender.included_content_types` parameter (see: `default_settings.yml` file delivered with this bundle).

Here you can define for which content types tracking script will be shown.

You can find more information on the YOOCHOOSE documentation, about [tracking in general](https://doc.yoochoose.net/display/PUBDOC/1.+Tracking+Events), and about the [generic asynchronous javascript tracker](https://doc.yoochoose.net/display/PUBDOC/Tracking+with+yc.js).

Additionaly, in case of missing content owner Id, there's option in `default_settings.yml` to set up default content author:
```yaml
    ez_recommendation.default.author_id: 14   # ID: 14 is default ID of admin user
```

### Displaying

In order to allow displaying recommendations on your site you must add portions of scripts which will integrate recommender engine with your site.

Implementation is very easy and can be performed in just a few steps (assuming that `EzRecommendationBundle` is properly configured and enabled in `EzPublishKernel.php`):

* add additional JavaScript assets to your header template (if your bundle is built on top of the DemoBundle this is  `page_head_script.html.twig`):

```twig
{% javascripts
    ...

    '%kernel.root_dir%/../vendor/components/handlebars.js/handlebars.min.js'
    '@EzSystemsRecommendationBundle/Resources/public/js/recommendationtemplaterenderer.js'
    '@EzSystemsRecommendationBundle/Resources/public/js/recommendationtemplatehelper.js'
    '@EzSystemsRecommendationBundle/Resources/public/js/recommendationrestclient.js'
%}
```

* place dedicated Twig helper in place where you want to display recommendations *(see further below for example)*:

```twig
{{ yc_show_recommendations(
    contentId = content.id,
    scenario = '',
    limit = '',
    contentType = '',
    template = '',
    fields = []
) }}
```

Parameter meanings (all bellow are required):

Parameter       | Type   | Description
----------------|--------|------------
`contentId`     | int    | this is in content based views normally the twig variable holding the content id (we want to get recommendations for)
`scenario`      | string | scenario used to display recommendations, you can create one at YOOCHOOSE dashboard
`limit`         | int    | how many recommendations will be shown?
`contentType`   | string | content type values you are expecting in response
`template`      | string | HandleBars template name (your templates are stored under `RecommendationBundle/Resources/views` directory. Take a look on `default.html.twig` file which includes default template that can be used to prepare customised version)
`fields`        | array  | here you can define which fields are required and will be requested from the recommender engine. These field names are also used inside HandleBars templates

Sample integration should look like below:

```twig
{{ yc_show_recommendations(
    contentId = content.id,
    scenario = 'popular',
    limit = 5,
    contentType = 'article',
    template = 'default',
    fields = ['ez_publishedDate', 'ez_url', 'title', 'image', 'author', 'intro']
) }}
```

You can also bypass named arguments using standard value passing as arguments.

#### The item id
The ItemId mentioned throughout this documentation is usually set to the viewed ContentId. Depending on requirements, it can be set to a different value, in collaboration with YOOCHOOSE.

## Troubleshooting

Most operations are logged via the `ez_recommendation` [Monolog channel](http://symfony.com/doc/current/cookbook/logging/channels_handlers.html). To log everything about Recommendation to dev.recommendation.log, add the following to your `config.yml`:

```yaml
monolog:
    handlers:
        ez_recommendation:
            type:   stream
            path:   "%kernel.logs_dir%/%kernel.environment%.recommendation.log"
            channels: [ez_recommendation]
            level: info
```

Tip: You can replace `info` by `debug` for more verbosity.

## Other resources

- [API doc for YOOCHOOSE Service](http://docs.ezreco.apiary.io/#)
- [Developer Guides for YOOCHOOSE Service](https://doc.yoochoose.net/display/PUBDOC/Developer+Guide)
