<?php
/**
 * @package         Regular Labs Library
 * @version         25.11.14282
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

namespace RegularLabs\Plugin\System\RegularLabs\Extension;

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Form\Form as JForm;
use Joomla\Event\Event;
use Joomla\Registry\Registry as JRegistry;
use RegularLabs\Library\Color as RL_Color;
use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\DownloadKey as RL_DownloadKey;
use RegularLabs\Library\FieldHelper as RL_FieldHelper;
use RegularLabs\Library\Input as RL_Input;
use RegularLabs\Library\Plugin\System as RL_SystemPlugin;
use RegularLabs\Library\RegEx as RL_RegEx;
use RegularLabs\Library\Uri as RL_Uri;
use RegularLabs\Plugin\System\RegularLabs\AdminMenu;
use RegularLabs\Plugin\System\RegularLabs\QuickPage;

defined('_JEXEC') or die;

final class RegularLabs extends RL_SystemPlugin
{
    static $_extra_events    = ['onAjaxRegularlabs' => 'onAjaxRegularlabs'];
    public $_enable_in_admin = true;

    public function getAjaxData()
    {
        $format = RL_Input::getString('format', 'json');

        if (RL_Input::getBool('getDownloadKey'))
        {
            return RL_DownloadKey::get();
        }

        if (RL_Input::getBool('checkDownloadKey'))
        {
            return $this->checkDownloadKey();
        }

        if (RL_Input::getBool('saveDownloadKey'))
        {
            return $this->saveDownloadKey();
        }

        if (RL_Input::getBool('saveColor'))
        {
            $this->saveColor();
        }

        $attributes = RL_Uri::getCompressedAttributes();
        $attributes = new JRegistry($attributes);

        $field_class = $attributes->get('field_class');

        if (empty($field_class) || ! class_exists($field_class))
        {
            return false;
        }

        $type = $attributes->get('type', '');

        $method = 'getAjax' . ucfirst($format) . ucfirst($type);

        $field_class = new $field_class;

        if ( ! method_exists($field_class, $method))
        {
            return false;
        }

        return $field_class->$method($attributes);
    }

    /**
     * @return  void
     */
    public function onAfterRender(): void
    {
        if ( ! RL_Document::isAdmin(true) || ! RL_Document::isHtml())
        {
            return;
        }

        $this->removeEmptyFormControlGroups();
        $this->removeFormColumnLayout();
        AdminMenu::combine();
    }

    /**
     * @return  void
     */
    public function onAfterRoute(): void
    {
        if ( ! is_file(JPATH_LIBRARIES . '/regularlabs/regularlabs.xml'))
        {
            if (JFactory::getApplication()->isClient('administrator'))
            {
                JFactory::getApplication()->enqueueMessage('The Regular Labs Library folder is missing or incomplete: ' . JPATH_LIBRARIES . '/regularlabs', 'error');
            }

            return;
        }

        QuickPage::render();
    }

    public function onAjaxRegularlabs(?Event $event = null)
    {
        $data = $this->getAjaxData();

        return $this->handleAjaxResult($data, $event);
    }

    /**
     * Normalizes the request data.
     *
     * @param string $context The context
     * @param object $data    The object
     * @param JForm  $form    The form
     *
     * @return  void
     */
    public function onContentNormaliseRequestData($context, $data, JForm $form)
    {
        if ( ! is_object($data) || empty($data->com_fields))
        {
            return;
        }

        foreach ($data->com_fields as $field_name => &$field_value)
        {
            RL_FieldHelper::correctFieldValue($field_name, $field_value);
        }
    }

    /**
     * @param string $buffer
     */
    protected function loadStylesAndScripts(string &$buffer): void
    {
        self::addStylesheetToInstaller();
    }

    /**
     * @throws Exception
     */
    private function addStylesheetToInstaller()
    {
        if (RL_Input::getCmd('option') !== 'com_installer')
        {
            return;
        }

        if ( ! self::hasRegularLabsMessages())
        {
            return;
        }

        RL_Document::style('regularlabs.admin-form');
    }

    /**
     * @return false|mixed|string|null
     * @throws Exception
     */
    private function checkDownloadKey()
    {
        $key       = RL_Input::getString('key');
        $extension = RL_Input::getString('extension', 'all');

        return RL_DownloadKey::isValid($key, $extension);
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function hasRegularLabsMessages()
    {
        foreach (JFactory::getApplication()->getMessageQueue() as $message)
        {
            if ( ! isset($message['message'])
                || ! str_contains($message['message'], 'class="rl-')
            )
            {
                continue;
            }

            return true;
        }

        return false;
    }

    private function removeEmptyFormControlGroups()
    {
        $html = $this->app->getBody();

        if ($html == '')
        {
            return;
        }

        $html = RL_RegEx::replace(
            '<div class="(control-label|controls)">\s*</div>',
            '',
            $html
        );

        $html = RL_RegEx::replace(
            '<div class="control-group">\s*</div>',
            '',
            $html
        );

        $this->app->setBody($html);
    }

    private function removeFormColumnLayout()
    {
        if ($this->app->isClient('site'))
        {
            return;
        }

        if (
            $this->app->input->get('option', '') != 'com_plugins'
            || $this->app->input->get('view', '') != 'plugin'
            || $this->app->input->get('layout', '') != 'edit'
        )
        {
            return;
        }

        $html = $this->app->getBody();

        if ($html == '')
        {
            return;
        }

        $html = str_replace('column-count-md-2 column-count-lg-3', '', $html);

        $this->app->setBody($html);
    }

    /**
     * @throws Exception
     */
    private function saveColor()
    {
        $table     = RL_Input::getCmd('table');
        $item_id   = RL_Input::getInt('item_id');
        $color     = RL_Input::getString('color');
        $id_column = RL_Input::getCmd('id_column', 'id');

        return RL_Color::save($table, $item_id, $color, $id_column);
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function saveDownloadKey()
    {
        $key = RL_Input::getString('key');

        return RL_DownloadKey::store($key);
    }
}
