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

$this->loadSearchTools();

Text::script('EB_CHOOSE_THEME', true);

$document = Factory::getApplication()->getDocument();
$document->getWebAssetManager()
	->useScript('table.columns')
	->useScript('multiselect');

$document->addScript(Uri::root(true) . '/media/com_eventbooking/js/admin-themes-default.min.js');