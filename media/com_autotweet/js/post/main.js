/*!
 * @package     Extly.Solutions
 * @subpackage  com_perfectpub - Publish your content easily and engage your audience.
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     http://https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @link        https://www.extly.com
 */

/* global angular, _, define, PostView */

'use strict';

define('post', [], function () {

  /* BEGIN - variables to be inserted here */

  /* END - variables to be inserted here */

  var postView = new PostView({
    el: jQuery('#adminForm')
  });

  return postView;

});
