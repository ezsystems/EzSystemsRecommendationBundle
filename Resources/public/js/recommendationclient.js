// RecoRestClient class

(function (global, doc) {
    var eZ = global.eZ = global.eZ || {};

    eZ.RecoRestClient = function (config) {
        this.config = config;
    };

    eZ.RecoRestClient.prototype.fetchRecommendations = function (targetId, templateId, responseCallback) {
        var xmlhttp = new XMLHttpRequest();

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

        xmlhttp.open('GET', this.config['restUrl'] + '?locationId=' + this.config['locationId'] + '&scenarioId=' + this.config['scenarioId'] + '&limit=' + this.config['limit'], true);
        xmlhttp.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xmlhttp.send();
    };
})(window, document);
