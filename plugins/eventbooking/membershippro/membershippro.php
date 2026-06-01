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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

class plgEventbookingMembershippro extends CMSPlugin implements SubscriberInterface
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
		JLoader::register('OSMembershipHelper', JPATH_ROOT . '/components/com_osmembership/helper/helper.php');

		$fields = OSMembershipHelper::getProfileFields(0);

		$options = [];

		foreach ($fields as $field)
		{
			$options[] = HTMLHelper::_('select.option', $field->name, $field->title);
		}

		$options[] = HTMLHelper::_('select.option', 'membership_id', Text::_('Membership ID'));

		$this->addResult($eventObj, $options);
	}

	/**
	 * Method to get data stored in Membership Pro profile of the given user
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

		$synchronizer = new RADSynchronizerMembershippro();

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
		if (!ComponentHelper::isEnabled('com_osmembership'))
		{
			return;
		}

		parent::registerListeners();
	}
}
