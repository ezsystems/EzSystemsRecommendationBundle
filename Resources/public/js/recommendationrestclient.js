/*
 * Copyright (C) eZ Systems AS. All rights reserved.
 * For full copyright and license information view LICENSE file distributed with this source code.
 */

(function (global, doc) {
    var eZ = global.eZ = global.eZ || {};

    /**
     * YooChoose recommender REST client.
     *
     * @class RecommendationRestClient
     * @param {Object} config user settings
     */
    eZ.RecommendationRestClient = function (config) {
        this.endpointUrl = config.endpointUrl || '';
        this.fields = config.fields || [];
        this.scenario = config.scenario || '';
        this.limit = config.limit || 0;
        this.language = config.language || '';
        this.fields = config.fields || [];
        this.filters = config.filters || '';
        this.contentType = config.contentType || '';
        this.outputTypeId = config.outputTypeId || '';
        this.contextItems = config.contextItems || '';
        this.categoryPath = config.categoryPath || '';
        this.errorMessage = config.errorMessage || 'Error occurred while loading recommendations';
        this.notSupportedMessage = config.notSupportedMessage || 'Cannot display recommendations, your browser is not supported';
        this.unauthorizedMessage = config.unauthorizedMessage || 'Internal error, unauthorized access to recommender engine (code: 401)';
        this.internalServerErrorMessage = config.internalServerErrorMessage || 'Internal server error, please validate your recommendation settings (code: 500)';
    };

    /**
     * Requests recommendations from recommender engine.
     *
     * @param {RecommendationRestClient~onSuccess} responseCallback
     * @param {RecommendationRestClient~onFailure} errorCallback
     */
    eZ.RecommendationRestClient.prototype.fetchRecommendations = function (responseCallback, errorCallback) {
        var xmlhttp = eZ.RecommendationRestClient.getXMLHttpRequest(),
            attributes = '',
            requestQueryString;

        if (xmlhttp === null) {
            errorCallback(this.notSupportedMessage);

            return;
        }

        xmlhttp.onreadystatechange = function () {
            var jsonResponse;

            if (xmlhttp.readyState === 4) {
                if (xmlhttp.status === 200) {
                    jsonResponse = JSON.parse(xmlhttp.response);
                    responseCallback(jsonResponse.recommendationResponseList, this);
                } else if (xmlhttp.status === 401) {
                    errorCallback(this.unauthorizedMessage);
                } else if (xmlhttp.status === 500) {
                    errorCallback(this.internalServerErrorMessage);
                } else {
                    errorCallback(this.errorMessage);
                }
            }
        }.bind(this);

        for (var i = 0; i < this.fields.length; i++) {
            attributes = attributes + '&attribute=' + this.fields[i];
        }

        requestQueryString = [
            this.endpointUrl,
            this.scenario,
            '.json?numrecs=', this.limit,
            '&contextitems=', this.contextItems,
            '&contenttype=', this.contentType,
            '&outputtypeid=', this.outputTypeId,
            '&categorypath=', encodeURIComponent(this.categoryPath),
            '&lang=', this.language,
            attributes,
            this.filters
        ];

        xmlhttp.open('GET', requestQueryString.join(''), true);
        xmlhttp.send();
    };

    /**
     * Sends notification ping.
     *
     * @static
     * @method ping
     * @param {String} url
     */
    eZ.RecommendationRestClient.ping = function (url) {
        var xmlhttp = eZ.RecommendationRestClient.getXMLHttpRequest();

        if (!xmlhttp) {
            return true;
        }

        xmlhttp.open('GET', url, false);
        xmlhttp.send();

        return true;
    };

    /**
     * Returns available XMLHttpRequest object (depending on the browser).
     *
     * @static
     * @method getXMLHttpRequest
     * @returns {Object} XMLHttpRequest
     */
    eZ.RecommendationRestClient.getXMLHttpRequest = function () {
        var xmlHttp;

        if (global.XMLHttpRequest) {
            xmlHttp = new XMLHttpRequest();
        } else {
            try {
                xmlHttp = new ActiveXObject('Msxml2.XMLHTTP');
            } catch(e) {
                try {
                    xmlHttp = new ActiveXObject('Microsoft.XMLHTTP');
                } catch(e) {
                    xmlHttp = null;
                }
            }
        }

        return xmlHttp;
    };

})(window, document);
