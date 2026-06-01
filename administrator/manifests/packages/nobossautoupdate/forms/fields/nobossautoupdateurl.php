<?php
/**
 * @package			No Boss Extensions
 * @subpackage  	No Boss Autoupdate
 * @author			No Boss Technology <contact@nobosstechnology.com>
 * @copyright		Copyright (C) 2019 No Boss Technology. All rights reserved.
 * @license			GNU Lesser General Public License version 3 or later; see <https://www.gnu.org/licenses/lgpl-3.0.en.html>
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('text');


class JFormFieldNobossautoupdateurl extends JFormField{
    protected $type = "nobossautoupdateurl";

    protected function getInput() {
        $url = JUri::root() . 'index.php?option=com_nobossajax&library=noboss.util.nobossautoupdate&method=updateNobossExt&format=raw';

        $html = "<div class='alert alert-info'>";
            $html .= "<div class='alert-message'><a href='{$url}' target='_blank'>{$url}</a></div>";
        $html .= "</div>";

        return $html;
    }
}
