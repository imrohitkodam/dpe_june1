<?php
/**
 * @package     Multiagency
 * @subpackage  com_multiagency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2026 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('jquery.framework');
$doc = Factory::getDocument();
$doc->addStyleSheet('templates/shaper_helix3/css/custom.css');
$doc->addScript(Uri::root(true) . '/media/com_dpe/js/dpe_ucm_tab.js');
?>

<div class="row-fluid">
    <div id="export-users-container" class="form-horizontal">

        <div class="modal-header">
            <h3 class="mb-0">
                <?php echo Text::_('COM_MULTIAGENCY_EXPORT_USERS'); ?>
            </h3>
        </div>

        <div class="modal-body">
            <form id="exportUsersForm">
               <div id="export-messages" class="alert alert-danger"></div> 
                <!-- Select Columns -->
                <div class="form-group row align-items-center">
                    <label class="col-md-3 control-label">
                        <strong><?php echo Text::_('COM_MULTIAGENCY_SELECT_COLUMNS'); ?> :</strong>
                    </label>

                    <div class="col-md-9 d-flex flex-wrap gap-3">

                        <!-- Mandatory (checked + locked) -->
                        <label class="checkbox-inline mb-0">
                            <input type="checkbox" checked disabled>
                            <?php echo Text::_('COM_MULTIAGENCY_USERS_NAME'); ?>
                        </label>
                        <input type="hidden" name="export_columns[]" value="name">

                        <label class="checkbox-inline mb-0">
                            <input type="checkbox" checked disabled>
                            <?php echo Text::_('COM_MULTIAGENCY_USERS_EMAIL'); ?>
                        </label>
                        <input type="hidden" name="export_columns[]" value="email">

                        <label class="checkbox-inline mb-0">
                            <input type="checkbox" checked disabled>
                            <?php echo Text::_('COM_MULTIAGENCY_ORGANISATION'); ?>
                        </label>
                        <input type="hidden" name="export_columns[]" value="organisation">

                        <!-- Optional -->
                        <label class="checkbox-inline mb-0">
                            <input type="checkbox" name="export_columns[]" value="jobtitle">
                            <?php echo Text::_('COM_MULTIAGENCY_USERS_JOBTITLE'); ?>
                        </label>

                        <label class="checkbox-inline mb-0">
                            <input type="checkbox" name="export_columns[]" value="role">
                            <?php echo Text::_('COM_MULTIAGENCY_USERS_ROLE'); ?>
                        </label>

                        <label class="checkbox-inline mb-0">
                            <input type="checkbox" name="export_columns[]" value="dpelead">
                            <?php echo Text::_('COM_MULTIAGENCY_FORM_DPELEAD_LIST'); ?>
                        </label>

                        <label class="checkbox-inline mb-0">
                            <input type="checkbox" name="export_columns[]" value="registerDate">
                            <?php echo Text::_('COM_MULTIAGENCY_CREATED_DATE'); ?>
                        </label>

                    </div>
                </div>

            </form>

            <!-- Action -->
            <div class="control-group mt-3">
                <button type="button" class="btn btn-primary" id="export-users-btn">
                    <?php echo Text::_('COM_MULTIAGENCY_EXPORT'); ?>
                </button>
            </div>
        </div>

        <div class="modal-footer">
            <!-- Optional footer -->
        </div>

    </div>
</div>
