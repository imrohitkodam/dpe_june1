<?php
/**
 * @package         Modals
 * @version         12.3.5PRO
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://regularlabs.com
 * @copyright       Copyright © 2023 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace RegularLabs\Plugin\EditorButton\Modals;

defined('_JEXEC') or die;

use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\EditorButtonPopup as RL_EditorButtonPopup;

class Popup extends RL_EditorButtonPopup
{
    protected $extension         = 'modals';
    protected $require_core_auth = false;

    protected function loadScripts()
    {
        RL_Document::script('regularlabs.regular');
        RL_Document::script('regularlabs.admin-form');
        RL_Document::script('regularlabs.admin-form-descriptions');
        RL_Document::script('modals.popup');

        $script = "document.addEventListener('DOMContentLoaded', function(){RegularLabs.ModalsPopup.init()});";
        RL_Document::scriptDeclaration($script, 'Modals Button', true, 'after');
    }
}
