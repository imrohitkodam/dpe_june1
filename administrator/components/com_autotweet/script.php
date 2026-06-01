<?php
/**
 * @author     Extly, CB <team@extly.com>
 * @copyright  Copyright (c)2012-2020 Extly, CB All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 *
 * @see       https://www.extly.com
 */
defined('_JEXEC') || exit();

if (!class_exists('XTF0FUtilsInstallscript')) {
    if (class_exists('F0FUtilsInstallscript')) {
        // Fallback f0f/F0FUtilsInstallscript
        class_alias('F0FUtilsInstallscript', 'XTF0FUtilsInstallscript');
    } else {
        // Fallback fof/FOFUtilsInstallscript
        class_alias('FOFUtilsInstallscript', 'XTF0FUtilsInstallscript');
    }
}

/**
 * Com_AutoTweetInstallerScript.
 *
 * @since       1.0
 */
class Com_AutoTweetInstallerScript extends XTF0FUtilsInstallscript
{
    /**
     * The component's name.
     *
     * @var string
     */
    protected $componentName = 'com_autotweet';

    /**
     * The title of the component (printed on installation and uninstallation messages).
     *
     * @var string
     */
    protected $componentTitle = 'Perfect Publisher';

    /**
     * The minimum PHP version required to install this extension.
     *
     * @var string
     */
    protected $minimumPHPVersion = '7.3.0';

    /**
     * The minimum Joomla! version required to install this extension.
     *
     * @var string
     */
    protected $minimumJoomlaVersion = '3.8.0';

    /**
     * The maximum Joomla! version this extension can be installed on.
     *
     * @var string
     */
    protected $maximumJoomlaVersion = '4.99.99';

    /**
     * The list of extra modules and plugins to install on component installation / update and remove on component
     * uninstallation.
     *
     * @var array
     */
    protected $installation_queue = [
        // * modules => { (folder) => { (module) => { (position), (published) } }* }*
    ];

    /**
     * The list of obsolete extra modules and plugins to uninstall on component upgrade / installation.
     *
     * @var array
     */
    protected $uninstallation_queue = [
        // * modules => { (folder) => { (module) }* }*
        'modules' => [
            'admin' => [
                'autotweet_latest',
            ],
            'site' => [
            ],
        ],

        // * plugins => { (folder) => { (element) }* }*
        'plugins' => [
            'installer' => [
                'autotweet',
            ],
            'system' => [
                'joocialgap',
            ],
        ],
    ];

    /**
     * Obsolete files and folders to remove from the free version only. This is used when you move a feature from the
     * free version of your extension to its paid version. If you don't have such a distinction you can ignore this.
     *
     * @var array
     */
    protected $removeFilesFree = [
        'files' => [
        ],
        'folders' => [
        ],
    ];

    /**
     * Obsolete files and folders to remove from both paid and free releases. This is used when you refactor code and
     * some files inevitably become obsolete and need to be removed.
     *
     * @var array
     */
    protected $removeFilesAllVersions = [
        'files' => [
            // Spanish - Deprecated files
            'administrator/language/es-ES/es-ES.mod_autotweet_latest.ini',
            'administrator/language/es-ES/es-ES.plg_content_autotweetopengraph.sys.ini',
            'administrator/language/es-ES/es-ES.plg_autotweet_autotweetpost.ini',
            'administrator/language/es-ES/es-ES.plg_system_autotweetautomator.sys.ini',
            'administrator/language/es-ES/es-ES.plg_content_autotweetopengraph.ini',
            'administrator/language/es-ES/es-ES.com_autotweet.sys.ini',
            'administrator/language/es-ES/es-ES.plg_autotweet_autotweetpost.sys.ini',
            'administrator/language/es-ES/es-ES.plg_system_autotweetsocialprofile.ini',
            'administrator/language/es-ES/es-ES.plg_system_autotweetcontent.sys.ini',
            'administrator/language/es-ES/es-ES.plg_system_autotweetsocialprofile.sys.ini',
            'administrator/language/es-ES/es-ES.plg_system_autotweetautomator.ini',
            'administrator/language/es-ES/es-ES.plg_system_autotweetcontent.ini',
            'administrator/language/es-ES/es-ES.mod_autotweet_latest.sys.ini',
            'administrator/language/es-ES/es-ES.com_autotweet.ini',
            'language/es-ES/es-ES.autotweet_cli.ini',
            'language/es-ES/es-ES.com_autotweet.ini',

            'administrator/components/com_autotweet/api/version.php',
            'administrator/components/com_autotweet/controllers/feedarticles.php',
            'administrator/components/com_autotweet/controllers/gpluschannels.php',
            'administrator/components/com_autotweet/controllers/pagespeedchannels.php',
            'administrator/components/com_autotweet/controllers/scoopitchannels.php',
            'administrator/components/com_autotweet/controllers/targets.php',
            'administrator/components/com_autotweet/controllers/userchannels.php',
            'administrator/components/com_autotweet/controllers/vkchannels.php',
            'administrator/components/com_autotweet/controllers/xingchannels.php',
            'administrator/components/com_autotweet/layouts/joocial/channeleditor/Scoopit.php',
            'administrator/components/com_autotweet/layouts/joocial/channeleditor/Tumblr.php',
            'administrator/components/com_autotweet/models/feedarticles.php',
            'administrator/components/com_autotweet/models/feedcontent.php',
            'administrator/components/com_autotweet/models/feedcontentcategories.php',
            'administrator/components/com_autotweet/models/feedk2.php',
            'administrator/components/com_autotweet/models/feedk2categories.php',
            'administrator/components/com_autotweet/models/feedzoo.php',
            'administrator/components/com_autotweet/models/feedzoocategories.php',
            'administrator/components/com_autotweet/models/targets.php',
            'administrator/components/com_autotweet/models/userchannels.php',
            'administrator/components/com_autotweet/tables/feedarticle.php',
            'administrator/components/com_autotweet/tables/feedcontent.php',
            'administrator/components/com_autotweet/tables/target.php',
            'administrator/components/com_autotweet/tables/userchannel.php',
            'administrator/components/com_autotweet/views/composer/tmpl/1-0-link.php',
            'administrator/components/com_autotweet/views/composer/tmpl/1-1-image.php',
            'administrator/components/com_autotweet/views/composer/tmpl/1-2-scheduler.php',
            'components/com_autotweet/controllers/mlogin.php',
            'components/com_autotweet/controllers/userchannels.php',
            'components/com_autotweet/views/request/tmpl/form.php',
            'components/com_autotweet/views/request/view.html.php',
            'components/com_autotweet/views/requests/tmpl/default.php',
            'components/com_autotweet/views/requests/view.html.php',
            'media/com_autotweet/js/channel/collections/fbalbums.js',
            'media/com_autotweet/js/channel/collections/gplusvalidations.js',
            'media/com_autotweet/js/channel/collections/licompanies.js',
            'media/com_autotweet/js/channel/collections/ligroups.js',
            'media/com_autotweet/js/channel/collections/livalidations.js',
            'media/com_autotweet/js/channel/collections/vkgroups.js',
            'media/com_autotweet/js/channel/collections/vkvalidations.js',
            'media/com_autotweet/js/channel/collections/xingvalidations.js',
            'media/com_autotweet/js/channel/models/fbalbum.js',
            'media/com_autotweet/js/channel/models/gplusvalidation.js',
            'media/com_autotweet/js/channel/models/licompany.js',
            'media/com_autotweet/js/channel/models/ligroup.js',
            'media/com_autotweet/js/channel/models/livalidation.js',
            'media/com_autotweet/js/channel/models/vkgroup.js',
            'media/com_autotweet/js/channel/models/vkvalidation.js',
            'media/com_autotweet/js/channel/models/xingvalidation.js',
            'media/com_autotweet/js/channel/views/fbalbum.js',
            'media/com_autotweet/js/channel/views/gplusvalidation.js',
            'media/com_autotweet/js/channel/views/licompany.js',
            'media/com_autotweet/js/channel/views/ligroup.js',
            'media/com_autotweet/js/channel/views/livalidation.js',
            'media/com_autotweet/js/channel/views/vkgroup.js',
            'media/com_autotweet/js/channel/views/vkvalidation.js',
            'media/com_autotweet/js/channel/views/xingvalidation.js',
            'media/com_autotweet/js/composer/agenda-controller.js',
            'media/com_autotweet/js/composer/agenda-service.js',
            'media/com_autotweet/js/composer/agendas-controller.js',
            'media/com_autotweet/js/composer/agendas-controller.min.js',
            'media/com_autotweet/js/composer/agendas-service.js',
            'media/com_autotweet/js/composer/agendas-service.min.js',
            'media/com_autotweet/js/composer/app.min.js',
            'media/com_autotweet/js/composer/directives.js',
            'media/com_autotweet/js/composer/directives.min.js',
            'media/com_autotweet/js/composer/editor-controller.js',
            'media/com_autotweet/js/composer/editor-controller.min.js',
            'media/com_autotweet/js/composer/jquery-extras.js',
            'media/com_autotweet/js/composer/message-controller.js',
            'media/com_autotweet/js/composer/request-helper.js',
            'media/com_autotweet/js/composer/requests-controller.js',
            'media/com_autotweet/js/composer/requests-controller.min.js',
            'media/com_autotweet/js/composer/requests-service.js',
            'media/com_autotweet/js/composer/requests-service.min.js',
            'media/com_autotweet/js/jootool.helper.min.js',
            'media/com_autotweet/js/jootool.js',
            'media/com_autotweet/js/jootool.min.js',
            'media/com_autotweet/js/target.js',
            'media/com_autotweet/js/target.min.js',
            'media/com_autotweet/js/userchannel.js',
            'media/com_autotweet/js/userchannel.min.js',

            // Migrate from v8 to v9
            'administrator/components/com_autotweet/script.autotweet.php',
            'media/com_autotweet/images/appstore.png',
            'media/com_autotweet/images/autotweet-icon.png',
            'media/com_autotweet/images/autotweet-logo-24.png',
            'media/com_autotweet/images/autotweet-logo-28.png',
            'media/com_autotweet/images/autotweet-logo-36.png',
            'media/com_autotweet/images/autotweet-logo.png',
            'media/com_autotweet/images/autotweettargetsbutton.png',
            'media/com_autotweet/images/circle.png',
            'media/com_autotweet/images/facebook.png',
            'media/com_autotweet/images/google-plus.png',
            'media/com_autotweet/images/googleplay.png',
            'media/com_autotweet/images/icon-32-calendar-o.png',
            'media/com_autotweet/images/icon-32-process.png',
            'media/com_autotweet/images/index.html',
            'media/com_autotweet/images/isologo-autotweet-32.png',
            'media/com_autotweet/images/Joocial-home-430.png',
            'media/com_autotweet/images/Joocial-home.jpg',
            'media/com_autotweet/images/joocial-logo-128.png',
            'media/com_autotweet/images/joocial-logo-16.jpg',
            'media/com_autotweet/images/joocial-logo-16.png',
            'media/com_autotweet/images/joocial-logo-20.png',
            'media/com_autotweet/images/joocial-logo-250.png',
            'media/com_autotweet/images/joocial-logo-256.png',
            'media/com_autotweet/images/joocial-logo-28.png',
            'media/com_autotweet/images/joocial-logo-280.png',
            'media/com_autotweet/images/joocial-logo-36.png',
            'media/com_autotweet/images/joocial-logo-75.png',
            'media/com_autotweet/images/joocialeditorbutton-j25.png',
            'media/com_autotweet/images/joocialeditorbutton.png',
            'media/com_autotweet/images/linkedin.png',
            'media/com_autotweet/images/menuj25',
            'media/com_autotweet/images/menuj25/bullhorn.png',
            'media/com_autotweet/images/menuj25/calendar.png',
            'media/com_autotweet/images/menuj25/index.html',
            'media/com_autotweet/images/menuj25/info.png',
            'media/com_autotweet/images/menuj25/leaf.png',
            'media/com_autotweet/images/menuj25/pencil-square.png',
            'media/com_autotweet/images/menuj25/random.png',
            'media/com_autotweet/images/menuj25/rss.png',
            'media/com_autotweet/images/menuj25/search-plus.png',
            'media/com_autotweet/images/menuj25/tasks.png',
            'media/com_autotweet/images/ok-circle.png',
            'media/com_autotweet/images/ok.png',
            'media/com_autotweet/images/pinterest.png',
            'media/com_autotweet/images/remove-circle.png',
            'media/com_autotweet/images/remove.png',
            'media/com_autotweet/images/twitter.png',

            // Remove old SQL scripts for Joomla 4
            'administrator/components/com_autotweet/sql/updates/mysql/6.4.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/6.4.1.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/6.4.2.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/6.4.3.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/6.4.4.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/6.4.5.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/6.5.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/6.5.1.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/6.5.2.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/6.6.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/6.6.1.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/6.6.2.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/6.7.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/6.7.1.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/6.8.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/6.9.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/7.0.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/7.1.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/7.2.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/7.3.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/7.3.1.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/7.3.2.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/7.3.3.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/7.4.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/7.4.4.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/7.5.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/7.5.2.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/7.6.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/7.6.4.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/7.7.2.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/7.7.3.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/7.7.4.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/7.7.5.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/7.8.1.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/7.9.1.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/7.9.7.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.0.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.0.5.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.1.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.11.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.11.1.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.12.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.14.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.14.1.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.17.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.18.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.19.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.2.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.2.1.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.20.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.23.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.24.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.30.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.4.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.5.1.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.7.0.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.7.2.sql',
            'administrator/components/com_autotweet/sql/updates/mysql/8.9.1.sql',

            // Remove old manifest filename for Joomla 4
            'administrator/components/com_autotweet/a_autotweet.xml',
        ],
        'folders' => [
            // Moved to preflight
            // 'administrator/components/com_autotweet/helpers',

            'administrator/components/com_autotweet/libraries',
            'administrator/components/com_autotweet/libs',
            'administrator/components/com_autotweet/vendor',
            'administrator/components/com_autotweet/views/feedarticle',
            'administrator/components/com_autotweet/views/gpluschannel',
            'administrator/components/com_autotweet/views/jomsocialchannel',
            'administrator/components/com_autotweet/views/lichannel',
            'administrator/components/com_autotweet/views/licompanychannel',
            'administrator/components/com_autotweet/views/ligroupchannel',
            'administrator/components/com_autotweet/views/mybusiness',
            'administrator/components/com_autotweet/views/scoopitchannel',
            'administrator/components/com_autotweet/views/target',
            'administrator/components/com_autotweet/views/targets',
            'administrator/components/com_autotweet/views/vkchannel',
            'administrator/components/com_autotweet/views/xingchannel',
            'components/com_autotweet/models',
            'components/com_autotweet/views/bloggerchannel',
            'components/com_autotweet/views/calendar',
            'components/com_autotweet/views/channels',
            'components/com_autotweet/views/composer',
            'components/com_autotweet/views/cpanels',
            'components/com_autotweet/views/easysocialchannel',
            'components/com_autotweet/views/facebookapps',
            'components/com_autotweet/views/fbchannel',
            'components/com_autotweet/views/feedarticle',
            'components/com_autotweet/views/gpluschannel',
            'components/com_autotweet/views/itemeditor',
            'components/com_autotweet/views/jomsocialchannel',
            'components/com_autotweet/views/lichannel',
            'components/com_autotweet/views/licompanychannel',
            'components/com_autotweet/views/ligroupchannel',
            'components/com_autotweet/views/lioauth2channel',
            'components/com_autotweet/views/lioauth2companychannel',
            'components/com_autotweet/views/mailchannel',
            'components/com_autotweet/views/mediumchannel',
            'components/com_autotweet/views/mlogin',
            'components/com_autotweet/views/nochannel',
            'components/com_autotweet/views/notauths',
            'components/com_autotweet/views/onesignalchannel',
            'components/com_autotweet/views/post',
            'components/com_autotweet/views/posts',
            'components/com_autotweet/views/pushalertchannel',
            'components/com_autotweet/views/pushwooshchannel',
            'components/com_autotweet/views/request',
            'components/com_autotweet/views/requests',
            'components/com_autotweet/views/scoopitchannel',
            'components/com_autotweet/views/telegramchannel',
            'components/com_autotweet/views/tumblrchannel',
            'components/com_autotweet/views/twchannel',
            'components/com_autotweet/views/userchannels',
            'components/com_autotweet/views/vkchannel',
            'components/com_autotweet/views/xingchannel',
            'media/com_autotweet/js/jootool',
            'media/com_autotweet/js/liveupdate',
            'media/com_autotweet/js/target',
            'media/com_autotweet/js/userchannel',

            // Migrate from v8 to v9
            'media/com_autotweet/images/menuj25',

            // Refactor Layouts
            'administrator/components/com_autotweet/layouts/autotweet',
            'administrator/components/com_autotweet/layouts/joocial',
            'components/com_autotweet/views/channel',
            'administrator/components/com_autotweet/views/channel/tmpl/bloggerchannel-post.php',
            'administrator/components/com_autotweet/views/channel/tmpl/mailchannel-post.php',
            'administrator/components/com_autotweet/views/channel/tmpl/mediumchannel-post.php',
            'administrator/components/com_autotweet/views/channel/tmpl/tumblrchannel-post.php',

            'libraries/autotweet/vendor/anibalsanchez/perfect-publisher-social-packages/vendor_prefixed/vendor_prefixed/vkcom',
        ],
    ];

    /**
     * A list of scripts to be copied to the "cli" directory of the site.
     *
     * @var array
     */
    protected $cliScriptFiles = [
    ];

    public function preflight($type, $parent)
    {
        $go = parent::preflight($type, $parent);

        if ($go) {
            $this->removeFilesAndFolders([
                'folders' => [
                    'administrator/components/com_autotweet/helpers',
                ],
            ]);
        }
    }

    /**
     * Runs after install, update or discover_update. In other words, it executes after Joomla! has finished installing
     * or updating your component. This is the last chance you've got to perform any additional installations, clean-up,
     * database updates and similar housekeeping functions.
     *
     * @param string     $type   install, update or discover_update
     * @param JInstaller $parent Parent object
     */
    public function postflight($type, $parent)
    {
        $this->isPaid = false;
        $this->renderType = $type;

        parent::postflight($type, $parent);

        $this->uninstallPostInstallationMessages();
        $this->moveModulePosition('mod_joocial_menu', 'title');
    }

    /**
     * Renders the post-installation message.
     *
     * @param bool       $status                     Param
     * @param bool       $fofInstallationStatus      Param
     * @param bool       $strapperInstallationStatus Param
     * @param JInstaller $parent                     Parent object
     */
    public function renderPostInstallation($status, $fofInstallationStatus, $strapperInstallationStatus, $parent)
    {
        $this->warnAboutJSNPowerAdmin();
        $this->_renderPostInstallation($status, $fofInstallationStatus, $strapperInstallationStatus, $parent);
    }

    /**
     * Installs Extly Strapper if necessary.
     *
     * @param JInstaller $parent The parent object
     *
     * @return array The installation status
     */
    protected function installStrapper($parent)
    {
        return false;
    }

    /**
     * Uninstalls subextensions (modules, plugins) bundled with the main extension.
     *
     * @param JInstaller $parent The parent object
     *
     * @return stdClass The subextension uninstallation status
     */
    protected function uninstallSubextensions($parent)
    {
        $db = \Joomla\CMS\Factory::getDBO();

        $query = 'SELECT type, folder, element, client_id FROM '.$db->quoteName('#__extensions')
            .' WHERE ('.
            $db->quoteName('type').' = '.$db->Quote('plugin').') AND ('.

            $db->quoteName('element').' like '.$db->Quote('%autotweet%').' OR '.
            $db->quoteName('name').' like '.$db->Quote('%AutoTweet%').' OR '.
            $db->quoteName('element').' like '.$db->Quote('%joocial%').' OR '.
            $db->quoteName('name').' like '.$db->Quote('%Joocial%')

            .')'.' ORDER BY '.$db->quoteName('extension_id');

        $db->setQuery($query);
        $extensions = $db->loadAssocList();

        foreach ($extensions as $ext) {
            $type = $ext['type'];
            $folder = $ext['folder'];
            $element = $ext['element'];
            $client_id = $ext['client_id'];

            /*
            if ($type == 'module')
            {
                Admin
                if ($client_id == 1)
                {
                    if (!isset($this->installation_queue['modules']['admin'][$element]))
                    {
                        $this->installation_queue['modules']['admin'][$element] = 1;
                    }
                }
                else
                {
                    Site
                    if (!isset($this->installation_queue['modules']['site'][$element]))
                    {
                        $this->installation_queue['modules']['site'][$element] = 1;
                    }
                }
            }
            */

            if ('plugin' === $type) {
                if (!isset($this->installation_queue['plugins'][$folder][$element])) {
                    $this->installation_queue['plugins'][$folder][$element] = 1;
                }
            }
        }

        return parent::uninstallSubextensions($parent);
    }

    /**
     * renderPostUninstallation.
     *
     * @param bool       $status Param
     * @param JInstaller $parent Parent object
     */
    protected function renderPostUninstallation($status, $parent)
    {
        ?>
		<h2><?php echo $this->componentTitle; ?> Uninstallation Status</h2>
		<?php
        parent::renderPostUninstallation($status, $parent);
    }

    protected function uninstallPostInstallationMessages()
    {
        // Make sure it's Joomla! 3.2.0 or later
        if (!version_compare(JVERSION, '3.2.0', 'ge')) {
            return;
        }

        // Get the extension ID for our component
        $db = XTF0FPlatform::getInstance()->getDbo();
        $query = $db->getQuery(true);
        $query->select('extension_id')
            ->from('#__extensions')
            ->where($db->qn('type').' = '.$db->q('component'))
            ->where($db->qn('element').' = '.$db->q($this->componentName));
        $db->setQuery($query);

        try {
            $ids = $db->loadColumn();
        } catch (Exception $exc) {
            return;
        }

        if (empty($ids)) {
            return;
        }

        $extension_id = array_shift($ids);

        $query = $db->getQuery(true)
            ->delete($db->qn('#__postinstall_messages'))
            ->where($db->qn('extension_id').' = '.$db->q($extension_id));

        try {
            $db->setQuery($query)->execute();
        } catch (Exception $e) {
            return;
        }
    }

    /**
     * Renders the message after installing or upgrading the component.
     *
     * @param bool       $status                     Param
     * @param bool       $fofInstallationStatus      Param
     * @param bool       $strapperInstallationStatus Param
     * @param JInstaller $parent                     Parent object
     */
    private function _renderPostInstallation($status, $fofInstallationStatus, $strapperInstallationStatus, $parent)
    {
    }

    /**
     * warnAboutJSNPowerAdmin.
     *
     * The PowerAdmin extension makes menu items disappear. People assume it's our fault. JSN PowerAdmin authors don't
     * own up to their software's issue. I have no choice but to warn our users about the faulty third party software.
     */
    private function warnAboutJSNPowerAdmin()
    {
        $db = \Joomla\CMS\Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->qn('#__extensions'))
            ->where($db->qn('type').' = '.$db->q('component'))
            ->where($db->qn('element').' = '.$db->q('com_poweradmin'))
            ->where($db->qn('enabled').' = '.$db->q('1'));
        $hasPowerAdmin = $db->setQuery($query)->loadResult();

        if (!$hasPowerAdmin) {
            return;
        }

        $query = $db->getQuery(true)
            ->select('manifest_cache')
            ->from($db->qn('#__extensions'))
            ->where($db->qn('type').' = '.$db->q('component'))
            ->where($db->qn('element').' = '.$db->q('com_poweradmin'))
            ->where($db->qn('enabled').' = '.$db->q('1'));
        $paramsJson = $db->setQuery($query)->loadResult();
        $jsnPAManifest = new JRegistry();
        $jsnPAManifest->loadString($paramsJson, 'JSON');
        $version = $jsnPAManifest->get('version', '0.0.0');

        if (version_compare($version, '2.1.2', 'ge')) {
            return;
        }

        echo <<< HTML
<div class="well" style="margin: 2em 0;">
<h1 style="font-size: 32pt; line-height: 120%; color: red; margin-bottom: 1em">
	WARNING: Menu items for {$this->componentName} might not be displayed on your site.</h1>
<p style="font-size: 18pt; line-height: 150%; margin-bottom: 1.5em">
	We have detected that you are using JSN PowerAdmin on your site. This software ignores Joomla! standards and
	<b>hides</b> the Component menu items to {$this->componentName} in the administrator backend of your site. Unfortunately we
	can't provide support for third party software. Please contact the developers of JSN PowerAdmin for support
	regarding this issue.
</p>
<p style="font-size: 18pt; line-height: 120%; color: green;">
	Tip: You can disable JSN PowerAdmin to see the menu items to {$this->componentName}.
</p>
</div>

HTML;
    }

    private function moveModulePosition($module, $position)
    {
        $db = \Joomla\CMS\Factory::getDBO();

        // Find the module ID
        $sql = $db->getQuery(true)
            ->select($db->qn('id'))
            ->from($db->qn('#__modules'))
            ->where($db->qn('module').' = '.$db->q($module))
            ->where($db->qn('client_id').' = '.$db->q('1'));
        $db->setQuery($sql);
        $id = $db->loadResult();

        if (!$id) {
            return;
        }

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__modules'))
            ->set($db->quoteName('position').' = '.$db->quote($position))
            ->where($db->quoteName('id').' = '.$db->quote($id));

        try {
            $db->setQuery($query)->execute();
        } catch (Exception $e) {
            return;
        }
    }
}
