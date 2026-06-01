<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class plgButtonDoclink extends PlgKoowaEditorButton
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

        $is_joomlatools_extension = false;
        $is_joomla4               = version_compare(JVERSION, '4.0', '>=');
        $is_joomla5               = version_compare(JVERSION, '5.0', '>=');

        $query = 'option=com_docman&view=doclink&itemless=1&e_name='.$name;

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
                'textHeader' => JText::_('PLG_DOCLINK_BUTTON_DOCUMENT'),
                'iconHeader' => 'icon-download',
                'popupType'  => 'iframe'
            ]);
        }
    
        if ($is_joomla4)
        {
            $button->icon    = 'download';
            $button->iconSVG = '<svg fill="black" width="24" height="24" viewBox="-30 -50 400 400" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" 
                                    clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414" role="img" aria-hidden="true" focusable="false">
                                    <path fill="black" d="M300 190.766c-.003-19.314-4.91-38.863-15.238-56.75-15.213-26.345-39.769-45.191-69.153-53.061-29.387-7.87-60.077-3.83-86.417 11.373-10.383 5.998-13.939 19.274-7.947 29.655 5.989 10.386 19.268 13.939 29.655 7.944 16.295-9.409 35.295-11.907 53.472-7.041 18.185 4.882 33.382 16.539 42.789 32.841 19.427 33.645 7.857 76.829-25.794 96.258-10.384 6.001-13.934 19.277-7.945 29.657 5.998 10.381 19.275 13.94 29.656 7.948 36.494-21.076 56.916-59.439 56.922-98.824"></path><path d="M230.226 42.998c-.003-7.383-3.765-14.572-10.559-18.645-35.48-21.251-79.752-21.618-115.544-.956-26.349 15.21-45.188 39.767-53.062 69.153-7.879 29.383-3.836 60.075 11.38 86.417 10.232 17.729 25.035 32.369 42.817 42.342 10.457 5.865 23.684 2.15 29.55-8.311 5.865-10.451 2.146-23.681-8.308-29.55-10.976-6.158-20.129-15.21-26.466-26.194-9.412-16.296-11.911-35.287-7.038-53.472 4.87-18.17 16.53-33.374 32.837-42.786 22.149-12.789 49.552-12.558 71.522.607 10.285 6.159 23.617 2.816 29.784-7.472a21.606 21.606 0 0 0 3.087-11.133"></path><path d="M227.799 178.126c-.003-.618-.006-1.228-.011-1.842-.199-11.986-10.067-21.553-22.057-21.356-11.866.188-21.358 9.871-21.358 21.699v.351c.45 25.661-13.092 49.469-35.234 62.251-16.305 9.416-35.296 11.91-53.478 7.038-18.183-4.873-33.38-16.526-42.789-32.835-6.338-10.974-9.601-23.465-9.45-36.013.004-.096.004-.188.004-.278 0-11.863-9.548-21.556-21.44-21.701-11.99-.148-21.827 9.449-21.975 21.437-.284 20.405 5.023 40.531 15.265 58.269 15.203 26.337 39.764 45.186 69.147 53.056 29.383 7.873 60.075 3.833 86.417-11.373 35.255-20.363 56.956-58.052 56.959-98.703"></path>
                                </svg>';
        }

        $button->set('text', JText::_('PLG_DOCLINK_BUTTON_DOCUMENT'));
        $button->set('name', 'download');

        JHtml::_('stylesheet', 'media/koowa/com_koowa/css/modal-override.css');

        return $button;
    }
}
