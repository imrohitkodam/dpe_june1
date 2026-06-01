<?php

/**
 * @package         Google Structured Data
 * @version         6.0.1 Pro
 * 
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2025 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

HTMLHelper::stylesheet('com_gsd/configNew.css', ['relative' => true, 'version' => 'auto']);

HTMLHelper::_('behavior.formvalidator');

$tabs_prefix = 'uitab';

?>

<div class="nr-app gsd-config-v2">
    <div class="nr-row">
        <div class="nr-main-container">
            <div class="nr-main-header config-header">
                <div class="wrapper">
                    <h2>
                        <?php echo Text::_('GSD') ?>
                        <span>/</span>
                        <span>
                            <?php echo Text::_('GSD_CONFIG'); ?>
                        </span>
                    </h2>
                    <div>
                        <joomla-toolbar-button id="toolbar-apply" task="config.apply" form-validation="">
                            <button class="button-apply btn btn-primary" type="button">
                                <span class="icon-apply" aria-hidden="true"></span>
                                &nbsp;<strong>Save</strong>
                            </button>
                        </joomla-toolbar-button>
                    </div>
                </div>
            </div>

            <div class="wrapper form-horizontal main">
                <form action="<?php echo Route::_('index.php?option=com_gsd&view=config'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
                    <?php 
                        echo HTMLHelper::_($tabs_prefix . '.startTabSet', 'tab', ['recall' => true, 'active' => 'globaldata']);

                        foreach ($this->form->getFieldSets() as $key => $fieldset)
                        {
                            echo HTMLHelper::_($tabs_prefix . '.addTab', 'tab', $fieldset->name, Text::_($fieldset->label));
                            echo $this->form->renderFieldSet($fieldset->name);
                            echo HTMLHelper::_($tabs_prefix . '.endTab');
                        }

                        echo HTMLHelper::_($tabs_prefix . '.endTabSet');
                    ?>

                    <?php echo HTMLHelper::_('form.token'); ?>

                    <input type="hidden" name="task" value="" />
                    <input type="hidden" name="name" value="config" />
                </form>
                
                <div class="footer text-center">
                    Made with ♥ by the Tassos Team
            
                    <div class="footer_links">
                        <a target="_blank" href="https://www.tassos.gr/contact">Support</a>
                        <span>/</span>
                        <a target="_blank" href="https://www.tassos.gr/docs/google-structured-data/">Documentation</a>
                        <span>/</span>
                        <a target="_blank" href="https://www.tassos.gr/joomla-extensions/">Free Joomla Extensions</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>