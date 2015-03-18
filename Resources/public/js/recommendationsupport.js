/*
 * Copyright (C) eZ Systems AS. All rights reserved.
 * For full copyright and license information view LICENSE file distributed with this source code.
 */

(function (global, doc) {
    var eZ = global.eZ = global.eZ || {};

    /**
     * Contains logic needed to fetch and display YooChoose recommendations
     *
     * @class
     */
    eZ.RecommendationSupport = function () {};

    /**
     * Default settings
     *
     * @type {Array}
     */
    eZ.RecommendationSupport.prototype.config = {
        msgEmpty: 'No recommendations found',
        msgError: 'An error occurred while loading the recommendations',
        msgNotSupported: 'Cannot display recommendations, this browser is not supported',
        restUrl: 'recommendations/fetch',
        limit: 5
    };

    /**
     * Overwrite default settings
     *
     * @method setOptions
     * @param config {Array} user settings
     */
    eZ.RecommendationSupport.prototype.setOptions = function (config) {
        for (var setting in config)
            this.config[setting] = config[setting];
    };

    /**
     * Return available XMLHttpRequest object (depending on browser)
     *
     * @method getXMLHttpRequest
     * @returns {Object} XMLHttpRequest
     */
    eZ.RecommendationSupport.prototype.getXMLHttpRequest = function () {
        var xmlHttp;

        if (window.XMLHttpRequest)
            xmlHttp = new XMLHttpRequest();
        else {
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

    /**
     * Display `browser not supported` message instead of recommendations
     *
     * @method showXMLHttpRequestError
     * @param {String} targetId target element ID
     */
    eZ.RecommendationSupport.prototype.showXMLHttpRequestError = function (targetId) {
        var targetElement = document.getElementById(targetId);
        targetElement.innerHTML = this.config['msgNotSupported'];
    };

    /**
     * Fetch recommendations data using AJAX call
     *
     * @method fetch
     * @param {String} targetId target element ID
     * @param {String} templateId template ID
     * @param {String} scenarioId scenario name
     * @param {Int} locationId location ID
     * @param {RecommendationSupport~onSuccess} responseCallback called on success
     */
    eZ.RecommendationSupport.prototype.fetch = function (targetId, templateId, scenarioId, locationId, responseCallback) {
        var xmlhttp = this.getXMLHttpRequest();

        if (xmlhttp === null) {
            this.showXMLHttpRequestError(targetId);
            return;
        }

        xmlhttp.onreadystatechange = function () {
            var jsonResponse;

            if (xmlhttp.readyState === XMLHttpRequest.DONE) {
                if (xmlhttp.status == 200) {
                    jsonResponse = JSON.parse(xmlhttp.response);
                } else {
                    jsonResponse = {status: 'fail'};
                }
                responseCallback(jsonResponse, targetId, templateId);
            }
        };

        xmlhttp.open('GET', this.config['restUrl'] + '/' + locationId + '/' + scenarioId + '/' + this.config['limit'], true);
        xmlhttp.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xmlhttp.send();
    };

    /**
     * Callback used for fetch
     *
     * @callback RecommendationSupport~onSuccess
     * @param response recommendations stored in JSON format
     * @param targetId target element ID
     * @param templateId template ID
     */
    eZ.RecommendationSupport.prototype.display = function (response, targetId, templateId ) {
        var targetElement = document.getElementById(targetId);

        if (response.status === 'success') {
            var template = document.getElementById(templateId);
            var compiledTemplate = Handlebars.compile(template.innerHTML);
            var recommendationData = {
                recommendations: response.content
            };
            targetElement.innerHTML = compiledTemplate(recommendationData);
        } else if (response.status === 'empty') {
            targetElement.innerHTML = this.config['msgEmpty'];
        } else {
            targetElement.innerHTML = this.config['msgError'];
        }
    };

    /**
     * Fetch and display recommendations using build in methods
     *
     * @method get
     * @param {String} targetId target element ID
     * @param {String} templateId template ID
     * @param {String} scenarioId scenario name
     * @param {Int} locationId location ID
     */
    eZ.RecommendationSupport.prototype.get = function (targetId, templateId, scenarioId, locationId) {
        this.fetch(
            targetId,
            templateId,
            scenarioId,
            locationId,
            this.display.bind(this)
        );
    };
})(window, document);

