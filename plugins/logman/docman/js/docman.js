/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
(function($) {
    PlgLogmanDocman = {
        Lightbox: {
            init: function (config) {
                // Initialize variables
                if (!config) config = {};
                if (!config.selector) config.selector = '.docman-file';

                $(config.selector).each(function (idx, el) {
                    $(el).click(function (e) {
                        var el = $(this);

                        e.preventDefault();

                        var data = {};

                        data.name = el.attr('data-name');
                        data.size = el.attr('data-size');
                        data.url = el.attr('href');

                        if (el.attr('data-width') && el.attr('data-height')) {
                            data.width = el.attr('data-width');
                            data.height = el.attr('data-height');
                            data.image = true;
                        }

                        var rendered = new EJS({element: document.getElementById('docman-file-template')}).render(data),
                            element = $('<div class="k-ui-namespace docman-file-modal"></div>').append(rendered),
                            container = $('#docman-file-tmp'),
                            output = element.appendTo(container);

                        var display = function() {
                            output.css('max-width', container.width());
                            $.magnificPopup.open({items: {type: 'inline', src: output}});
                            container.empty();
                        };

                        setTimeout(display, 100);
                    });
                });
            }
        }
    }
}(kQuery));
