<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class plgButtonLogmanlinker extends PlgKoowaEditorButton
{
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();
    }

    public function onDisplay($name)
    {
        $button        = new JObject();
        $button->class = 'btn';

        $editor = $this->getEditor();

        $is_joomlatools_extension = false;
        $is_joomla4               = version_compare(JVERSION, '4.0', '>=');
        $is_joomla5               = version_compare(JVERSION, '5.0', '>=');

        $query = 'option=com_logman&view=linker&e_name='.$name.'&tmpl=koowa&itemless=1';

        $link = $this->getLink($query);

        try
        {
            if (class_exists('Koowa') && class_exists('KObjectManager')) {
                $is_joomlatools_extension = (boolean) KObjectManager::getInstance()->isRegistered('dispatcher');
            }
        }
        catch (Exception $e) {}

        $button->set('modal', true);
        $button->set('options', "{handler: 'iframe', size: {x: 1000, y: 600}}");
        $button->set('link', $link);

        if ($is_joomlatools_extension && $is_joomla5)
        {
            $button->set('action', 'modal'); 

            $button->set('options', [
                'textHeader' => JText::_('PLG_LOGMANLINKER_BUTTON_LINKER'),
                'iconHeader' => 'icon-link',
                'popupType'  => 'iframe'
            ]);
        }

        if ($is_joomla4)
        {
            $button->icon    = 'link';
            $button->iconSVG = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 -1 10 10">
                                    <path d="M5.88.03c-.18.01-.36.03-.53.09-.27.1-.53.25-.75.47a.5.5 0 1 0 .69.69c.11-.11.24-.17.38-.22.35-.12.78-.07 1.06.22.39.39.39 1.04 0 1.44l-1.5 1.5c-.44.44-.8.48-1.06.47-.26-.01-.41-.13-.41-.13a.5.5 0 1 0-.5.88s.34.22.84.25c.5.03 1.2-.16 1.81-.78l1.5-1.5c.78-.78.78-2.04 0-2.81-.28-.28-.61-.45-.97-.53-.18-.04-.38-.04-.56-.03zm-2 2.31c-.5-.02-1.19.15-1.78.75l-1.5 1.5c-.78.78-.78 2.04 0 2.81.56.56 1.36.72 2.06.47.27-.1.53-.25.75-.47a.5.5 0 1 0-.69-.69c-.11.11-.24.17-.38.22-.35.12-.78.07-1.06-.22-.39-.39-.39-1.04 0-1.44l1.5-1.5c.4-.4.75-.45 1.03-.44.28.01.47.09.47.09a.5.5 0 1 0 .44-.88s-.34-.2-.84-.22z" />
                                </svg>';
        }

        $button->set('text', JText::_('PLG_LOGMANLINKER_BUTTON_LINKER'));
        $button->set('name', 'link');

        JHtml::_('stylesheet', 'media/koowa/com_koowa/css/modal-override.css');

        return $button;
    }
}
