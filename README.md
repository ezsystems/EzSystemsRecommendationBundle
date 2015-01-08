# Recommendation Bundle
This bundle integrates Recommendation services into eZ Platform. It supports the YooChoose recommender at this moment.

## Requirements

- PHP 5.4.4
- eZ Publish 5.4/2014.11 or above, with the REST API configured to use sessions and publicly open to the YooChoose servers.
- A YooChoose license

This bundle is independent from legacy's ezrecommendation extension, and doesn't require it.

## Installation
As this repository is private, packagist can't be used to ease installation.

First, edit `composer.json`, and add the following:
```json
    "require": {
        "ezsystems/recommendation-bundle": "0.1.*@rc",
    }
    "repositories": [
        {
            "type": "vcs",
            "url":  "git@github.com:ezsystems/EzSystemsRecommendationBundle.git"
        }
    ]
```
If you already have a `"repositories"` entry, you may add it to the existing array,
but it will work in either case.

Run the following from your eZ Publish installation root to get the latest stable version:
```
php composer.phar require ezsystems/recommendation-bundle
```

Enable the bundle in `ezpublish/EzPublishKernel.php`:
```
$bundles = array(
    // existing bundles
    new EzSystems\RecommendationBundle\EzSystemsRecommendationBundle()
);
```

## Configuration
The bundle's configuration is siteaccess aware. This is an example of settings:
```
ez_recommendation:
    system:
        default:
            yoochoose:
                customer_id: "12345"
                license_key: "1234-5678-9012-3456-7890"
            server_uri: "http://example.com"
```

### `yoochoose.customer_id` and `yoochoose.license_key`
These are your YooChoose customer ID and license keys.

### `server_uri`
The URI your site's REST API can be accessed from.

### [advanced] `ez_recommendation.api_endpoint`
This will set the URI used for the YooChoose backend. Changing it without a valid reason will break all calls to yoochoose.
It can be useful to test the API by mocking the service.

## Usage

### Initial setup
Your content structure must be mapped to the YooChoose domain model. This must be done in collaboration with YooChoose.

### Indexing
**Public** content is automatically indexed. When necessary, eZ Publish will notify YooChoose of changes to content. Initial import is to be managed with your YooChoose sales representative. Note that your server's REST API will need to be open to the YooChoose servers for indexing to be possible.

### Tracking
Events from the site needs to be sent to YooChoose so that recommendations can be adapted to visitors. Tracking can be setup in multiple ways, depending on anyone's constraints.

You can find more information on the YooChoose documentation, about [tracking in general](https://doc.yoochoose.net/display/PUBDOC/1.+Tracking+Events), and about the [generic asynchronous javascript tracker](https://doc.yoochoose.net/display/PUBDOC/Tracking+with+yc.js).

#### The item id
The ItemId mentioned throughout this documentation is usually set to the viewed ContentId. Depending on requirements, it can be set to a different value, in collaboration with YooChoose.

## Troubleshooting
Most operations are logged via the `ez_recommendation` [Monolog channel](http://symfony.com/doc/current/cookbook/logging/channels_handlers.html). To log everything about Recommendation to dev.recommendation.log, add the following to your `config.yml`:

```
monolog:
    handlers:
        ez_recommendation:
            type:   stream
            path:   "%kernel.logs_dir%/%kernel.environment%.recommendation.log"
            channels: [ez_recommendation]
            level: info
```

You can replace `info` by `debug` for more verbosity.
