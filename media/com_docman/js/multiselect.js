/**
 * Multiselect
 *
 * @copyright   Copyright (C) 2020 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 */

(function($) {

    $.fn.multiselect = function(options)
    {
        var containers  = this;
        var contained   = false;
        var lastChecked = null;

        if (containers.attr('type') != 'checkbox')
        {
            checkboxes = containers.find(':checkbox');
            contained  = true;
        }
        else checkboxes = containers;
                    
        containers.on('click', function(e)
        {
            var element = $(e.target);

            // Label workaround
            
            if (element.is('label')) {
                element = element.parent().find(':checkbox');
            }

            if (element.attr('type') == 'checkbox')
            {               
                if (e.shiftKey)
                {
                    start = checkboxes.index(element);
                    end   = lastChecked;

                    let state = contained ? !element.prop('checked') : element.prop('checked');

                    checkboxes.slice(Math.min(start,end), Math.max(start,end)+ 1).prop('checked', state);

                    if (!state) lastChecked = null; // Reset
                }
                else
                {
                    if (lastChecked == null) {
                        lastChecked = checkboxes.index(element);
                        return;
                    }
                }
            }
        }).on('mousedown', function(e)
        {
            if (e.shiftKey) {
                e.preventDefault(); // Prevent selecting of text by Shift+click
            }
        });
    }

})(kQuery);