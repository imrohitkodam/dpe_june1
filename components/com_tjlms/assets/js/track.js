/*add a plugin that tracks how long a user was on each page*/
Crocodoc.addPlugin('analytics', function (scope) {
    var currentPage,
        startTime,
        config;

    function track(page) {
        var elapsed,
            now = Date.now();
        if (currentPage) {
            elapsed = now - startTime;
            if (typeof config.ontrack === 'function') {
                config.ontrack(currentPage, elapsed / 1000);
            }
        }
        startTime = now;
        currentPage = page;
    }

    return {
        /*the messages property tells the viewer which messages this*/
        /*plugin is interested in*/
        messages: ['pagefocus'],

        /*this onmessage method is called when a message listed above*/
        /*is broadcast within the viewer instance*/
        onmessage: function (name, data) {
            /*in this case, we are only listening for one message type,*/
            /*so we don't need to do any checking against the name value*/
            track(data.page);
        },

        /*init is called when the viewer is initialized, and the plugin*/
        /*config is passed as a parameter*/
        init: function (pluginConfig) {
            config = pluginConfig;
        }
    };
});
