/*
 * Copyright (C) eZ Systems AS. All rights reserved.
 * For full copyright and license information view LICENSE file distributed with this source code.
 */

(function (global, doc) {
    const eZ = global.eZ = global.eZ || {};

    eZ.YooChooseRestClient = function () {};

    /**
     * Sends notification ping.
     *
     * @static
     * @method ping
     * @param {String} url
     */
    eZ.YooChooseRestClient.prototype.ping = function (url) {
        const xmlHttp = eZ.YooChooseRestClient.getXMLHttpRequest();

        if (!xmlHttp) {
            return true;
        }

        xmlHttp.open('GET', url, true);
        xmlHttp.send();

        return true;
    };

    /**
     * Returns available XMLHttpRequest object (depending on the browser).
     *
     * @static
     * @method getXMLHttpRequest
     * @returns {Object} XMLHttpRequest
     */
    eZ.YooChooseRestClient.getXMLHttpRequest = function () {
        let xmlHttp;

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
