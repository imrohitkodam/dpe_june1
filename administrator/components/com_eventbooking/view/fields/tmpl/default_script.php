<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip', ['html' => true, 'sanitize' => false]);

Factory::getApplication()->getDocument()->getWebAssetManager()
	->useScript('table.columns')
	->useScript('multiselect');

$this->loadDraggableLib();
$this->loadSearchTools();