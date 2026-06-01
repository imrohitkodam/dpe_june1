<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

if (class_exists('XTS_Google_Client', false)) {
    // Prevent error with preloading in PHP 7.4
    // @see https://github.com/googleapis/google-api-php-client/issues/1976
    return;
}

$classMap = [
    'XTS_BUILD\\Google\\Client' => 'XTS_Google_Client',
    'XTS_BUILD\\Google\\Service' => 'XTS_Google_Service',
    'XTS_BUILD\\Google\\AccessToken\\Revoke' => 'XTS_Google_AccessToken_Revoke',
    'XTS_BUILD\\Google\\AccessToken\\Verify' => 'XTS_Google_AccessToken_Verify',
    'XTS_BUILD\\Google\\Model' => 'XTS_Google_Model',
    'XTS_BUILD\\Google\\Utils\\UriTemplate' => 'XTS_Google_Utils_UriTemplate',
    'XTS_BUILD\\Google\\AuthHandler\\Guzzle6AuthHandler' => 'XTS_Google_AuthHandler_Guzzle6AuthHandler',
    'XTS_BUILD\\Google\\AuthHandler\\Guzzle7AuthHandler' => 'XTS_Google_AuthHandler_Guzzle7AuthHandler',
    'XTS_BUILD\\Google\\AuthHandler\\Guzzle5AuthHandler' => 'XTS_Google_AuthHandler_Guzzle5AuthHandler',
    'XTS_BUILD\\Google\\AuthHandler\\AuthHandlerFactory' => 'XTS_Google_AuthHandler_AuthHandlerFactory',
    'XTS_BUILD\\Google\\Http\\Batch' => 'XTS_Google_Http_Batch',
    'XTS_BUILD\\Google\\Http\\MediaFileUpload' => 'XTS_Google_Http_MediaFileUpload',
    'XTS_BUILD\\Google\\Http\\REST' => 'XTS_Google_Http_REST',
    'XTS_BUILD\\Google\\Task\\Retryable' => 'XTS_Google_Task_Retryable',
    'XTS_BUILD\\Google\\Task\\Exception' => 'XTS_Google_Task_Exception',
    'XTS_BUILD\\Google\\Task\\Runner' => 'XTS_Google_Task_Runner',
    'XTS_BUILD\\Google\\Collection' => 'XTS_Google_Collection',
    'XTS_BUILD\\Google\\Service\\Exception' => 'XTS_Google_Service_Exception',
    'XTS_BUILD\\Google\\Service\\Resource' => 'XTS_Google_Service_Resource',
    'XTS_BUILD\\Google\\Exception' => 'XTS_Google_Exception',
];

foreach ($classMap as $class => $alias) {
    class_alias($class, $alias);
}

/**
 * This class needs to be defined explicitly as scripts must be recognized by
 * the autoloader.
 */
class XTS_Google_Task_Composer extends \XTS_BUILD\Google\Task\Composer
{
}

if (\false) {
  class XTS_Google_AccessToken_Revoke extends \XTS_BUILD\Google\AccessToken\Revoke {}
  class XTS_Google_AccessToken_Verify extends \XTS_BUILD\Google\AccessToken\Verify {}
  class XTS_Google_AuthHandler_AuthHandlerFactory extends \XTS_BUILD\Google\AuthHandler\AuthHandlerFactory {}
  class XTS_Google_AuthHandler_Guzzle5AuthHandler extends \XTS_BUILD\Google\AuthHandler\Guzzle5AuthHandler {}
  class XTS_Google_AuthHandler_Guzzle6AuthHandler extends \XTS_BUILD\Google\AuthHandler\Guzzle6AuthHandler {}
  class XTS_Google_AuthHandler_Guzzle7AuthHandler extends \XTS_BUILD\Google\AuthHandler\Guzzle7AuthHandler {}
  class XTS_Google_Client extends \XTS_BUILD\Google\Client {}
  class XTS_Google_Collection extends \XTS_BUILD\Google\Collection {}
  class XTS_Google_Exception extends \XTS_BUILD\Google\Exception {}
  class XTS_Google_Http_Batch extends \XTS_BUILD\Google\Http\Batch {}
  class XTS_Google_Http_MediaFileUpload extends \XTS_BUILD\Google\Http\MediaFileUpload {}
  class XTS_Google_Http_REST extends \XTS_BUILD\Google\Http\REST {}
  class XTS_Google_Model extends \XTS_BUILD\Google\Model {}
  class XTS_Google_Service extends \XTS_BUILD\Google\Service {}
  class XTS_Google_Service_Exception extends \XTS_BUILD\Google\Service\Exception {}
  class XTS_Google_Service_Resource extends \XTS_BUILD\Google\Service\Resource {}
  class XTS_Google_Task_Exception extends \XTS_BUILD\Google\Task\Exception {}
  interface XTS_Google_Task_Retryable extends \XTS_BUILD\Google\Task\Retryable {}
  class XTS_Google_Task_Runner extends \XTS_BUILD\Google\Task\Runner {}
  class XTS_Google_Utils_UriTemplate extends \XTS_BUILD\Google\Utils\UriTemplate {}
}
