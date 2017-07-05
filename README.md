# Recommendation Bundle

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/1a1934d8-2677-4ae0-be94-6c1d3f541b38/big.png)](https://insight.sensiolabs.com/projects/1a1934d8-2677-4ae0-be94-6c1d3f541b38)

This bundle integrates Recommendation services into eZ Platform. It supports the [YOOCHOOSE](https://yoochoose.com/) recommender service.

**Support** Stable versions of this bundle is officially supported by eZ as of version 1.0.0, for users with a eZ Enterprise and YOOCHOOSE subscription.

## Requirements

- PHP 5.4.4 *or higher PHP 5.x version*
- Symfony 2.7 *or higher Symfony 2.x version*
- eZ Publish v5.4.1+ or eZ Platform/Studio v1.0+, with the REST API configured to use sessions and publicly open to the YOOCHOOSE servers.
- A YOOCHOOSE subscription

This bundle is independent from legacy's `ezrecommendation` extension, and doesn't require it.

## Installation

This package is available via composer, so the instructions below are similar to how you install any other open source Symfony Bundle.

Run the following from your eZ Publish / eZ Platform installation root *(here with most recent 1.0.x release)*:
```bash
composer require --no-update ezsystems/recommendation-bundle:^1.0.0
composer update --prefer-dist
```

Enable the bundle in `ezpublish/EzPublishKernel.php`:
```php
$bundles = array(
    // existing bundles
    new EzSystems\RecommendationBundle\EzSystemsRecommendationBundle()
);

```
Import additional routing by adding the following lines to your `routing.yml` file:

```yaml
recommendationBundleRestRoutes:
    resource: "@EzSystemsRecommendationBundle/Resources/config/routing_rest.yml"
    prefix:   %ezpublish_rest.path_prefix%
```
Keep in mind, that legacy support is disabled by default. To enable legacy search engine (requires `ezpublish-kernel` bundle) uncomment these lines in bundle `services.yml`:
```yaml
# ez_recommendation.legacy.search_engine:
#     class: ezpSearchEngine
#     factory_class: EzSystems\RecommendationBundle\eZ\Publish\LegacySearch\LegacySearchFactory
#     factory_method: build
#     arguments: [@ezpublish_legacy.kernel]

# ez_recommendation.legacy.recommendation_search_engine:
#     class: EzSystems\RecommendationBundle\eZ\Publish\LegacySearch\RecommendationLegacySearchEngine
#     arguments:
#         - @ez_recommendation.client.yoochoose_notifier
#         - @ez_recommendation.legacy.search_engine

# ez_recommendation.legacy.search_configuration_mapper:
#     class: EzSystems\RecommendationBundle\eZ\Publish\LegacySearch\ConfigurationMapper
#     arguments:
#         - @ez_recommendation.legacy.recommendation_search_engine
#         - @ezpublish.siteaccess
#     tags:
#         - { name: kernel.event_subscriber }
```

## Configuration

The bundle's configuration depends on siteaccess. This is an example of settings *(config.yml)*:
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

A working example:

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
        api_endpoint: '//reco.yoochoose.net'
        consume_timeout: 20
    tracking:
        api_endpoint: 'http://event.yoochoose.net'
        script_url: 'cdn.yoochoose.net/yct.js'
```

**IMPORTANT**
Changing any of the parameters above without a valid reason will break all calls to YOOCHOOSE.

*Possible use cases when parameters can be changed:*
- testing the API by mocking the service
- integrating with a hosted version of YOOCHOOSE Recommendation service

## Usage

### Initial setup

Your content structure must be mapped to the YOOCHOOSE domain model. This must be done in collaboration with YOOCHOOSE.

### Indexing

**Public** content is automatically indexed. When necessary, eZ Publish will notify YOOCHOOSE of changes to content. Initial content import is to be managed with your YOOCHOOSE sales representative. Note that your server's REST API have to be open to the YOOCHOOSE servers to allow content indexing.

### Tracking of user's activity

Events from the site need to be sent to YOOCHOOSE so that recommendations can be adapted to visitors. Tracking can be setup in multiple ways, depending on anyone's constraints.

`EzSystemsRecommendationBundle` provides Twig extension which helps to integrate tracking functionality into your site. All you need to do is to place a small snippet code somewhere in the `HEAD` section of your header template (if your bundle is built on top of the DemoBundle this is `page_head.html.twig`):

```twig
{% if content is defined %}
    {{ yc_track_user(content.id) }}
{% endif %}
```

Next step is to configure settings under the `recommender.included_content_types` parameter (see: `default_settings.yml` file delivered with this bundle).

Here you can define for which content types tracking script will be shown.

You can find more information on the YOOCHOOSE documentation, about [tracking in general](https://doc.yoochoose.net/display/PUBDOC/1.+Tracking+Events), and about the [generic asynchronous javascript tracker](https://doc.yoochoose.net/display/PUBDOC/Tracking+with+yc.js).

Additionaly, in case of missing content owner Id, there's option in `default_settings.yml` file to set up default content author:
```yaml
    ez_recommendation.default.author_id: 14   # ID: 14 is default ID of admin user
```

### Displaying

In order to allow displaying recommendations on your site you must add scripts (included in the bundle). They will allow you to integrate recommender engine with your site.

Implementation is very easy and can be performed in just a few steps (assuming that `EzSystemsRecommendationBundle` is properly configured and enabled in `EzPublishKernel.php` file):

* add additional JavaScript assets to your header template (if your bundle is built on top of the DemoBundle this is `page_head_script.html.twig` file):

```twig
{% javascripts
    ...

    '%kernel.root_dir%/../vendor/components/handlebars.js/handlebars.min.js'
    '@EzSystemsRecommendationBundle/Resources/public/js/recommendationtemplaterenderer.js'
    '@EzSystemsRecommendationBundle/Resources/public/js/recommendationtemplatehelper.js'
    '@EzSystemsRecommendationBundle/Resources/public/js/recommendationrestclient.js'
%}
```

* insert dedicated Twig helper in place where you want to display recommendations *(see further below for example)*:

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

Meanings of the parameters (all bellow are required):

Parameter       | Type   | Description
----------------|--------|------------
`contentId`     | int    | this is in content based views normally the Twig variable holding the content id (we want to get recommendations for)
`scenario`      | string | scenario used to display recommendations, you can create one at YOOCHOOSE dashboard
`limit`         | int    | how many recommendations will be shown?
`contentType`   | string | content type values you are expecting in response
`template`      | string | HandleBars template name (your templates are stored under `EzRecommendationBundle/Resources/public/views` directory. Take a look on `default.html.twig` file which includes default template that can be used to prepare customised version)
`fields`        | array  | here you can define which fields are required and will be requested from the recommender engine. These field names are also used inside `Handle Bars` templates.

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

You can also omit names of arguments by using standard value passing as arguments.

#### The item id
The ItemId mentioned throughout this documentation is usually set to the viewed ContentId. Depending on requirements, it can be set to a different value, in collaboration with YOOCHOOSE.

## Troubleshooting

Most operations are logged via the `ez_recommendation` [Monolog channel](http://symfony.com/doc/current/cookbook/logging/channels_handlers.html). To log all events from `EzSystemsRecommendationBundle` to the `dev.recommendation.log` file, add the following entry to your `config.yml` file:

```yaml
monolog:
    handlers:
        ez_recommendation:
            type:   stream
            path:   "%kernel.logs_dir%/%kernel.environment%.recommendation.log"
            channels: [ez_recommendation]
            level: info
```

**Tip:** You can replace `info` value with `debug` value for certain verbosity level.

## Other resources

- [API doc for YOOCHOOSE Service](http://docs.ezreco.apiary.io/#)
- [Developer Guides for YOOCHOOSE Service](https://doc.yoochoose.net/display/PUBDOC/Developer+Guide)
