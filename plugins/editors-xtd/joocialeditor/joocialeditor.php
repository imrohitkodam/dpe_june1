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

use XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla\ScriptHelper;
use Joomla\CMS\Object\CMSObject;

defined('_JEXEC') || exit;

if (!defined('AUTOTWEET_API') && !@include_once(JPATH_ADMINISTRATOR.'/components/com_autotweet/api/autotweetapi.php')) {
    return;
}

/**
 * Editor JoocialEditor buton.
 *
 * @since       1.0
 */
class PlgButtonJoocialEditor extends \Joomla\CMS\Plugin\CMSPlugin
{
    /**
     * Constructor.
     *
     * @param object &$subject The object to observe
     * @param array  $config   An array that holds the plugin configuration
     */
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);

        // Com_jreviews,
        $integrated_components = $this->params->get('integrated_components', 'com_autotweet,com_content,com_easyblog,com_flexicontent,com_jcalpro,com_k2,com_zoo');
        $this->integrated_components = explode(',', $integrated_components);

        $this->loadLanguage();

        // Load component language file for use with plugin
        $jlang = \Joomla\CMS\Factory::getLanguage();
        $jlang->load('com_autotweet');
    }

    /**
     * Display the button.
     *
     * @param string $name Param
     *
     * @return array A four element array of (article_id, article_title, category_id, object)
     */
    public function onDisplay($name)
    {
        $jinput = \Joomla\CMS\Factory::getApplication()->input;
        $comp = $jinput->get('option');

        if (!in_array($comp, $this->integrated_components, true)) {
            return false;
        }

        $icon = \Joomla\CMS\Uri\Uri::root().'/media/com_autotweet/images/perfectpub-editorbutton.png';
        $css = '.icon-perfectpub-editorbutton { width: 18px; height: 16px; line-height: 16px; background: url("'.$icon.'") no-repeat scroll 2px 50% transparent!important;}';

        ScriptHelper::addStyleDeclaration($css);

        $link = AutoTweetDefaultView::addItemeditorHelperApp();

        if (EXTLY_J3) {
            $button = new JObject();
            $button->modal = true;
            $button->class = 'btn';
            $button->link = $link;
            $button->text = JText::_('PLG_JOOCIALEDITOR_BUTTON');
            $button->name = 'perfectpub-editorbutton';
            $button->options = "{handler: 'iframe', size: {x: 800, y: 500}}";

            return $button;
        }

        $button = new CMSObject;
        $button->modal   = true;
        $button->link    = $link;
        $button->text    = JText::_('PLG_JOOCIALEDITOR_BUTTON');
        $button->name    = 'perfectpub-editorbutton';
        $button->icon    = 'perfectpub';
        $button->iconSVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 270.933 270.933" height="1024" width="1023.999"><path d="M-333.862-37.055l310.86 183.264-3.28 360.846-314.141 177.582-310.862-183.264 3.28-360.846z" transform="matrix(.3707 -.00344 .00382 .33335 259.505 27.273)" stroke="#ff8900" stroke-width="25.802" fill="none"/><path d="M-13501.088 7453.537l92.099-160.275M-13593.655 7293.317l184.862 107.132M-13501.263 7240.638l92.598 159.95M-13501.233 7240.442l-92.149 159.898M-13593.455 7293.263l92.47 159.903M-13501.258 7240.591l.345 213.182M-13593.305 7400.136l184.491-106.82" fill="none" stroke="#ff8900" stroke-width="3.817" stroke-linecap="round" stroke-miterlimit="3" transform="matrix(1.25297 0 0 1.12671 17052.298 -8141.713)"/><path d="M156.664 18.084a20.11 18.084 0 01-19.678 18.08A20.11 18.084 0 01116.46 18.86 20.11 18.084 0 01135.257.038a20.11 18.084 0 0121.331 16.492M156.664 252.85a20.11 18.084 0 01-19.678 18.08 20.11 18.084 0 01-20.525-17.303 20.11 18.084 0 0118.796-18.824 20.11 18.084 0 0121.331 16.493M270.933 193.98a20.11 18.084 0 01-19.678 18.08 20.11 18.084 0 01-20.523-17.303 20.11 18.084 0 0118.794-18.823 20.11 18.084 0 0121.333 16.492M270.933 76.24a20.11 18.084 0 01-19.678 18.08 20.11 18.084 0 01-20.523-17.303 20.11 18.084 0 0118.794-18.823 20.11 18.084 0 0121.333 16.492M40.22 76.24a20.11 18.084 0 01-19.678 18.08A20.11 18.084 0 01.02 77.017a20.11 18.084 0 0118.794-18.823 20.11 18.084 0 0121.333 16.492M40.22 193.98a20.11 18.084 0 01-19.678 18.08A20.11 18.084 0 01.02 194.756a20.11 18.084 0 0118.794-18.823 20.11 18.084 0 0121.333 16.492M155.576 135.467a20.11 18.084 0 01-19.678 18.08 20.11 18.084 0 01-20.525-17.303 20.11 18.084 0 0118.796-18.824 20.11 18.084 0 0121.332 16.493" fill="#ff8900"/></svg>';
        $button->options = [
            'height'     => '500px',
            'width'      => '800px',
            'bodyHeight' => '70',
            'modalWidth' => '80',
        ];

        return $button;
    }
}
