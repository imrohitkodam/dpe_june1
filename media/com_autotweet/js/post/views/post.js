/*!
 * @package     Extly.Solutions
 * @subpackage  com_perfectpub - Publish your content easily and engage your audience.
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     http://https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @link        https://www.extly.com
 */

/* global angular, _, define, Backbone */

'use strict';

var PostView = Backbone.View.extend({

  events: {
    'change #plugin': 'onChangePlugin'
  },

  initialize: function () {
    this.overrideConditionsTab = this.$('#overrideconditions-tab');
    this.auditInfoTab = this.$('#auditinfo-tab');

    // Activate Tabs
    this.$('#qTypeTabs a[data-toggle=tab]').first().tab();

    this.onChangePlugin();
    // this.onChangeCreateEvent();
  },

  onChangePlugin: function () {
    var plugin = this.$('#plugin').val();

    if (plugin == 'autotweetpost') {
      this.overrideConditionsTab.fadeIn(0);
      this.overrideConditionsTab.find('a').show();

      jQuery('<style>')
        .prop('type', 'text/css')
        .html('#autotweet-advanced-text-attrs {display: none;}')
        .appendTo('head');
    }
    else {
      this.overrideConditionsTab.fadeOut(0);
      this.auditInfoTab.find('a').show();
    }
  }

});
