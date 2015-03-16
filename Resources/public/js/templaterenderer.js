// TemplateRenderer class

(function (global, doc) {
    var eZ = global.eZ = global.eZ || {};

    eZ.RecoTemplateRenderer = function (config) {
        this.config = config;
    };

    eZ.RecoTemplateRenderer.prototype.display = function (response, targetId, templateId) {
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
})(window, document);
