# Recommendation Bundle
This bundle integrates Recommendation services into eZ Platform. It supports the YooChoose recommender at this moment.

**Work in progress** This Bundle is work in progress, and support is restricted to pull request (contribution) directly on this repository until it reaches v1.0. After that, it will offically be supported by eZ for users with eZ and Yoochoose subscription.

## Requirements

- PHP 5.4.4
- eZ Publish 5.4.1/2015.01 or above, with the REST API configured to use sessions and publicly open to the YooChoose servers.
- A YooChoose license

This bundle is independent from legacy's ezrecommendation extension, and doesn't require it.

## Installation
This package is available via composer, so the instructions below are similar to how you install any other open source Symfony Bundle.

Run the following from your eZ Publish installation root (pick most recent release, example here is last one as of this writing):
```bash
php composer.phar require ezsystems/recommendation-bundle:~1.0@alpha
```

Enable the bundle in `ezpublish/EzPublishKernel.php`:
```php
$bundles = array(
    // existing bundles
    new EzSystems\RecommendationBundle\EzSystemsRecommendationBundle()
);
```

## Configuration
The bundle's configuration is siteaccess aware. This is an example of settings (config.yml):
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

### `yoochoose.customer_id` and `yoochoose.license_key`
These are your YooChoose customer ID and license keys.

### `server_uri`
The URI your site's REST API can be accessed from.

### `recommender.included_content_types`
This allows you to define content types on which tracking script will be shown. Go to the Tracking section to get more details.

### [advanced]
```yaml
ez_recommendation:
    api_endpoint: 'https://admin.yoochoose.net'
    recommender:
        api_endpoint: 'http://event.yoochoose.net'
        consume_timeout: 20
    tracking:
        api_endpoint: 'http://event.yoochoose.net'
        script_url: 'cdn.yoochoose.net/yct.js'
```

All of those settings will set advanced options for YooChoose backend. Changing any of these parameters without a valid reason will break all calls to YooChoose. It can be useful to test the API by mocking the service, or if you have a hosted version of YooChoose Recommendation service.

## Usage

### Initial setup
Your content structure must be mapped to the YooChoose domain model. This must be done in collaboration with YooChoose.

### Indexing
**Public** content is automatically indexed. When necessary, eZ Publish will notify YooChoose of changes to content. Initial import is to be managed with your YooChoose sales representative. Note that your server's REST API will need to be open to the YooChoose servers for indexing to be possible.

### Tracking
Events from the site needs to be sent to YooChoose so that recommendations can be adapted to visitors. Tracking can be setup in multiple ways, depending on anyone's constraints.

`EzRecommendationBundle` delivers Twig extension which helps integrate tracking functionality into your site. All you need to do is place small snippet code somewhere in the HEAD section of your header template (if your bundle is built on top of the DemoBundle this is `page_head.html.twig`):

```twig
{% if content is defined %}
    {{ yc_track_user(content.id) }}
{% endif %}
```

Next step is to configure settings under the `recommender.included_content_types` parameter (see: `default_settings.yml` file delivered with this bundle).

Here you can define for which content types tracking script will be shown.

You can find more information on the YooChoose documentation, about [tracking in general](https://doc.yoochoose.net/display/PUBDOC/1.+Tracking+Events), and about the [generic asynchronous javascript tracker](https://doc.yoochoose.net/display/PUBDOC/Tracking+with+yc.js).

###Displaying

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
`scenario`      | string | scenario used to display recommendations, you can create one at YooChoose dashboard
`limit`         | int    | how many recommendations will be shown?
`contentType`   | string | content type values you are expecting in response
`template`      | string | HandleBars template name (your templates are stored under `EzRecommendationBundle/Resources/public/views` directory. Take a look on `default.html.twig` file which includes default template that can be used to prepare customised version)
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
The ItemId mentioned throughout this documentation is usually set to the viewed ContentId. Depending on requirements, it can be set to a different value, in collaboration with YooChoose.

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

You can replace `info` by `debug` for more verbosity.
