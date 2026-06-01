<?php
/**
 * @package Helix3 Framework
 * @author JoomShaper https://www.joomshaper.com
 * @copyright (c) 2010 - 2021 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
*/

defined('JPATH_BASE') or die;

$selector = empty($displayData['selector']) ? '' : $displayData['selector'];
$id = empty($displayData['id']) ? '' : $displayData['id'];
$active = empty($displayData['active']) ? '' : $displayData['active'];
$title = empty($displayData['title']) ? '' : $displayData['title'];

$li = '<li class="' . $active . '"><a href="#' . $id . '" data-toggle="tab">' . $title . '</a></li>';

if ($id == 'legal-summary')
{
    $html = '<ul class="nav" id="tjucm_myTabTabs1">
            <li><a href="#legal-Lawfulbasis" class="nav-link">Lawful Basis</a></li>
            <li><a href="#legal-data-subjects" class="nav-link">Data Subjects</a></li>
            <li><a href="#legal-accuracy" class="nav-link">Accuracy</a></li>
            <li><a href="#legal-destination" class="nav-link">Destination</a></li>
            <li><a href="#legal-retention" class="nav-link">Retention</a></li>
            <li><a href="#legal-impact-assessment" class="nav-link">Impact Assessment</a></li>
            <li><a href="#legal-status-attachment" class="nav-link">Status and Attachment</a></li>
            </ul>';
    //$html ="";
    $li = '<li class="' . $active . '" id="rop-'.$id.'"><a href="#' . $id . '" data-toggle="tab">' . $title . '</a>'.$html.'</li>';
}
else
{
    $li = '<li class="' . $active . '" id="rop-'.$id.'" ><a href="#' . $id . '" data-toggle="tab">' . $title . '</a></li>';
}

echo 'jQuery(function($){ $(', json_encode('#' . $selector . 'Tabs'), ').append($(', json_encode($li), ')); });';
