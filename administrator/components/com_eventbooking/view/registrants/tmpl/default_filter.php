<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Multilanguage;

if ($this->config->get('allow_filter_registrants_by_type'))
{
	echo $this->lists['filter_registrants_type'];
}

if ($this->config->get('allow_filter_registrants_by_category', 1))
{
	echo EventbookingHelperHtml::getChoicesJsSelect($this->lists['filter_category_id']);
}

if ($this->config->enable_select_event_from_modal)
{
	echo EventbookingHelperHtml::getEventSelectionInput($this->state->filter_event_id, 'filter_event_id', 'submit();');
}
else
{
	echo EventbookingHelperHtml::getChoicesJsSelect($this->lists['filter_event_id']);
}

if (isset($this->lists['filter_ticket_type_id']))
{
	echo $this->lists['filter_ticket_type_id'];
}

foreach ($this->filters as $filter)
{
	$this->hasFilterFields = true;
	echo EventbookingHelperHtml::getChoicesJsSelect($filter);
}

echo $this->lists['filter_published'];

if (Multilanguage::isEnabled())
{
	echo $this->lists['filter_language'];
}

if ($this->config->activate_checkin_registrants)
{
	echo $this->lists['filter_checked_in'];
}

echo $this->pagination->getLimitBox();