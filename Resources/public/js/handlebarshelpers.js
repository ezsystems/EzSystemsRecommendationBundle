/*
 * Copyright (C) eZ Systems AS. All rights reserved.
 * For full copyright and license information view LICENSE file distributed with this source code.
 */

// register HandleBars helpers when library is loaded

Handlebars.registerHelper('formatDate', function (timestamp) {
    var t = new Date(parseInt(timestamp, 10) * 1000);
    return t.toLocaleDateString();
});

Handlebars.registerHelper('formatTime', function (timestamp) {
    var t = new Date(parseInt(timestamp, 10) * 1000);
    return t.toLocaleTimeString();
});
