// register HandleBars helpers when library is loaded

document.addEventListener('DOMContentLoaded', function () {
    Handlebars.registerHelper('formatDate', function (timestamp) {
        var t = new Date(parseInt(timestamp, 10) * 1000);
        return t.toLocaleDateString();
    });

    Handlebars.registerHelper('formatTime', function (timestamp) {
        var t = new Date(parseInt(timestamp, 10) * 1000);
        return t.toLocaleTimeString();
    });
});


