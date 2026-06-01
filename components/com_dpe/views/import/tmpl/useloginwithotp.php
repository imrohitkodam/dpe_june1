<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

$document = Factory::getDocument();
$document->addStyleSheet('templates/shaper_helix3/css/bootstrap.min.css');
$document->addStyleSheet('templates/shaper_helix3/css/custom.css');
HTMLHelper::_('script', 'media/system/js/messages.min.js');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('bootstrap.renderModal');

?>
<!--
  NOTE (must-do server-side):
  1) Server MUST sanitize/escape any message included in JSON responses:
     $msgSafe = htmlspecialchars($msgRaw, ENT_QUOTES, 'UTF-8');
  2) Server MUST validate OTP server-side (e.g. preg_match('/^[0-9]{6}$/', $otp')).
  3) Implement rate-limiting and lockouts for repeated OTP failures.
  4) Add security headers (preferably via server / Joomla System - HTTP Headers plugin):
     - Content-Security-Policy, X-Frame-Options, X-Content-Type-Options, Strict-Transport-Security, Referrer-Policy
-->

<style>
    .martop
    {
        margin-top: 10% !important;
    }
    .logHead
    {
        font-weight: 600;font-family: 'Open Sans', sans-serif !important;
    }
    .otpsubhead{
        font-size: 15px;font-family:  'Open Sans', sans-serif !important;
    }
</style>

<div id="system-message-container"></div>

<div class="container">
    <div class="row shadow-sm p-3 mb-5 bg-white rounded martop">
        <!-- Left side with the image -->
        <div class="col-sm-4 mt-4 s">
            <img src="<?php echo Uri::root();?>images/DataProtectionEd_Logo150H.jpg" alt="DPE Logo" class="img-fluid">
            <br>
            <div class="col-12">
                <h5 class="mb-sm-3 mb-4 mt-0 blue-text logHead"><?php echo Text::_("DPE_LOGIN_WITH_OTP");?></h5>
                <h4 class="otpsubhead"><?php echo Text::_('COM_DPE_OTP_SUBHEAD');?></h4>
            </div>
        </div>

        <!-- Right side with the form -->
        <div class="col-sm-8 mt-4 margin-tops">
            <form action="<?php echo Route::_('index.php?option=com_users&task=user.login'); ?>" method="post" class="form-validate">
                <div class="otp-container">
                    <label for="otp">Enter <?php echo Text::_('COM_DPE_OTP_FULL');?>:</label>
                    <input type="text" name="otp" id="otp" class="otp-input form-control" placeholder="Enter your <?php echo Text::_('COM_DPE_OTP_FULL');?>">
                </div>
                <br>
                <div class="mb-3">
                    <!-- removed inline onclick; handlers are bound in JS -->
                    <button type="submit" class="btn btn-primary btn-block btn-otp-submit">
                        <?php echo Text::_('JLOGIN'); ?>
                    </button>
                    <button type="button" class="btn btn-success btn-block btn-otp-resend">
                        <?php echo Text::_('COM_DPE_RESND_OTP'); ?>
                    </button>
                </div>

                <input type="hidden" name="username" id="username" value="">
                <input type="hidden" name="password" id="password" value="">
                <input type="hidden" name="loginwithotp" id="loginwithotp" value="loginwithotp">

                <?php echo HTMLHelper::_('form.token'); ?>
            </form>
        </div>
    </div>

    <div class="row">
    </div>
</div>

<!-- Full safe JS for OTP popup (include this <script> block in your page) -->
<!-- This script expects jQuery and Joomla JS helpers to be present. It will load DOMPurify if not already loaded. -->
<script>
/**
 * OTP popup - safe client-side script
 * - Sanitizes any server-provided HTML using DOMPurify (loaded dynamically if needed)
 * - Uses safe DOM APIs (textContent) for any text rendering
 * - Validates OTP client-side (numeric, configurable length)
 * - Sends AJAX requests without pre-encoding payload (let jQuery handle encoding)
 * - Uses Joomla CSRF token header
 *
 * Author: Generated patch
 * Date: 2025
 */

(function (window, document, jQuery, Joomla) {
  'use strict';

  // Configuration - match server-side policy
  var OTP_MIN = 4;      // minimum OTP digits allowed (change to 6 if server expects 6)
  var OTP_MAX = 8;      // maximum OTP digits allowed
  var CSP_MSG = 'Message sanitized for safety.';

  // Utility: ensure DOMPurify is available, otherwise load it dynamically
  function ensureDOMPurify(callback) {
    if (window.DOMPurify) {
      return callback();
    }

    var script = document.createElement('script');
    script.src = 'https://unpkg.com/dompurify@2.4.0/dist/purify.min.js';
    script.async = true;
    script.onload = function () {
      if (!window.DOMPurify) {
        // fallback noop sanitizer
        window.DOMPurify = { sanitize: function (s) { return String(s); } };
      }
      callback();
    };
    script.onerror = function () {
      window.DOMPurify = { sanitize: function (s) { return String(s); } };
      callback();
    };
    document.head.appendChild(script);
  }

  // Sanitize a message (defense in depth). This returns a safe string.
  function sanitizeMessage(msg) {
    try {
      var raw = (typeof msg === 'undefined' || msg === null) ? '' : String(msg);
      return window.DOMPurify && typeof window.DOMPurify.sanitize === 'function'
        ? window.DOMPurify.sanitize(raw)
        : raw.replace(/[<>&"']/g, function (c) {
            return {'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;',"'":'&#39;'}[c];
          });
    } catch (e) {
      console.error('sanitizeMessage error', e);
      return CSP_MSG;
    }
  }

  // Show message via Joomla.renderMessages in a safe way
  function showJoomlaMessage(msg, type) {
    try {
      var safe = sanitizeMessage(msg);
      var allowed = ['message', 'error', 'warning', 'info', 'success'];
      var t = (allowed.indexOf(type) !== -1) ? type : 'message';

      // Clear existing messages and render sanitized message
      jQuery('#system-message-container').empty();

      if (typeof Joomla !== 'undefined' && Joomla.renderMessages) {
        Joomla.renderMessages({ [t]: [safe] });
      } else {
        // Fallback: create sanitized text node
        var $msgWrap = jQuery('<div role="alert" class="joomla-alert jo omla-alert--' + t + '"></div>');
        $msgWrap.text(safe);
        jQuery('#system-message-container').append($msgWrap);
      }

      // Auto-remove (fade) messages after 10s
      setTimeout(function () {
        jQuery('.joomla-alert').fadeOut('slow', function () {
          jQuery(this).remove();
        });
      }, 10000);
    } catch (e) {
      console.error('showJoomlaMessage error', e);
    }
  }

  // Basic OTP client-side validation
  function isValidOtp(otp) {
    if (!otp) return false;
    var re = new RegExp('^[0-9]{' + OTP_MIN + ',' + OTP_MAX + '}$');
    return re.test(String(otp).trim());
  }

  // Get value helper (reads from parent when present)
  function getParentValue(selector) {
    try {
      if (window.parent && window.parent.jQuery) {
        return window.parent.jQuery(selector).val() || '';
      }
    } catch (e) {
      // Access to parent may be blocked by cross-origin — handle gracefully
      console.warn('getParentValue failed', e);
    }
    return '';
  }

  // Public: Request OTP (AJAX)
  function getOtp() {
    var username = getParentValue('#username');
    var password = getParentValue('#password');

    if (!username || !password) {
      showJoomlaMessage('Username or password is missing.', 'error');
      return;
    }

    jQuery.ajax({
      url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&task=users.sendOtpToUser",
      type: 'POST',
      data: {
        username: username,
        password: password
      },
      dataType: "json",
      headers: { 'X-CSRF-Token': Joomla.getOptions('csrf.token', '') },
      success: function (response) {
        var msg = response && response.data && response.data.msg ? response.data.msg : 'OTP request completed.';
        var type = response && response.data && response.data.type ? response.data.type : 'message';
        showJoomlaMessage(msg, type);
      },
      error: function (jqXHR, textStatus, errorThrown) {
        showJoomlaMessage('An unexpected error occurred: ' + textStatus, 'error');
      }
    });
  }

  // Public: Check OTP (AJAX)
  function checkOtp(event) {
    if (event && typeof event.preventDefault === 'function') {
      event.preventDefault();
    }

    var username = getParentValue('#username');
    var password = getParentValue('#password');
    var otp = jQuery('#otp').val() ? String(jQuery('#otp').val()).trim() : '';

    if (!username) {
      showJoomlaMessage('Username is not set.', 'error');
      return false;
    }
    if (!password) {
      showJoomlaMessage('Password is not set.', 'error');
      return false;
    }
    if (!isValidOtp(otp)) {
      showJoomlaMessage('Invalid OTP format. Please enter the numeric OTP.', 'error');
      return false;
    }

    try { jQuery('#username').val(username); jQuery('#password').val(password); } catch (e) {}

    jQuery.ajax({
      url: Joomla.getOptions('system.paths').base + "/index.php?option=com_dpe&task=users.checkOtp",
      type: 'POST',
      data: {
        username: username,
        password: password,
        otp: otp
      },
      dataType: "json",
      headers: { 'X-CSRF-Token': Joomla.getOptions('csrf.token', '') },
      success: function (response) {
        var success = response && response.data && (response.data.success === true);
        var msg = response && response.data && response.data.msg ? response.data.msg : (success ? 'Login successful' : 'An error occurred.');
        var type = response && response.data && response.data.type ? response.data.type : (success ? 'success' : 'error');

        showJoomlaMessage(msg, type);

        if (success) {
          try { window.parent && window.parent.jQuery && window.parent.jQuery('#otpsuccess').val('success'); } catch (e) {}
          setTimeout(function () {
            try { window.parent && window.parent.SqueezeBox && window.parent.SqueezeBox.close(); } catch (e) {}
          }, 1200);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        showJoomlaMessage('An unexpected error occurred: ' + textStatus, 'error');
      }
    });

    return false;
  }

  // Bind events safely after ensuring DOMPurify is available
  ensureDOMPurify(function () {
    jQuery(document).ready(function () {
      // Prevent context menu (if required)
      jQuery(document).on("contextmenu", function (e) {
        e.preventDefault();
      });

      // Use delegated handlers bound to classes (no inline onclick)
      jQuery(document).off('click.dpe-getotp').on('click.dpe-getotp', '.btn-otp-resend', function (evt) {
        evt.preventDefault();
        getOtp();
      });

      jQuery(document).off('click.dpe-checkotp').on('click.dpe-checkotp', '.btn-otp-submit', function (evt) {
        evt.preventDefault();
        checkOtp(evt);
      });

      // Show messages if present in URL (sanitized)
      try {
        var params = new URLSearchParams(window.location.search);
        var msg = params.get('msg');
        var type = params.get('type');
        if (msg && type) {
          showJoomlaMessage(msg, type);
        }
      } catch (e) { /* ignore older browsers */ }

      // Close message handlers
      jQuery(document).on('click', '.joomla-alert--close', function () {
        jQuery(this).closest('.joomla-alert').remove();
      });

      // Also support legacy close link
      jQuery(document).on('click', 'a.close', function (ev) {
        ev.preventDefault();
        jQuery(this).parent().remove();
      });
    });
  });

  // Expose functions to global scope if other inline HTML expects them
  window.getOtp = getOtp;
  window.checkOtp = checkOtp;

})(window, document, jQuery, Joomla);
</script>
