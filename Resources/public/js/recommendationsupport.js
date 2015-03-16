// RecommendationSupport class

(function (global, doc) {
    var eZ = global.eZ = global.eZ || {};

    eZ.RecommendationSupport = function () {};

    eZ.RecommendationSupport.prototype.config = {
        msgEmpty: 'No recommendations found',
        msgError: 'An error occurred while loading the recommendations',
        msgNotSupported: 'Cannot display recommendations, this browser is not supported',
        restUrl: 'recommendations/fetch',
        limit: 5
    };

    eZ.RecommendationSupport.prototype.setOptions = function (config) {
        for (var setting in config)
            this.config[setting] = config[setting];
    };

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

    eZ.RecommendationSupport.prototype.showXMLHttpRequestError = function (targetId) {
        var targetElement = document.getElementById(targetId);
        targetElement.innerHTML = this.config['msgNotSupported'];
    };

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

    eZ.RecommendationSupport.prototype.display = function (response, targetId, templateId, msgError, msgEmpty) {
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

