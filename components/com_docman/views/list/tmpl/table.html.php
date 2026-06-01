<?
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2012 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
defined('KOOWA') or die; ?>

<? if ($params->get('combined_layout')): ?>
    <?= import('combined_table.html') ?>
<? else: ?>
    <?= import('default_table.html') ?>
<? endif ?>