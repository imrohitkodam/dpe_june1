/*!
 * @package     Extly.Solutions
 * @subpackage  com_perfectpub - Publish your content easily and engage your audience.
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     http://https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @link        https://www.extly.com
 */

/* globals jQuery, angular, Joomla, _ */

'use strict';

angular.module('starter.jquery-extras', [])
  .run(function () {
    angular.element(document).ready(function () {

      var form = document.getElementById('adminForm'),
        $form = jQuery(form);

      // Social Attributes Tabs and fields group - Action on click
      $form.find('.post-attrs-group a').click(function (e) {
        var btn = jQuery(e.target), v, btnParent;

        v = btn.attr('data-value');

        // It is a child object that received the click
        if (typeof v === 'undefined') {
            btnParent = btn.parents('.xt-button');
            v = btnParent.attr('data-value');
        }

        $form.find('.xt-subform').hide();
        $form.find('.xt-subform-' + v).show();
      });

      // Hide Social Attributes Tabs
      $form.find('.xt-subform').css('display', 'none');
    });
  });
