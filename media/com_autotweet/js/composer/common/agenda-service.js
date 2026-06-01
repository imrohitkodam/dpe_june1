/*!
 * @package     Extly.Solutions
 * @subpackage  com_perfectpub - Publish your content easily and engage your audience.
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     http://https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @link        https://www.extly.com
 */

/* globals angular,_ */

'use strict';

/**
 * Services that persists and retrieves Agendas from localStorage
 */
angular.module('starter.agenda-service', [])
  .factory('Agenda', function () {

    var STORAGE_ID = 'agendas-autotweet',
      _this = this;

    _this.get = function () {
      return JSON.parse(localStorage.getItem(STORAGE_ID) || '[]');
    };

    _this.put = function (todos) {
      localStorage.setItem(STORAGE_ID, JSON.stringify(todos));
    };

    _this.clear = function () {
      this.put([]);
    };

    return {
      get: _this.get,
      put: _this.put,
      clear: _this.clear
    };

  });
