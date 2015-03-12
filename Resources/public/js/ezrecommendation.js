// register HandleBars helpers when library is loaded

document.addEventListener('DOMContentLoaded', function() {
    Handlebars.registerHelper('each', function(context, options) {
        var result = '';
        for(var i = 0, j = context.length; i < j; i++) {
            result = result + options.fn(context[i]);
        }
        return result;
    });

    Handlebars.registerHelper('formatDate', function(timestamp) {
        var fixedTimestamp = (timestamp + '000') * 1;
        var t = new Date(fixedTimestamp);
        return t.toLocaleDateString();
    });

    Handlebars.registerHelper('formatTime', function(timestamp) {
        var fixedTimestamp = (timestamp + '000') * 1;
        var t = new Date(fixedTimestamp);
        return t.toLocaleTimeString();
    });
});

// EzRecoRestClient class

var EzRecoRestClient = function (config) {
    this.config = config;
};

EzRecoRestClient.prototype.fetchRecommendations = function (responseCallback, targetId, templateId) {
    var xmlhttp = new XMLHttpRequest();
    var jsonResponse;

    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState === XMLHttpRequest.DONE) {
            if (xmlhttp.status == 200) {
                jsonResponse = JSON.parse(xmlhttp.response);
            } else {
                jsonResponse = new Array();
                jsonResponse['status'] = 'fail';
            }
            responseCallback(jsonResponse, targetId, templateId);
        }
    };

    xmlhttp.open('GET', window.location.protocol + '//' + window.location.host + this.config['restUrl'] + '?locationId=' + this.config['locationId'] + '&scenarioId=' + this.config['scenarioId'] + '&limit=' + this.config['limit'], true);
    xmlhttp.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xmlhttp.send();
};

// EzRecoTemplateRenderer class

EzRecoTemplateRenderer = function (config) {
    this.config = config;
};

EzRecoTemplateRenderer.prototype.display = function (response, targetId, templateId) {
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
