/*!
 * @package     Extly.Solutions
 * @subpackage  com_perfectpub - Publish your content easily and engage your audience.
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     http://https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @link        https://www.extly.com
 */

/* global define, FeedView */

'use strict';

define('feed', [], function () {
  "use strict";

  /* BEGIN - variables to be inserted here */

  /* END - variables to be inserted here */

  var feedView = new FeedView({
    el: jQuery('#adminForm')
  });

  return feedView;

});
