/*
 * Copyright (C) eZ Systems AS. All rights reserved.
 * For full copyright and license information view LICENSE file distributed with this source code.
 */

(function (global, doc) {
    var eZ = global.eZ = global.eZ || {};

    /**
     * HandleBars template renderer helper.
     *
     * @class RecommendationTemplateRenderer
     * @param {Object} config user settings
     */
    eZ.RecommendationTemplateRenderer = function (config) {
        this.templateId = config.templateId;
        this.recommendationsTarget = document.getElementById((config.recommendationsTargetPrefix || 'recommendations-target-') + this.templateId);
        this.recommendationsTemplate = document.getElementById((config.recommendationsTemplatePrefix || 'recommendations-template-') + this.templateId);
        this.feedbackUrl = config.feedbackUrl || '';
    };

    /**
     * Displays message.
     *
     * @method displayMessage
     */
    eZ.RecommendationTemplateRenderer.prototype.displayMessage = function (message) {
        this.recommendationsTarget.innerHTML = message;
    };

    /**
     * Displays recommendations.
     *
     * @method displayRecommendations
     * @param {Array} recommendations
     */
    eZ.RecommendationTemplateRenderer.prototype.displayRecommendations = function (recommendations) {
        var compiledTemplate = Handlebars.compile(this.recommendationsTemplate.innerHTML),
            itemId = [];

        for (var responseIdx in recommendations) {
            var attr = [];
            for (var attrIdx in recommendations[responseIdx].attributes) {
                if (recommendations[responseIdx].attributes.hasOwnProperty(attrIdx)) {
                    attr[recommendations[responseIdx].attributes[attrIdx].key] = recommendations[responseIdx].attributes[attrIdx].values.length
                        ? recommendations[responseIdx].attributes[attrIdx].values[0]
                        : '';
                }
            }
            recommendations[responseIdx].attr = attr;
            recommendations[responseIdx].clickRecommended = recommendations[responseIdx].links.clickRecommended;
            itemId.push(recommendations[responseIdx].itemId);
        }

        this.recommendationsTarget.innerHTML = compiledTemplate(recommendations);

        // informs YooChoose that recommendations were successfully delivered and displayed
        if (recommendations.length > 0) {
            eZ.RecommendationRestClient.ping(this.feedbackUrl + itemId.join());
        }
    };

})(window, document);
