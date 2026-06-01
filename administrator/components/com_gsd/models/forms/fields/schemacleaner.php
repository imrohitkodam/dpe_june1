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

use Joomla\CMS\Form\Field\SubformField;
use Joomla\CMS\Factory;

class JFormFieldSchemaCleaner extends SubformField
{
    /**
     *  Disable input's label
     *
     *  @return  null
     */
    protected function getLabel()
    {
        return;
    }

    /**
     * Method to get a list of options for a list input.
     *
     * @return      array           An array of JHtml options.
     */
    protected function getInput()
    {
        Factory::getDocument()->addStyleDeclaration('
            .schemacleaner {
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .schemacleaner tbody tr:first-child td {
                padding-top:18px;
            }
            .schemacleaner thead tr > * {
                border-bottom:solid 1px #ddd;
                background-color: #fafafa;
                font-size: .9rem;
                font-weight: 500;
                padding-top:12px;
                padding-bottom:12px;
            }
            .schemacleaner thead td .btn-group { 
                display:none;
            }
            .schemacleaner th, .schemacleaner td {
                vertical-align:middle;
                border:none;
                padding:8px 14px;
            }
            .schemacleaner .controls {
                padding:0;
                min-width:auto;
            }
            .schemacleaner .control-group {
                margin:0;
            }
            .schemacleaner thead tr > th:first-child {
                width: 70px !important;
            }
            .schemacleaner thead tr > th:nth-child(2) {
                width: 100% !important;
            }
            .schemacleaner input {
                width: 100% !important;
            }
            .schemacleaner .nrtoggle {
                top: 3px;
                left: 6px;
            }
        ');

        $html = '<div class="schemacleaner">' . parent::getInput() . '</div>';

        return $html;
    }
}