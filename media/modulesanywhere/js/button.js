/**
 * @package         Modules Anywhere
 * @version         7.16.8PRO
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2023 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

(function() {
    'use strict';

    window.RegularLabs = window.RegularLabs || {};

    window.RegularLabs.ModulesAnywhereButton = window.RegularLabs.ModulesAnywhereButton || {
        insertText: function(content, editor_name) {
            Joomla.editors.instances[editor_name].replaceSelection(content);
        },
    };
})();
