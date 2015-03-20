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
     * @param {Object} config user settings
     */
    eZ.RecommendationSupport = function (config) {
        this.msgEmpty = config.msgEmpty || 'No recommendations found';
        this.msgError = config.msgError || 'An error occurred while loading the recommendations';
        this.msgNotSupported = config.msgNotSupported || 'Cannot display recommendations, this browser is not supported';
        this.restUrl = config.restUrl || 'recommendations/fetch';
        this.limit = config.limit || 5;
    };

    /**
     * Return available XMLHttpRequest object (depending on browser)
     *
     * @method getXMLHttpRequest
     * @returns {Object} XMLHttpRequest
     */
    eZ.RecommendationSupport.prototype.getXMLHttpRequest = function () {
        var xmlHttp;

        if (global.XMLHttpRequest)
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
        targetElement.innerHTML = this.msgNotSupported;
    };

    /**
     * Fetch recommendations data using AJAX call
     *
     * @method fetch
     * @param {String} targetId target element ID
     * @param {String} templateId template ID
     * @param {String} scenarioId scenario name
     * @param {Int} contentId content ID
     * @param {RecommendationSupport~onSuccess} responseCallback called on success
     */
    eZ.RecommendationSupport.prototype.fetch = function (targetId, templateId, scenarioId, contentId, responseCallback) {
        var xmlhttp = this.getXMLHttpRequest();

        if (xmlhttp === null) {
            this.showXMLHttpRequestError(targetId);
            return;
        }

        xmlhttp.onreadystatechange = function () {
            var jsonResponse;

            if (xmlhttp.readyState === 4) {
                if (xmlhttp.status === 200) {
                    jsonResponse = JSON.parse(xmlhttp.response);
                } else {
                    jsonResponse = {status: 'fail'};
                }
                responseCallback(jsonResponse, targetId, templateId);
            }
        };

        xmlhttp.open('GET', this.restUrl + '/' + contentId + '/' + scenarioId + '/' + this.limit, true);
        xmlhttp.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xmlhttp.send();
    };

    /**
     * Callback used for fetch
     *
     * @callback RecommendationSupport~onSuccess
     * @param {String} response recommendations stored in JSON format
     * @param {String} targetId target element ID
     * @param {String} templateId template ID
     */
    eZ.RecommendationSupport.prototype.display = function (response, targetId, templateId ) {
        var template, compiledTemplate, recommendationData,
            targetElement = document.getElementById(targetId);

        if (response.status === 'success') {
            template = document.getElementById(templateId);
            compiledTemplate = Handlebars.compile(template.innerHTML);
            recommendationData = {
                recommendations: response.content
            };
            targetElement.innerHTML = compiledTemplate(recommendationData);
        } else if (response.status === 'empty') {
            targetElement.innerHTML = this.msgEmpty;
        } else {
            targetElement.innerHTML = this.msgError;
        }
    };

    /**
     * Fetch and display recommendations using build in methods
     *
     * @method get
     * @param {String} targetId target element ID
     * @param {String} templateId template ID
     * @param {String} scenarioId scenario name
     * @param {Int} contentId content ID
     */
    eZ.RecommendationSupport.prototype.get = function (targetId, templateId, scenarioId, contentId) {
        this.fetch(
            targetId,
            templateId,
            scenarioId,
            contentId,
            this.display.bind(this)
        );
    };
})(window, document);

