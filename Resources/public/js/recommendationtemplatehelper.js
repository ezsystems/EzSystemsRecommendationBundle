/*
 * Copyright (C) eZ Systems AS. All rights reserved.
 * For full copyright and license information view LICENSE file distributed with this source code.
 */

/**
 * Converts ISO-8601 string to locale time/date format.
 */
Handlebars.registerHelper('toLocaleString', function (iso8601data) {
    return new Date(iso8601data).toLocaleString();
});
