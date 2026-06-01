<?php
/**
 * @package        Joomla
 * @subpackage     Events Booking
 * @author         Tuan Pham Ngoc
 * @copyright      Copyright (C) 2012 - 2015 Ossolution Team
 * @license        GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

class plgEventbookingContactenhanced extends CMSPlugin implements SubscriberInterface
{
	use RADEventResult;

	/**
	 * Application object.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 */
	protected $app;

	/**
	 * Database object.
	 *
	 * @var    \Joomla\Database\DatabaseDriver
	 */
	protected $db;

	/**
	 * @return array
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onGetFields'      => 'onGetFields',
			'onGetProfileData' => 'onGetProfileData',
		];
	}

	/**
	 * Get list of profile fields used for mapping with fields in Events Booking
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onGetFields(Event $eventObj): void
	{
		$db      = $this->db;
		$options = [];
		$fields  = array_keys($db->getTableColumns('#__ce_details'));

		// Remove some system fields
		$fields = array_diff($fields, ['id', 'alias', 'ordering', 'checked_out', 'checked_out_time', 'user_id', 'catid', 'hits', 'params']);

		foreach ($fields as $field)
		{
			$options[] = HTMLHelper::_('select.option', $field, $field);
		}

		$this->addResult($eventObj, $options);
	}

	/**
	 * Method to get data stored in Contact Enhanced data of the given user
	 *
	 * @param   Event  $eventObj
	 *
	 * @return void
	 */
	public function onGetProfileData(Event $eventObj): void
	{
		/**
		 * @var int   $userId
		 * @var array $mappings
		 */
		[$userId, $mappings] = array_values($eventObj->getArguments());

		$synchronizer = new RADSynchronizerContactenhanced();

		$result = $synchronizer->getData($userId, $mappings);

		$this->addResult($eventObj, $result);
	}

	/**
	 * Override registerListeners method to only register listeners if needed
	 *
	 * @return void
	 */
	public function registerListeners()
	{
		if (!ComponentHelper::isEnabled('com_contactenhanced'))
		{
			return;
		}

		parent::registerListeners();
	}
}
