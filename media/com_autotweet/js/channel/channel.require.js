/*!
 * @package     Extly.Solutions
 * @subpackage  com_perfectpub - Publish your content easily and engage your audience.
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     http://https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @link        https://www.extly.com
 */

/* global jQuery, Request, Joomla, alert, Backbone, define, _ */

define('channel', ['extlycore'], function (Core) {
  "use strict";

  /* BEGIN - variables to be inserted here */


  /* END - variables to be inserted here */

  var $adminForm = jQuery('#adminForm');

  (new ChannelView({
    el: $adminForm,
    collection: new Channels()
  })).onChangeChannelType();

  var twValidationView = new TwValidationView({
    el: $adminForm,
    collection: new TwValidations()
  });

  var liOAuth2ValidationView = new LiOAuth2ValidationView({
    el: $adminForm,
    collection: new LiOAuth2Validations()
  });

  var eventsDispatcher = _.clone(Backbone.Events);

  var fbValidationView = new FbValidationView({
    el: $adminForm,
    collection: new FbValidations(),
    attributes: { dispatcher: eventsDispatcher }
  });

  var fbChannelView = new FbChannelView({
    el: $adminForm,
    collection: new FbChannels(),
    attributes: {
      dispatcher: eventsDispatcher,
      messagesview: fbValidationView
    }
  });

  var fbChValidationView = new FbChValidationView({
    el: $adminForm,
    collection: new FbChValidations()
  });

  var fbExtendView = new FbExtendView({
    el: $adminForm,
    collection: new FbExtends(),
    attributes: { dispatcher: eventsDispatcher }
  });

  var liOAuth2CompanyView = new LiOAuth2CompanyView({
    el: $adminForm,
    collection: new LiOAuth2Companies()
  });

  var scoopitValidationView = new ScoopitValidationView({
    el: $adminForm,
    collection: new ScoopitValidations()
  });

  var scoopitTopicView = new ScoopitTopicView({
    el: $adminForm,
    collection: new ScoopitTopics()
  });

  var tumblrValidationView = new TumblrValidationView({
    el: $adminForm,
    collection: new TumblrValidations()
  });

  var bloggerValidationView = new BloggerValidationView({
    el: $adminForm,
    collection: new BloggerValidations()
  });

  var telegramValidationView = new TelegramValidationView({
    el: $adminForm,
    collection: new TelegramValidations()
  });

  var mediumValidationView = new MediumValidationView({
    el: $adminForm,
    collection: new MediumValidations()
  });

  var pushwooshValidationView = new PushwooshValidationView({
    el: $adminForm,
    collection: new PushwooshValidations()
  });

  var oneSignalValidationView = new OneSignalValidationView({
    el: $adminForm,
    collection: new OneSignalValidations()
  });

  var pushAlertValidationView = new PushAlertValidationView({
    el: $adminForm,
    collection: new PushAlertValidations()
  });

  var pagespeedValidationView = new PageSpeedValidationView({
    el: $adminForm,
    collection: new PageSpeedValidations()
  });

  var pinterestValidationView = new PinterestValidationView({
    el: $adminForm,
    collection: new PinterestValidations()
  });

  var mybusinessValidationView = new MyBusinessValidationView({
    el: $adminForm,
    collection: new MyBusinessValidations()
  });

  window.xtAppDispatcher = eventsDispatcher;

  try {
    if (
        !window.punycode &&
        typeof define == 'function' &&
        typeof define.amd == 'object' &&
        define.amd
    ) {
        require(['punycode'], function(punycode) {
            window.punycode = punycode;
        });
    }
  } catch (e) {

  }

});
