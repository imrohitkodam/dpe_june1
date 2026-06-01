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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('behavior.core');
HTMLHelper::_('bootstrap.tooltip', '.hasTooltip', ['html' => true, 'sanitize' => false]);
HTMLHelper::_('behavior.multiselect');

$this->loadDraggableLib();
$this->loadSearchTools();

$document = Factory::getApplication()->getDocument();

$document->getWebAssetManager()
	->useScript('table.columns')
	->useScript('multiselect');

$document->addScript(Uri::root(true) . '/media/com_eventbooking/js/admin-plugins-default.min.js');

Text::script('EB_CHOOSE_PLUGIN', true);