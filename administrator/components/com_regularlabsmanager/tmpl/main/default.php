<?php
/**
 * @package         Regular Labs Extension Manager
 * @version         9.2.5
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2025 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text as JText;
use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\DownloadKey as RL_DownloadKey;
use RegularLabs\Library\Parameters as RL_Parameters;
use RegularLabs\Library\Version as RL_Version;

$config = RL_Parameters::getComponent('regularlabsmanager');

RL_Document::script('regularlabs.regular');
RL_Document::script('regularlabs.admin-form-descriptions');
RL_Document::script('regularlabsmanager.script');

RL_Document::style('regularlabs.admin-form');

$script = "document.addEventListener('DOMContentLoaded', function(){RegularLabs.Manager.init()});";
RL_Document::scriptDeclaration($script, 'RegularLabsManager', true, 'after');

// Download Key Popup
echo RL_DownloadKey::getOutputForComponent('all', false, true, 'RegularLabs.Manager.refresh();');
?>
    <div id="regularlabsmanager" class="position-relative rl-has-spinner mb-3 overflow-hidden" style="min-height: 200px;">
        <div class="position-absolute w-100 pe-none" style="height: 200px;">
            <div id="rlem_spinner" class="rl-spinner rl-spinner-lg"></div>
        </div>
        <div id="rlem_error" class="alert alert-danger hidden">
            <?php echo JText::_('RLEM_SOMETHING_WENT_WRONG'); ?>
        </div>
        <div id="rlem_content" class="hidden"></div>
    </div>
<?php

// Copyright
echo RL_Version::getFooter('REGULARLABSEXTENSIONMANAGER', $config->show_copyright);
