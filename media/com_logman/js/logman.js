/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
(function ($) {
    Logman = {
        More: function (config) {
            var my = {
                init: function (config) {
                    this.button = config.button ? config.button : $('#show-more');

                    if (config.url) {
                        this.next(config.url);
                    }
                },

                next: function(url) {
                    this.url = url;
                    this.button.one('click', this.fetch);
                },

                fetch: function()
                {
                    var loading = my.button.data('loading'),
                        caption = my.button.html();

                    if (loading) {
                        my.button.html(loading);
                    }

                    my.button.trigger('before.fetch');

                    var url = my.url;

                    $.ajax(url, {
                        "success": function (data, status, request)
                        {
                            var args = {"content": data};

                            var url = Logman.Headers.getLink(request, 'next');

                            if (url) {
                                my.next(url);
                                args.url = url;
                            }

                            if (loading) {
                                my.button.html(caption);
                            }

                            my.button.trigger('after.fetch', args);
                        }
                    });
                },

                bind: function(event, handler) {
                    this.button.on(event, handler);
                }
            };

            my.init(config);

            return my;
        },

        Headers: {
            getLink: function(request, rel) {
                var url = null;

                var links = request.getResponseHeader('link');

                if (links)
                {
                    links = links.split(',');

                    $.each(links, function(idx, link)
                    {
                        if (link.search('rel="' + rel + '"') !== -1)
                        {
                            var result = link.match(/<(.*?)>/);

                            if (result) {
                                url = result.pop();
                            }

                            return false;
                        }
                    });
                }

                return url;
            }
        },

        Export: function (config) {
            var my = {
                init: function (config) {
                    config = config ? config : {};
                    this.container = config.container ? config.container : '#logman-export';
                    this.init_offset = config.init_offset ? config.init_offset : 0;
                    this.url = config.url;
                    this.timeout = config.timeout ? config.timeout : 30000;
                    this.exported = 0;
                    this.limit = 50;

                    this.callbacks = {};

                    this.callbacks.success = function (data, status, request) {
                        // Update progress bar.
                        my.update(request);

                        var url = Logman.Headers.getLink(request, 'next');

                        if (url) {
                            my.request(url);
                        } else {
                            // Export completed.
                            $(my.container).trigger('exportComplete', {exported: my.exported});
                        }
                    };

                    this.callbacks.error = function () {
                        alert('Export failed');
                    };
                },

                update: function (request) {

                    var total = request.getResponseHeader('X-Total-Count');
                    var exported = this.limit;
                    var remaining = total - exported;

                    if (remaining < 0)
                    {
                        exported = total;
                        remaining = 0;
                    }

                    // Update total exported amount.
                    this.exported += exported;
                    var completed = 100;

                    if (remaining) {
                        completed = parseInt(this.exported * 100 / (this.exported + remaining));
                    }

                    var data = {'exported': this.exported, 'completed': completed, 'remaining': remaining};

                    $(this.container).trigger('exportUpdate', data);
                },

                start: function () {
                    this.request(this.url + '&offset=' + this.init_offset + '&limit=' + this.limit);
                },

                request: function (url) {
                    $.ajax(url, {type: 'get', timeout: this.timeout, success: this.callbacks.success, error: this.callbacks.error});
                },

                bind: function (event, callback) {
                    $(this.container).on(event, callback);
                }
            };

            my.init(config);

            return my;
        }
    };
})(kQuery);