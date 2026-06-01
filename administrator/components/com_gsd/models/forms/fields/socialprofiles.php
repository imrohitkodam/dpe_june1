<?php

/**
 * @package         Google Structured Data
 * @version         6.0.1 Pro
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2021 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

JLoader::register('NRFormField', JPATH_PLUGINS . '/system/nrframework/helpers/field.php');

class JFormFieldSocialProfiles extends NRFormField
{
    protected function getInput()
    {
        $services = $this->getServices();

        HTMLHelper::stylesheet('com_gsd/fields/socialprofiles.css', ['version' => 'auto', 'relative' => true]);

        $html = '<div class="gsd-social-profiles">';
        foreach ($services as $service => $icon)
        {
            $input_id = $this->id . '_' . $service;
            $html .= '<div class="control-group gsd-social-profiles--row">';

            $service_label = Text::_('GSD_SOCIALPROFILES_' . strtoupper($service));
            $translated_label = $service !== 'other_profiles' ? Text::sprintf('GSD_SOCIALPROFILES_ITEM_URL', $service_label) : $service_label;

            $label_text = $icon . '<span>' . $translated_label . '</span>';
            
            $html .= '<div class="control-label"><label class="control-label" for="' . $input_id . '">' . $label_text . '</label></div>';
            $html .= '<div class="gsd-social-profiles--row--content">';

            if ($service === 'other_profiles')
            {
                $html .= '<textarea rows="5" class="form-control" id="' . $input_id . '" name="' . $this->name . '[' . $service . ']" placeholder="Enter a URL per line">' . (isset($this->value[$service]) ? htmlspecialchars($this->value[$service], ENT_QUOTES) : '') . '</textarea>';
            }
            else
            {
                $html .= '<input type="url" class="form-control" id="' . $input_id . '" name="' . $this->name . '[' . $service . ']" value="' . (isset($this->value[$service]) ? htmlspecialchars($this->value[$service], ENT_QUOTES) : '') . '" placeholder="https://' . $service . '.com/profile">';
            }
            
            $html .= '</div></div>';
        }
        $html .= '</div>';

        return $html;
    }

    private function getServices()
    {
        return [
            'facebook' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none"><circle cx="12" cy="12" r="12" fill="#1877f2"></circle><path d="M18 12a6.02 6.02 0 0 0-6-6 6.02 6.02 0 0 0-6 6 5.98 5.98 0 0 0 5.025 5.925v-4.2h-1.5V12h1.5v-1.35c0-1.5.9-2.325 2.25-2.325.675 0 1.35.15 1.35.15v1.5h-.75c-.75 0-.975.45-.975.9V12h1.65l-.3 1.725h-1.425V18c3-.45 5.175-3 5.175-6z" fill="#fff"></path></svg>',
            'x' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none"><circle cx="12" cy="12" r="12" fill="currentColor"></circle><g><path d="M13.129 11.076L17.588 6H16.5315L12.658 10.4065L9.5665 6H6L10.676 12.664L6 17.9865H7.0565L11.1445 13.332L14.41 17.9865H17.9765L13.129 11.076ZM11.6815 12.7225L11.207 12.0585L7.4375 6.78H9.0605L12.1035 11.0415L12.576 11.7055L16.531 17.2445H14.908L11.6815 12.7225Z" fill="white"></path></g></svg>',
            'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"><circle cx="12" cy="12" r="12" fill="#f00073"></circle><path d="M12 7.084h2.458c.578 0 .867.145 1.084.217.289.145.506.217.723.434a1.5 1.5 0 0 1 .434.723c.072.217.145.506.217 1.084V12v2.458c0 .578-.145.867-.217 1.084-.145.289-.217.506-.434.723a1.5 1.5 0 0 1-.723.434c-.217.072-.506.145-1.084.217H12 9.542c-.578 0-.867-.145-1.084-.217-.289-.145-.506-.217-.723-.434s-.361-.434-.434-.723c-.072-.217-.145-.506-.217-1.084V12 9.542c0-.578.145-.867.217-1.084.145-.289.217-.506.434-.723s.434-.361.723-.434c.217-.072.506-.145 1.084-.217H12zM12 6H9.542a3.64 3.64 0 0 0-1.446.289 3.13 3.13 0 0 0-1.084.723c-.361.362-.506.651-.723 1.084-.145.361-.217.795-.289 1.446V12v2.458a3.64 3.64 0 0 0 .289 1.446 3.13 3.13 0 0 0 .723 1.084c.362.361.651.506 1.084.723.361.145.795.217 1.446.289H12h2.458a3.64 3.64 0 0 0 1.446-.289 3.13 3.13 0 0 0 1.084-.723c.361-.362.506-.651.723-1.084.145-.361.217-.795.289-1.446V12 9.542a3.64 3.64 0 0 0-.289-1.446 3.13 3.13 0 0 0-.723-1.084c-.362-.361-.651-.506-1.084-.723-.361-.145-.795-.217-1.446-.289H12zm0 2.892c-1.735 0-3.108 1.374-3.108 3.108s1.374 3.108 3.108 3.108 3.108-1.373 3.108-3.108S13.735 8.892 12 8.892zm0 5.133a2.024 2.024 0 1 1 0-4.048A2.02 2.02 0 0 1 14.024 12c0 1.084-.94 2.024-2.024 2.024zm3.181-4.483c.399 0 .723-.324.723-.723s-.324-.723-.723-.723-.723.324-.723.723.324.723.723.723z" fill="#fff"></path></svg>',
            'tiktok' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="-5 -5 34 34"><circle cx="12" cy="12" r="17" fill="##100f0d"></circle><path d="M19.589 6.686a4.793 4.793 0 0 1-3.77-4.245V2h-3.445v13.672a2.896 2.896 0 0 1-5.201 1.743l-.002-.001.002.001a2.895 2.895 0 0 1 3.183-4.51v-3.5a6.329 6.329 0 0 0-5.394 10.692 6.33 6.33 0 0 0 10.857-4.424V8.687a8.182 8.182 0 0 0 4.773 1.526V6.79a4.831 4.831 0 0 1-1.003-.104z" fill="#fff"></path></svg>',
            'linkedin' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"><circle cx="12" cy="12" r="12" fill="#2867b2"></circle><path d="M8.7 18H6.15V9.975H8.7V18zM7.425 8.85C6.6 8.85 6 8.25 6 7.425S6.675 6 7.425 6c.825 0 1.425.6 1.425 1.425S8.25 8.85 7.425 8.85zM18 18h-2.55v-4.35c0-1.275-.525-1.65-1.275-1.65s-1.5.6-1.5 1.725V18h-2.55V9.975h2.4V11.1c.225-.525 1.125-1.35 2.4-1.35 1.425 0 2.925.825 2.925 3.3V18H18z" fill="#fff"></path></svg>',
            'pinterest' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"><circle cx="12" cy="12" r="12" fill="#e60023"></circle><path d="M12.042 4.937c-3.984 0-7.244 3.26-7.244 7.244 0 2.988 1.811 5.523 4.346 6.61 0-.543 0-1.087.091-1.63.181-.634.905-3.984.905-3.984s-.272-.453-.272-1.177c0-1.087.634-1.901 1.358-1.901.634 0 .996.453.996 1.086s-.453 1.63-.634 2.535c-.181.724.362 1.358 1.177 1.358 1.358 0 2.264-1.72 2.264-3.894 0-1.63-1.086-2.807-2.988-2.807-2.173 0-3.531 1.63-3.531 3.441 0 .634.181 1.087.453 1.449.091.181.181.181.091.362 0 .091-.091.453-.181.543-.091.181-.181.272-.362.181-.996-.453-1.449-1.539-1.449-2.807 0-2.083 1.72-4.527 5.161-4.527 2.807 0 4.618 1.992 4.618 4.165 0 2.807-1.539 4.98-3.894 4.98-.815 0-1.539-.453-1.811-.905l-.543 1.992c-.181.543-.453 1.087-.724 1.539.634.181 1.358.272 2.083.272 3.984 0 7.244-3.26 7.244-7.244.09-3.622-3.169-6.881-7.153-6.881z" fill="#fff"></path></svg>',
            'youtube' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"><circle cx="12" cy="12" r="12" fill="#D63E22"></circle><path d="M18.65 8.588a1.73 1.73 0 0 0-1.225-1.225c-1.05-.263-5.513-.263-5.513-.263L6.4 7.363a1.73 1.73 0 0 0-1.225 1.225C5 9.725 5 12 5 12s0 2.275.263 3.412a1.73 1.73 0 0 0 1.225 1.225C7.538 16.9 12 16.9 12 16.9l5.512-.262a1.73 1.73 0 0 0 1.225-1.225C19 14.275 19 12 19 12s0-2.275-.35-3.412zM10.6 14.1V9.9l3.675 2.1-3.675 2.1z" fill="#fff"></path></svg>',
            'other_profiles' => ''
        ];
    }
}