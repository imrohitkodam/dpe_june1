<?php
/**
 * @package     UCM
 * @subpackage  module-ucm
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Helper\ModuleHelper;

// Include the news functions only once
JLoader::register('ModUcmHelper', __DIR__ . '/helper.php');

$itemsData     = ModUcmHelper::getList($params);

$columnsToShow = ModUcmHelper::getColumnsData($params, $itemsData['fieldsColumn']);

require ModuleHelper::getLayoutPath('mod_ucm_data', $params->get('layout', 'default'));
