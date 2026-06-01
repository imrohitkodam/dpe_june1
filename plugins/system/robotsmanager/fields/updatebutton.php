<?php
defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

class JFormFieldUpdatebutton extends FormField
{
    protected $type = 'updatebutton';

    protected function getInput()
    {
        HTMLHelper::_('script', 'plg_system_robotsmanager/js/batch.js', ['version' => 'auto', 'relative' => true]);

        $buttonText = Text::_('PLG_SYSTEM_ROBOTSMANAGER_UPDATE_BUTTON');
        $title = Text::_('PLG_SYSTEM_ROBOTSMANAGER_UPDATE_TITLE');
        $html   = [];
        $html[] = '<div class="modal fade" id="rm-modal" tabindex="-1" aria-hidden="true">';
        $html[] = '  <div class="modal-dialog">';
        $html[] = '    <div class="modal-content">';
        $html[] = '      <div class="modal-header">';
        $html[] = '        <h5 class="modal-title">' . htmlspecialchars($title, ENT_COMPAT, 'UTF-8') . '</h5>';
        $html[] = '        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
        $html[] = '      </div>';
        $html[] = '      <div class="modal-body">';
        $html[] = '        <div class="rm-wrapper">';
        $html[] = '          <button id="rm-run" class="btn btn-primary" type="button">' . htmlspecialchars($buttonText, ENT_COMPAT, 'UTF-8') . '</button>';
        $html[] = '          <div id="rm-progress" class="progress mt-2" style="height:24px; display:none; max-width:480px;">';
        $html[] = '            <div id="rm-bar" class="progress-bar" role="progressbar" style="width:0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>';
        $html[] = '          </div>';
        $html[] = '          <div id="rm-status" class="mt-2 text-muted"></div>';
        $html[] = '        </div>';
        $html[] = '      </div>';
        $html[] = '      <div class="modal-footer">';
        $html[] = '        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' . htmlspecialchars(Text::_('JCLOSE'), ENT_COMPAT, 'UTF-8') . '</button>';
        $html[] = '      </div>';
        $html[] = '    </div>';
        $html[] = '  </div>';
        $html[] = '</div>';

        return implode("\n", $html);
    }
} 