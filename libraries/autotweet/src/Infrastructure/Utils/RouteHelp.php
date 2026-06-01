<?php

/*
 * @package     Perfect Publisher
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 */

defined('_JEXEC') || exit;

/**
 * RouteHelp.
 *
 * @since       1.0
 */
class RouteHelp
{
    // Routing always by Backend-Frontend Query
    public const ROUTING_MODE_COMPATIBILITY = 0;

    // Routing by Backend-Frontend Query or just JRoute on Front-end
    //  (some components may return edition page, e.g. SobiPro or EasyBlog)
    public const ROUTING_MODE_PERFORMANCE = 1;

    // Language management
    public const LANGMGMT_REMOVELANG = 1;
    public const LANGMGMT_REPLACELANG = 2;
    public const LANGMGMT_REPLACECONTENTLANG = 3;
    public const LANGMGMT_SEF_VAR = '&lang=';

    public const XT_USERAGENT = 'Xtzilla/8';

    protected $langmgmt_enabled = 0;

    protected $langmgmt_default_language = '';

    protected $langmgmt_content_language;

    protected $routing_mode = 0;

    // Disable URL routing when wrong URLs are returned by Joomla
    protected $urlrouting_enabled = 1;

    protected $validate_url = 1;

    protected $root_url = '';

    protected $root_url_path = '';

    private static $_instance = null;

    /**
     * RouteHelp.
     */
    protected function __construct()
    {
        $this->langmgmt_enabled = EParameter::getComponentParam(CAUTOTWEETNG, 'langmgmt_enabled', 0);
        $this->langmgmt_default_language = EParameter::getComponentParam(CAUTOTWEETNG, 'langmgmt_default_language', '');

        $this->routing_mode = EParameter::getComponentParam(CAUTOTWEETNG, 'routing_mode', 0);

        // Root url overwrite
        $this->root_url = EParameter::getComponentParam(CAUTOTWEETNG, 'base_url', '');

        // Legacy invalid base_url initialization
        if ('http://' === $this->root_url) {
            $this->root_url = '';
        }

        if ((!empty($this->root_url)) && (0 !== strpos($this->root_url, 'http'))) {
            $msg = 'Invalid  Base URL Override (it must have http/https protocol): '.$this->root_url;
            AutotweetLogger::getInstance()->log(\Joomla\CMS\Log\Log::ERROR, $msg);
            \Joomla\CMS\Factory::getApplication()->enqueueMessage($msg, 'error');
        }

        $this->root_url = $this->_processRoot($this->root_url);

        // Root Url Check
        if (!empty($this->root_url)) {
            $this->root_url = $this->validateUrl($this->root_url);
        }

        $this->root_url_path = $this->_getPath();

        // Disable URL routing when wrong URLs are returned by Joomla
        $this->urlrouting_enabled = EParameter::getComponentParam(CAUTOTWEETNG, 'urlrouting_enabled', 1);

        $this->validate_url = EParameter::getComponentParam(CAUTOTWEETNG, 'validate_url', 1);
    }

    /**
     * getInstance.
     *
     * @return Instance
     */
    public static function &getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * getRoot.
     *
     * @return string
     */
    public function getRoot()
    {
        return $this->root_url;
    }

    /**
     * getAbsoluteRawUrl.
     *
     * @param string $url Param
     *
     * @return string
     */
    public function getAbsoluteRawUrl($url)
    {
        if (!$this->isAbsoluteUrl($url)) {
            $url = $this->_forceRelativeUrl($url);
        }

        if (false === strpos($url, 'index.php?')) {
            // Unexpected format, just return
            return $url;
        }

        $url = $this->root_url.$url;

        return $this->_addUtm($url);
    }

    /**
     * getAbsoluteUrl.
     *
     * @param string $url     Param
     * @param string $isImage Param
     *
     * @return string
     */
    public function getAbsoluteUrl($url, $isImage = false)
    {
        static $cache = [];

        $key = md5($url);

        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        if (!$this->isAbsoluteUrl($url)) {
            if ($isImage) {
                if (method_exists('\Joomla\CMS\HTML\HTMLHelper', 'cleanImageURL')) {
                    $metadata = \Joomla\CMS\HTML\HTMLHelper::cleanImageURL($url);
                    $url = $metadata->url;
                }

                $url = $this->routeImageUrl($url);
            } else {
                $url = $this->routeUrl($url);
            }
        }

        if (!$isImage) {
            $url = $this->_addUtm($url);
        }

        $cache[$key] = $url;

        return $url;
    }

    /**
     * isAbsoluteUrl.
     *
     * @param string $url Param
     *
     * @return bool
     */
    public function isAbsoluteUrl($url)
    {
        // (preg_match('|^(http(s)?:)?//|', $url))

        return 'http' === substr($url, 0, 4);
    }

    /**
     * isLocalUrl.
     *
     * @param string $url Param
     *
     * @return bool
     */
    public function isLocalUrl($url)
    {
        return false !== strpos($url, $this->root_url);
    }

    /**
     * setContentLanguage.
     *
     * @param string $lang Param
     */
    public function setContentLanguage($lang)
    {
        $this->langmgmt_content_language = $lang;
    }

    /**
     * getLanguageSef.
     *
     * @param string $tag Param
     *
     * @return string
     */
    public function getLanguageSef($tag = null)
    {
        if (!$tag) {
            $tag = \Joomla\CMS\Factory::getLanguage()->getTag();
        }

        $languages = JLanguageHelper::getLanguages('lang_code');

        return $languages[$tag]->sef;
    }

    /**
     * validateUrl.
     *
     * @param string $url Param
     *
     * @return string
     */
    public function validateUrl($url)
    {
        if (!$this->validate_url) {
            return $url;
        }

        $logger = AutotweetLogger::getInstance();

        if (false !== filter_var($url, \FILTER_VALIDATE_URL)) {
            // $logger->log(\Joomla\CMS\Log\Log::INFO, 'ValidateUrl: OK url = ' . $url);

            return $url;
        }

        // Second chance
        $c = curl_init();
        curl_setopt($c, \CURLOPT_URL, $url);

        // User Agent definition
        curl_setopt($c, \CURLOPT_USERAGENT, self::XT_USERAGENT);

        // Get the header
        curl_setopt($c, \CURLOPT_HEADER, 1);

        // And *only* get the header
        curl_setopt($c, \CURLOPT_NOBODY, 1);

        // Get the response as a string from curl_exec(), rather than echoing it
        curl_setopt($c, \CURLOPT_RETURNTRANSFER, 1);

        // Don't use a cached version of the url
        curl_setopt($c, \CURLOPT_FRESH_CONNECT, 1);

        if (!curl_exec($c)) {
            $logger->log(\Joomla\CMS\Log\Log::ERROR, 'ValidateUrl: invalid url = '.$url);
            \Joomla\CMS\Factory::getApplication()->enqueueMessage(JText::_('COM_AUTOTWEET_COMPARAM_VALIDATE_URL_ERROR'), 'error');

            return null;
        }

        // $logger->log(\Joomla\CMS\Log\Log::INFO, 'ValidateUrl: OK - Second chance - url = ' . $url);

        return $url;
    }

    /**
     * isMultilingual.
     *
     * @return bool
     */
    public static function isMultilingual()
    {
        static $isMultilingual = null;

        if (null === $isMultilingual) {
            $isMultilingual = (\Joomla\CMS\Plugin\PluginHelper::isEnabled('system', 'languagefilter'));
        }

        return $isMultilingual;
    }

    /**
     * _addUtm.
     *
     * @param string $url Param
     *
     * @return string
     */
    private function _addUtm($url)
    {
        if (empty($url)) {
            return $url;
        }

        $utm_source = EParameter::getComponentParam(CAUTOTWEETNG, 'utm_source');
        $utm_medium = EParameter::getComponentParam(CAUTOTWEETNG, 'utm_medium');
        $utm_term = EParameter::getComponentParam(CAUTOTWEETNG, 'utm_term');
        $utm_content = EParameter::getComponentParam(CAUTOTWEETNG, 'utm_content');
        $utm_campaign = EParameter::getComponentParam(CAUTOTWEETNG, 'utm_campaign');

        if (($utm_source) || ($utm_medium) || ($utm_term) || ($utm_content) || ($utm_campaign)) {
            $uri = \Joomla\CMS\Uri\Uri::getInstance($url);

            if ($utm_source) {
                $uri->setVar('utm_source', $utm_source);
            }

            if ($utm_medium) {
                $uri->setVar('utm_medium', $utm_medium);
            }

            if ($utm_term) {
                $uri->setVar('utm_term', $utm_term);
            }

            if ($utm_content) {
                $uri->setVar('utm_content', $utm_content);
            }

            if ($utm_campaign) {
                $uri->setVar('utm_campaign', $utm_campaign);
            }

            $url = $uri->toString();
        }

        return $url;
    }

    /**
     * Routes the URL.
     * This is a substitute for the original Joomla route function JRoute::_
     * because JRoute::_ does work from frontend only and has some special behavoir
     * with image URLs.
     *
     * @param string $url Param
     *
     * @return string
     */
    private function routeUrl($url)
    {
        $logger = AutotweetLogger::getInstance();

        $logger->log(\Joomla\CMS\Log\Log::INFO, 'internal url = '.$url);

        if (!empty($url)) {
            // Get (sef) url for frontend and backend
            if ($this->urlrouting_enabled) {
                $url = $this->build($url);
                $logger->log(\Joomla\CMS\Log\Log::INFO, 'routeURL: routed url = '.$url);
            } else {
                $logger->log(\Joomla\CMS\Log\Log::WARNING, 'routeURL: url routing disabled');
            }

            // Check for language management mode and correct url language if needed
            if ($this->langmgmt_enabled) {
                $url = $this->correctUrlLang($url);
                $logger->log(\Joomla\CMS\Log\Log::INFO, 'routeURL: language corrected url = '.$url);
            }

            // JoomSef router is generating absolute Urls, no need to make them absolute
            if (!$this->isAbsoluteUrl($url)) {
                $url = $this->createAbsoluteUrl($url);
            }

            $url = $this->validateUrl($url);
        }

        return $url;
    }

    /**
     * Routes the Image.
     *
     * @param string $filename Param
     *
     * @return string
     */
    private function routeImageUrl($filename)
    {
        // $logger = AutotweetLogger::getInstance();
        // $logger->log(\Joomla\CMS\Log\Log::INFO, 'routeImageUrl filename = ' . $filename);

        $url = '';

        if (!empty($filename)) {
            $url = implode('/', array_map('rawurlencode', explode('/', $filename)));
            $url = $this->createAbsoluteUrl($url);
            $url = $this->validateUrl($url);
        }

        return $url;
    }

    /**
     * build.
     *
     * Route/build the URL.
     * This is a substitute for the original Joomla route function JRoute::_
     * because JRoute::_ does work from frontend only for SEF urls.
     * Works also for JoomSEF and sh404sef.
     *
     * @param string $url Param
     *
     * @return object
     */
    private function build($url)
    {
        $url = $this->_forceRelativeUrl($url);

        if (false === strpos($url, 'index.php?')) {
            return $url;
        }

        // Multilanguage support
        $url = $this->defineMultilingualTag($url);

        if (self::ROUTING_MODE_PERFORMANCE === (int) $this->routing_mode) {
            if ((\Joomla\CMS\Factory::getApplication()->isClient('administrator')) || (defined('AUTOTWEET_CRONJOB_RUNNING'))) {
                return $this->_frontSiteSefQuery($url);
            }

            return JRoute::_($url, false);
        }

        // ROUTING_MODE_COMPATIBILITY
        return $this->_frontSiteSefQuery($url);
    }

    /**
     * _frontSiteSefQuery.
     *
     * @param string $url Param
     *
     * @return string
     */
    private function _frontSiteSefQuery($url)
    {
        $logger = AutotweetLogger::getInstance();

        $url_as_param = urlencode(base64_encode($url));
        $callsef = $this->root_url.'index.php?option=com_autotweet&view=sef&task=route&url='.$url_as_param;

        // Get the url
        $c = curl_init();
        curl_setopt($c, \CURLOPT_USERAGENT, self::XT_USERAGENT);
        curl_setopt($c, \CURLOPT_URL, $callsef);
        curl_setopt($c, \CURLOPT_HEADER, 0);
        curl_setopt($c, \CURLOPT_NOBODY, 0);
        curl_setopt($c, \CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, \CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($c, \CURLOPT_TIMEOUT, 40);
        curl_setopt($c, \CURLOPT_FOLLOWLOCATION, 1);

        $logger->log(\Joomla\CMS\Log\Log::INFO, 'Calling SEF Router: '.$callsef);

        $sefurl = curl_exec($c);
        $result_code = curl_getinfo($c);

        $logger->log(\Joomla\CMS\Log\Log::INFO, '--> result ('.$result_code['http_code'].'): '.$sefurl);

        $sefurl = base64_decode($sefurl, true);

        $logger->log(\Joomla\CMS\Log\Log::INFO, '--> result Url: '.$sefurl);

        // REDIRECT Case: Ok, one more chance
        if (((int) $result_code['http_code'] >= 300)
            && ((int) $result_code['http_code'] < 400)
            && (array_key_exists('redirect_url', $result_code))) {
            $redirect_url = $result_code['redirect_url'];
            $logger->log(\Joomla\CMS\Log\Log::INFO, 'REDIRECT Calling SEF Router: '.$redirect_url);

            $callsef = $redirect_url;
            curl_setopt($c, \CURLOPT_URL, $callsef);

            $sefurl = curl_exec($c);
            $result_code = curl_getinfo($c);

            $logger->log(\Joomla\CMS\Log\Log::INFO, '--> result ('.$result_code['http_code'].'): '.$sefurl);

            $sefurl = base64_decode($sefurl, true);

            $logger->log(\Joomla\CMS\Log\Log::INFO, '--> result Url: '.$sefurl);
        }

        // Error handling
        if (curl_errno($c)) {
            $sefurl = JRoute::_($url, false);

            $logger = AutotweetLogger::getInstance();
            $logger->log(\Joomla\CMS\Log\Log::WARNING, 'Error routing SEF URL via frontend request - curl_error: '.curl_errno($c).' '.curl_error($c));
        } elseif (((int) $result_code['http_code'] < 200) ||
                ((int) $result_code['http_code'] >= 300)) {
            // Non-200 http_code cases
            $logger = AutotweetLogger::getInstance();
            $logger->log(\Joomla\CMS\Log\Log::WARNING, 'Error routing SEF URL via frontend request - http error: '.$result_code['http_code'].' - callurl = '.$url.' - return url = '.$sefurl);
            $sefurl = JRoute::_($url, false);
        } else {
            // In backend we need to remove some parts from the url
            $sefurl = str_replace('/components/com_autotweet/', '/', $sefurl);
        }

        // Something odd has happened
        if (empty($sefurl)) {
            $logger->log(\Joomla\CMS\Log\Log::WARNING, 'Error routing SEF URL via frontend request - http error: '.$result_code['http_code'], $result_code);
        }

        curl_close($c);

        return $sefurl;
    }

    /**
     * _siteApplicationSefQuery.
     *
     * @param string $url Param
     *
     * @return string
     */
    private function _siteApplicationSefQuery($url)
    {
        // In the back end we need to set the application to the site app instead
        \Joomla\CMS\Factory::$application = JApplication::getInstance('site');
        \Joomla\CMS\Factory::$application->initialise();

        $sefurl = JRoute::_($url, false);

        // Set the appilcation back to the administartor app
        \Joomla\CMS\Factory::$application = JApplication::getInstance('administrator');

        return $sefurl;
    }

    /**
     * Helps with the Joomla url hell and creates corect url savely for frontend, backend and images.
     *
     * @param string $site_url Param
     *
     * @return string
     */
    private function createAbsoluteUrl($site_url)
    {
        $site_url = $this->_forceRelativeUrl($site_url);
        $url = $this->root_url.$site_url;

        return $url;
    }

    /**
     * _forceRelativeUrl.
     *
     * @param string $site_url Param
     *
     * @return string
     */
    private function _forceRelativeUrl($site_url)
    {
        $pattern = '//'.\Joomla\CMS\Uri\Uri::getInstance($this->root_url)->getHost();

        // If starts with '//qqq.com', avoid '//qqq.com/qqq'
        if (0 === strpos($site_url, $pattern)) {
            $site_url = str_replace($pattern, '', $site_url);
        }

        if ($this->hasPath($site_url)) {
            $path = $this->root_url_path;
            $l = strlen($path);
            $site_url = substr($site_url, $l);
        }

        // Just in case
        if (0 === strpos($site_url, '/administrator')) {
            $site_url = substr($site_url, 14);
        }

        // If starts with '//', avoid 'qqq.com///qqq'
        if ('//' === substr($site_url, 0, 2)) {
            $site_url = substr($site_url, 2);
        }

        // If starts with '/', avoid 'qqq.com//qqq'
        if ('/' === substr($site_url, 0, 1)) {
            $site_url = substr($site_url, 1);
        }

        return $site_url;
    }

    /**
     * _processRoot.
     *
     * @param string $url param
     *
     * @return string
     */
    private function _processRoot($url)
    {
        if (empty($url)) {
            try {
                $url = \Joomla\CMS\Uri\Uri::root();
            } catch (Exception $e) {
                $url = 'http://undefined-domain.com/';
            }
        }

        // Forced front-end SSL
        if ((2 === \Joomla\CMS\Factory::getConfig()->get('force_ssl'))
            && (0 === strpos($url, 'http:'))) {
            $url = str_replace('http:', 'https:', $url);
        }

        // Always end with '/'
        if ('/' !== substr($url, -1)) {
            $url .= '/';
        }

        return $url;
    }

    /**
     * getHost.
     *
     * @return string
     */
    private function getHost()
    {
        $uri = new JUri();

        if ($uri->parse($this->root_url)) {
            $host = $uri->toString(
                [
                    'scheme',
                    'host',
                    'port',
                ]
            );

            return $host;
        }

        return null;
    }

    /**
     * getPath.
     *
     * @return string
     */
    private function _getPath()
    {
        $uri = new JUri();

        if ($uri->parse($this->root_url)) {
            $path = $uri->toString(
                [
                    'path',
                ]
            );

            return $path;
        }

        return null;
    }

    /**
     * hasPath.
     *
     * @param string $url Param
     *
     * @return string
     */
    private function hasPath($url)
    {
        $path = $this->root_url_path;
        $l = strlen($path);

        // At least /a/
        return ($l >= 3) && (substr($url, 0, $l) === $path);
    }

    /**
     * defineMultilingualTag.
     *
     * @param string $url Param
     *
     * @return string
     */
    private function defineMultilingualTag($url)
    {
        if (!$this->isMultilingual() || !(bool) $this->langmgmt_enabled) {
            return $url;
        }

        $uri = new JUri();

        if ((!$uri->parse($url)) || ($uri->hasVar('lang'))) {
            return $url;
        }

        $logger = AutotweetLogger::getInstance();
        $logger->log(\Joomla\CMS\Log\Log::INFO, 'defineMultilingualTag: Url '.$url);

        $languages = JLanguageHelper::getLanguages('lang_code');

        if ((self::LANGMGMT_REPLACECONTENTLANG === (int) $this->langmgmt_enabled)
            && (!empty($this->langmgmt_content_language))) {
            $langCode = $languages[$this->langmgmt_content_language]->sef;
        } else {
            $tag = \Joomla\CMS\Factory::getLanguage()->getTag();
            $langCode = $languages[$tag]->sef;
        }

        $uri->setVar('lang', $langCode);
        $url = $uri->toString();

        $logger->log(\Joomla\CMS\Log\Log::INFO, 'defineMultilingualTag: Lang-Url '.$url);

        return $url;
    }

    /**
     * correctUrlLang.
     *
     * @param string $url Param
     *
     * @return string
     */
    private function correctUrlLang($url)
    {
        $language = null;
        $logger = AutotweetLogger::getInstance();

        if ((self::LANGMGMT_REPLACECONTENTLANG === (int) $this->langmgmt_enabled)
            && (!empty($this->langmgmt_content_language))) {
            $language = $this->langmgmt_content_language;
            $logger->log(\Joomla\CMS\Log\Log::INFO, 'correctUrlLang LANGMGMT_REPLACECONTENTLANG '.$language);

            if ('*' === $language) {
                $logger->log(\Joomla\CMS\Log\Log::WARNING, 'correctUrlLang: language * nothing to do.');

                return $url;
            }

            if (empty($language)) {
                $logger->log(\Joomla\CMS\Log\Log::WARNING, 'correctUrlLang: no language definition. Mode: '.self::LANGMGMT_REPLACECONTENTLANG);

                return $url;
            }

            $langSefValue = $this->getLanguageSef($language);

            return $this->correctUrlLangReplace($url, $langSefValue);
        }

        if ((self::LANGMGMT_REPLACELANG === (int) $this->langmgmt_enabled)
            && (!empty($this->langmgmt_default_language))) {
            $language = $this->langmgmt_default_language;
            $logger->log(\Joomla\CMS\Log\Log::INFO, 'correctUrlLang LANGMGMT_REPLACELANG '.$language);

            if (empty($language)) {
                $logger->log(\Joomla\CMS\Log\Log::WARNING, 'correctUrlLang: no language definition. Mode: '.self::LANGMGMT_REPLACELANG);

                return $url;
            }

            $langSefValue = $this->getLanguageSef($language);

            return $this->correctUrlLangReplace($url, $langSefValue);
        }

        if (self::LANGMGMT_REMOVELANG === (int) $this->langmgmt_enabled) {
            $logger->log(\Joomla\CMS\Log\Log::INFO, 'correctUrlLang LANGMGMT_REMOVELANG');

            return $this->correctUrlLangReplace($url, '');
        }

        return $url;
    }

    /**
     * correctUrlLangReplace.
     *
     * @param string $url          Param
     * @param string $langSefValue Param
     *
     * @return string
     */
    private function correctUrlLangReplace($url, $langSefValue)
    {
        $langSefValues = $this->getLanguageSefs();
        $searchs = [];

        // Url: http://blabla.com/index.php?option=com_content&view=article&id=999&Itemid=42&lang=en
        if (false !== strpos($url, self::LANGMGMT_SEF_VAR)) {
            foreach ($langSefValues as $l) {
                // Nothing to replace
                if ($l === $langSefValue) {
                    continue;
                }

                $searchs[] = '#'.self::LANGMGMT_SEF_VAR.$l.'#';
            }

            // Case 1: Replace lang=en
            if (empty($langSefValue)) {
                // Remove language from URL
                $replace = '';
            } else {
                // Replace language tag with default language
                $replace = self::LANGMGMT_SEF_VAR.$langSefValue;
            }
        } else {
            foreach ($langSefValues as $l) {
                // Nothing to replace
                if ($l === $langSefValue) {
                    continue;
                }

                $searchs[] = '#/'.$l.'/#';
            }

            // Case 2: check for lang tag in SEF url - http://blabla.com/en/extensions-for-joomla
            if (empty($langSefValue)) {
                // Remove language from URL
                $replace = '/';
            } else {
                // Replace language tag with default language
                $replace = '/'.$langSefValue.'/';
            }
        }

        $url = preg_replace($searchs, $replace, $url);

        return $url;
    }

    /**
     * getLanguageSefs.
     *
     * @return array
     */
    private function getLanguageSefs()
    {
        $languages = JLanguageHelper::getLanguages('lang_code');
        $tags = [];

        foreach ($languages as $language) {
            $tags[] = $language->sef;
        }

        return $tags;
    }
}
