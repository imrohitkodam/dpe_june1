/*!
 * @package     Extly.Solutions
 * @subpackage  com_perfectpub - Publish your content easily and engage your audience.
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     http://https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @link        https://www.extly.com
 */

/* global angular */

'use strict';

angular.module('starter.jquery-extras', [])
  .run(function () {
    angular.element(document).ready(function () {

      // Ease on ChosenJS
      jQuery('#itemEditorTabsContent .xt-tab-pane').addClass("tab-pane fade");

      // Tabs
      var tabs = jQuery('#itemEditorTabs a:first');

      if (tabs.length) {
        tabs.show();
      }

      // First tab on the caller
      tabs = window.parent.jQuery('ul.nav-tabs li a:first');

      if (tabs.length) {
        tabs.show();
      }

    });
  });

