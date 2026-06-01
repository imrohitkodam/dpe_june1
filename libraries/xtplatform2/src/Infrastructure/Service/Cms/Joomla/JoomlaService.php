<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

/*
 * @package     Extly Infrastructure Support
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @see         https://www.extly.com
 */

namespace XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla;

use XTP_BUILD\Extly\Infrastructure\Creator\CreatorTrait;
use XTP_BUILD\Extly\Infrastructure\Service\Cms\CmsException;
use XTP_BUILD\Extly\Infrastructure\Service\Cms\CmsServiceAbstract;
use XTP_BUILD\Extly\Infrastructure\Service\Cms\CmsSettingsRegistry;
use XTP_BUILD\Extly\Infrastructure\Service\Cms\Contracts\CmsServiceInterface;
use XTP_BUILD\Extly\Infrastructure\Support\Date;
use XTP_BUILD\Extly\Infrastructure\Support\Estring;
use XTP_BUILD\Extly\Infrastructure\Support\UrlHelper;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Cache\Exception\CacheExceptionInterface as CacheException;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Input\Cli as CMSCliInput;
use Joomla\CMS\Input\Input as CMSWebInput;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Session\Session as CMSSession;
use Joomla\CMS\Uri\Uri as Uri;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Console\Loader\LoaderInterface;
use Joomla\DI\Container;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry as Registry;
use Joomla\Session\Session as JoomlaSession;
use Joomla\Session\SessionInterface;

class JoomlaService extends CmsServiceAbstract implements CmsServiceInterface
{
    use CreatorTrait;
    use JoomlaVersionAwareTrait;

    protected $component;

    public function __construct($name, array $config = null)
    {
        parent::__construct($name, $config);

        // The minimum configuration is empty, we can't load the CMS
        if (empty($this->config[CmsSettingsRegistry::CONFIG_CMS_PATH_ROOT])) {
            $this->config[CmsSettingsRegistry::CONFIG_CMS_PATH_ROOT] = $this->detectCmsPathRoot();
        }

        // Nothing else to do
        if (empty($this->config[CmsSettingsRegistry::CONFIG_CMS_PATH_ROOT])) {
            throw new CmsException('Minimum configuration (CMS_PATH_ROOT) for Joomla has not been provided.');
        }

        \defined('JOOMLA_SITE_PATH') || \define('JOOMLA_SITE_PATH', $this->config[CmsSettingsRegistry::CONFIG_CMS_PATH_ROOT]);
        \defined('JOOMLA_SITE_INCLUDES_PATH') || \define('JOOMLA_SITE_INCLUDES_PATH', JOOMLA_SITE_PATH.'/includes');
        $this->detectJoomlaVersion();

        if (!class_exists('JConfig')) {
            $this->createCms();
        }

        $extensionAlias = $this->config[CmsSettingsRegistry::CONFIG_EXTENSION_ALIAS];
        $this->defineComponent('com_'.$extensionAlias);

        if ($this->isCli()) {
            $this->setServerHttpHost($this->getRootUri());
        }
    }

    /**
     * detectCmsPathRoot.
     *
     * @return string
     */
    public function detectCmsPathRoot()
    {
        // www/libraries/xtplatform2/src/Infrastructure/Service/Cms/Joomla/
        $pathRoot = realpath(__DIR__.'/../../../../../../..');

        if (($pathRoot) && (is_file($pathRoot.'/configuration.php'))) {
            return realpath($pathRoot);
        }

        return null;
    }

    /**
     * createCmsCli.
     */
    public function createCmsCli()
    {
        // We are a valid entry point.
        \defined('_JEXEC') || \define('_JEXEC', 1);
        \defined('DS') || \define('DS', \DIRECTORY_SEPARATOR);
        \defined('JPATH_BASE') || \define('JPATH_BASE', JOOMLA_SITE_PATH);

        // Load system defines
        if (is_file(JOOMLA_SITE_INCLUDES_PATH.'/defines.php')) {
            require_once JOOMLA_SITE_INCLUDES_PATH.'/defines.php';
        }

        if (!\defined('_JDEFINES')) {
            require_once JPATH_BASE.'/includes/defines.php';
        }

        if ($this->isJ4) {
            $this->createCmsCliJ4();
            $this->startCliAppJ4();
        } else {
            $this->createCmsCliJ3();
            \JLoader::registerAlias('JApplicationCliForJ3', '\\\Extly\\Infrastructure\\Service\\Cms\\Joomla\\CliApplicationForJ3', '5.0');
            $this->startCliAppJ3();
        }

        // Configure error reporting to maximum for CLI output.
        error_reporting(\E_ALL);
        ini_set('display_errors', 1);
    }

    public function startCliAppJ3()
    {
        Factory::getApplication('CliForJ3');

        // System configuration
        if (!\defined('JDEBUG')) {
            // System configuration.
            $config = new \JConfig();
            \define('JDEBUG', $config->debug);
        }
    }

    public function startCliAppJ4()
    {
        // Boot the DI container
        $container = Factory::getContainer();
        $container->alias('session', 'session.cli')
            ->alias('JSession', 'session.cli')
            ->alias(CMSSession::class, 'session.cli')
            ->alias(JoomlaSession::class, 'session.cli')
            ->alias(SessionInterface::class, 'session.cli');

        $container = Factory::getContainer();
        // $app = $container->get(\Joomla\Console\Application::class);

        $container->alias(ConsoleApplicationForJ4::class, 'ConsoleApplicationForJ4')
            ->share(
                'ConsoleApplicationForJ4',
                function (Container $container) {
                    $dispatcher = $container->get(DispatcherInterface::class);

                    // Console uses the default system language
                    $config = $container->get('config');
                    $locale = $config->get('language');
                    $debug = $config->get('debug_lang');

                    $lang = $container->get(LanguageFactoryInterface::class)->createLanguage($locale, $debug);

                    $app = new ConsoleApplicationForJ4($config, $dispatcher, $container, $lang);

                    // The session service provider needs Factory::$application, set it if still null
                    if (null === Factory::$application) {
                        Factory::$application = $app;
                    }

                    $app->setCommandLoader($container->get(LoaderInterface::class));
                    $app->setLogger($container->get('logger'));
                    $app->setSession($container->get(SessionInterface::class));
                    $app->setUserFactory($container->get(UserFactoryInterface::class));

                    return $app;
                },
                true
            );

        $app = $container->get(ConsoleApplicationForJ4::class);
    }

    public function loadExtensionLanguage($extension)
    {
        // Load Library language
        $lang = Factory::getApplication()->getLanguage();

        // Try the xtdir4alg_cli file in the current language
        // (without allowing the loading of the file in the default language)
        $lang->load($extension, JPATH_SITE, null, false, false)

        // Fallback to the xtdir4alg_cli file in the default language
        || $lang->load($extension, JPATH_SITE, null, true);

        Factory::getApplication('Site')->loadLanguage();
    }

    /**
     * boot.
     *
     * @param LumenApplication $pp Param
     */
    public function boot(\ArrayAccess $app)
    {
        $connections = $app['config']['database.connections'];

        $connections['mysql']['host'] = $this->getConnectionHost();
        $connections['mysql']['database'] = $this->getConnectionDatabase();
        $connections['mysql']['username'] = $this->getConnectionUsername();
        $connections['mysql']['password'] = $this->getConnectionPassword();
        $connections['mysql']['prefix'] = $this->getConnectionPrefix();

        $app['config']['database.connections'] = $connections;
    }

    public function getConnectionHost()
    {
        return Factory::getApplication()->get('host');
    }

    public function getConnectionDatabase()
    {
        return Factory::getApplication()->get('db');
    }

    public function getConnectionUsername()
    {
        return Factory::getApplication()->get('user');
    }

    public function getConnectionPassword()
    {
        return Factory::getApplication()->get('password');
    }

    public function getConnectionPrefix()
    {
        return Factory::getApplication()->get('dbprefix');
    }

    public function translate($value, $default = null)
    {
        $text = Text::_($value);

        if (!empty($text)) {
            return $text;
        }

        return Text::_($default);
    }

    public function getSetting($key, $default = null, $component = null)
    {
        if (!$component) {
            $component = $this->component;
        }

        if (!$component) {
            throw new CmsException('JoomlaService: Undefined component.');
        }

        if ($this->isCli()) {
            $params = ComponentHelper::getParams($component);
        } else {
            if ($this->isAdmin()) {
                $params = ComponentHelper::getParams($component);
            } else {
                $params = Factory::getApplication()->getParams($component);
            }
        }

        return $params->get($key, $default);
    }

    public function getProduct($key)
    {
        return $this->config['product'][$key];
    }

    public function getItem($id)
    {
    }

    public function getPlugin($name)
    {
        return new Plugin($this, $name);
    }

    public function getContentTypeEnumFactory()
    {
        return new ContentTypeEnum(ContentTypeEnum::JOOMLA_ARTICLE);
    }

    public function getUser($id = null)
    {
        return User::create($id);
    }

    public function getSession()
    {
        return new Session();
    }

    public function getMailClient()
    {
        return new Mail();
    }

    public function getProductInfo()
    {
        return new ProductInfo($this);
    }

    public function getRouter()
    {
        return new Router();
    }

    public function getSitename()
    {
        return Factory::getApplication()->get('sitename');
    }

    public function getTemporaryFolderPath()
    {
        return Factory::getApplication()->get('tmp_path');
    }

    public function getLogFolderPath()
    {
        return Factory::getApplication()->get('log_path');
    }

    public function getRootFolderPath()
    {
        return JPATH_ROOT;
    }

    public function getCacheFolderPath()
    {
        return JPATH_CACHE;
    }

    public function getRootUri()
    {
        if (isset($this->config[CmsSettingsRegistry::CONFIG_CMS_BASE_URL])) {
            return $this->config[CmsSettingsRegistry::CONFIG_CMS_BASE_URL];
        }

        $baseUrl = $this->getSetting(CmsSettingsRegistry::CONFIG_CMS_BASE_URL);

        if (!empty($baseUrl)) {
            return $baseUrl;
        }

        if ($this->isCli()) {
            throw new CmsException('Minimum configuration (CMS_BASE_URL) has not been provided.');
        }

        return Uri::root();
    }

    public function loadTemplate($key)
    {
        $options = new Registry();
        $options->set('component', 'none');
        $options->set('client', 'site');

        return LayoutHelper::render($key, null, JPATH_XT_COMPONENT_LAYOUTS, $options);
    }

    public function slugify($title)
    {
        return str_replace('&', '-', ApplicationHelper::stringUrlSafe($title));
    }

    public function cleanCache($includedComponents)
    {
        $includedComponents = Estring::create($includedComponents)
            ->convertListToArray();

        if (empty($includedComponents)) {
            return;
        }

        foreach ($includedComponents as $component) {
            $this->cleanCacheByGroup($component);
        }

        return true;
    }

    public function getPageLimit()
    {
        return Factory::getApplication()->get('list_limit');
    }

    public function getWebserviceSecretKey()
    {
        return sha1(Factory::getApplication()->get('secret').Factory::getApplication()->get('password'));
    }

    public function getApiToken()
    {
        $webserviceApiToken = $this->getSetting(CmsSettingsRegistry::WEBSERVICE_API_KEY);

        if (!empty($webserviceApiToken)) {
            return $webserviceApiToken;
        }

        return Factory::getApplication()->getSession()->getFormToken();
    }

    public function defineComponent($component)
    {
        $this->component = $component;

        \defined('JPATH_COMPONENT_ADMINISTRATOR') ||
            \define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR.'/components/'.$component);

        // Load Library language
        $lang = Factory::getApplication()->getLanguage();

        $lang->load($component, JPATH_ADMINISTRATOR, null, false, false)
            || $lang->load($component, JPATH_ADMINISTRATOR, null, true)
            || $lang->load($component, JPATH_SITE, null, false, false)
            || $lang->load($component, JPATH_SITE, null, true);

        \defined('JPATH_XT_COMPONENT') || \define(
            'JPATH_XT_COMPONENT',
            JPATH_ADMINISTRATOR.'/components/'.$component
        );

        \defined('JPATH_XT_COMPONENT_LAYOUTS') || \define('JPATH_XT_COMPONENT_LAYOUTS', JPATH_XT_COMPONENT.'/layouts');
    }

    /**
     * setServerHttpHost.
     *
     * @param string $cmsBaseUrl Param
     */
    public function setServerHttpHost($cmsBaseUrl)
    {
        $_SERVER['HTTP_HOST'] = UrlHelper::create()->getHost($cmsBaseUrl);
    }

    /**
     * getTimezone.
     *
     * @return string
     */
    public function getTimezone()
    {
        return Factory::getApplication()->get('offset');
    }

    /**
     * isMultilingualSite.
     *
     * @return string
     */
    public function isMultilingualSite()
    {
        return PluginHelper::isEnabled('system', 'languagefilter');
    }

    /**
     * getCurrentSefCode.
     *
     * @return string
     */
    public function getCurrentSefCode()
    {
        $webInput = new CMSWebInput();
        $lang = $webInput->get('lang');

        if (!empty($lang)) {
            // Check if Joomla has already auto-translated the SefCode to LangCode
            if (false !== strpos($lang, '-')) {
                // Return a SefCode!
                return $this->translateLangCode2SefCode($lang);
            }

            return $lang;
        }

        $uri = Uri::getInstance();
        $lang = $uri->getVar('lang');

        if (!empty($lang)) {
            return $lang;
        }

        if (!$this->isCli()) {
            return $this->getDefaultSefCode();
        }

        $cliInput = new CMSCliInput();
        $lang = $cliInput->get('lang');

        if (!empty($lang)) {
            return $lang;
        }

        // Not detected, then the default Sef Code
        return $this->getDefaultSefCode();
    }

    /**
     * getCurrentLanguageCode.
     *
     * @return string
     */
    public function getCurrentLanguageCode()
    {
        $langSefCode = $this->getCurrentSefCode();

        if (!empty($langSefCode)) {
            return $this->translateSefCode2LangCode($langSefCode);
        }

        $siteLanguage = Factory::getApplication()->getLanguage()->getTag();

        if (!empty($siteLanguage)) {
            return $siteLanguage;
        }

        return $this->getDefaultLanguageCode();
    }

    /**
     * getDefaultLanguageCode.
     *
     * @return string
     */
    public function getDefaultLanguageCode()
    {
        $langTag = Factory::getApplication()->getLanguage()->getTag();

        if (!empty($langTag)) {
            return $langTag;
        }

        return $this->getSetting('site', 'en-GB', 'com_languages');
    }

    /**
     * getDefaultSefCode.
     *
     * @return string
     */
    public function getDefaultSefCode()
    {
        $langCode = $this->getDefaultLanguageCode();

        return $this->translateLangCode2SefCode($langCode);
    }

    public function translateSefCode2LangCode($langSefCode)
    {
        $langs = LanguageHelper::getLanguages('sef');

        if (isset($langs[$langSefCode])) {
            $lang = $langs[$langSefCode];

            return $lang->lang_code;
        }

        // There is some inconsistency somewhere,
        // the language has been unpublished

        return null;
    }

    public function translateLangCode2SefCode($langCode)
    {
        $langs = LanguageHelper::getLanguages('lang_code');

        if (isset($langs[$langCode])) {
            $lang = $langs[$langCode];

            return $lang->sef;
        }

        // There is some inconsistency somewhere,
        // the language has been unpublished

        return null;
    }

    /**
     * getCurrentLanguageCodeFilter.
     *
     * @return string
     */
    public function getCurrentLanguageCodeFilter()
    {
        return ['*', $this->getCurrentLanguageCode()];
    }

    public function getSefCodes()
    {
        $langs = LanguageHelper::getLanguages('sef');

        return array_keys($langs);
    }

    public function isAdmin()
    {
        $theApp = Factory::getApplication();

        return $theApp instanceof \Joomla\CMS\Application\AdministratorApplication;
    }

    public function getMenu($client = 'site')
    {
        $isMultilingualSite = $this->isMultilingualSite();

        if (!$isMultilingualSite) {
            return Factory::getApplication()->getMenu($client);
        }

        $currentLanguageObject = $this->getCurrentLanguageObject();

        $options = [
            'language' => $currentLanguageObject,
        ];

        // Create a Menu object
        $classname = '\JMenu'.ucfirst($client);
        $menu = new $classname($options);

        return $menu;
    }

    public function isMultisite()
    {
        return false;
    }

    public function getMultisiteCodes()
    {
        return false;
    }

    public function getCurrentMultisiteCode()
    {
        return false;
    }

    public function dateNullValue()
    {
        return $this->isJ4 ? null : Date::DATE_ZERO;
    }

    /**
     * Clean the cache.
     *
     * @param string $group    The cache group
     * @param int    $clientId The ID of the client
     */
    protected function cleanCacheByGroup($group, $clientId = 0)
    {
        $options = [
            'defaultgroup' => $group,
            'cachebase' => $clientId ?
                JPATH_ADMINISTRATOR.'/cache' :
                Factory::getApplication()->get('cache_path', JPATH_SITE.'/cache'),
            'result' => true,
        ];

        try {
            $cache = Cache::getInstance('callback', $options);
            $cache->clean();
        } catch (CacheException $exception) {
            $options['result'] = false;
        }

        if (class_exists('JEventDispatcher')) {
            \JEventDispatcher::getInstance()->trigger('onContentCleanCache', $options);
        }
    }

    private function createCmsCliJ3()
    {
        // Get the framework.
        require_once JPATH_LIBRARIES.'/import.legacy.php';

        // Bootstrap the CMS libraries.
        require_once JPATH_LIBRARIES.'/cms.php';

        // Import the configuration.
        require_once JPATH_CONFIGURATION.'/configuration.php';
    }

    private function createCmsCliJ4()
    {
        // Get the framework.
        require_once JPATH_BASE.'/includes/framework.php';
    }

    private function createCms()
    {
        // Avoid notices - REQUEST_METHOD
        $backupRequestMethod = null;

        if (isset($_SERVER['REQUEST_METHOD'])) {
            $backupRequestMethod = $_SERVER['REQUEST_METHOD'];
            $_SERVER['REQUEST_METHOD'] = null;
        }

        if (!isset($_SERVER['HTTP_HOST']) && (!empty($this->config[CmsSettingsRegistry::CONFIG_CMS_BASE_URL]))) {
            $this->setServerHttpHost($this->config[CmsSettingsRegistry::CONFIG_CMS_BASE_URL]);
        }
        // Avoid notices - REQUEST_METHOD

        $this->createCmsCli();

        // Avoid notices
        if ($backupRequestMethod) {
            $_SERVER['REQUEST_METHOD'] = $backupRequestMethod;
        }
        // Avoid notices
    }

    private function getCurrentLanguageObject()
    {
        $locale = $this->getCurrentLanguageCode();
        $debug = Factory::getApplication()->get('debug_lang');

        return Language::getInstance($locale, $debug);
    }

    private function createExtensionNamespaceMapJ4()
    {
        // Trait ExtensionNamespaceMapper: libraries/src/Application/ExtensionNamespaceMapper.php
        \JLoader::register('JNamespacePsr4Map', JPATH_LIBRARIES.'/namespacemap.php');
        $extensionPsr4Loader = new \JNamespacePsr4Map();
        $extensionPsr4Loader->load();
    }
}
