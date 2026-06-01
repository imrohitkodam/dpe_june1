<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

class plgEventBookingJcomments extends CMSPlugin implements SubscriberInterface
{
	use RADEventResult;

	/**
	 * Application object.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 */
	protected $app;

	/**
	 * @return array
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onEventDisplay' => 'onEventDisplay',
		];
	}

	/**
	 * Display comment form on event details page
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onEventDisplay(Event $eventObj)
	{
		/* @var EventbookingTableEvent $row */
		[$row] = array_values($eventObj->getArguments());

		ob_start();
		$this->displayCommentForm($row);

		$result = [
			'title'    => Text::_('EB_COMMENT'),
			'form'     => ob_get_clean(),
			'position' => $this->params->get('output_position', 'after_register_buttons'),
		];

		$this->addResult($eventObj, $result);
	}

	/**
	 * Display form allows users to add comments about the event via JComments
	 *
	 * @param   object  $row
	 */
	private function displayCommentForm($row)
	{
		require_once JPATH_ROOT . '/components/com_jcomments/jcomments.php';

		echo '<div style="clear:both; padding-top: 10px;"></div>';

		echo JComments::show($row->id, 'com_eventbooking', $row->title);
	}

	/**
	 * Override registerListeners method to only register listeners if needed
	 *
	 * @return void
	 */
	public function registerListeners()
	{
		if (!ComponentHelper::isEnabled('com_jcomments'))
		{
			return;
		}

		parent::registerListeners();
	}
}
