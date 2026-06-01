/*!
 * @package     Extly.Solutions
 * @subpackage  com_perfectpub - Publish your content easily and engage your audience.
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     http://https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @link        https://www.extly.com
 */

/* jslint plusplus: true, browser: true, sloppy: true */
/* global jQuery, Request, Joomla, alert, Backbone */

var Channel = Core.ExtlyModel.extend({
  url: function () {
				return Core.SefHelper.route('index.php?option=com_autotweet&view=channels&task=getParamsForm&toolbar=none');
  }
});
