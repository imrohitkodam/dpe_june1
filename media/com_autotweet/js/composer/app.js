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

"use strict";

(function () {
  var deps = ['starter.message-controller',
    'starter.editor-controller',
    'starter.requests-controller',
    'starter.agenda-controller',
    'starter.cronjob-expr-controller',
    'starter.jquery-extras'];

  angular.module('starter', deps)
    .config(function ($logProvider, $compileProvider) {

      // Debug Application
      $logProvider.debugEnabled(false);
      $compileProvider.debugInfoEnabled(false);

    });
})();
