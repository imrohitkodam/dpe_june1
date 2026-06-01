<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormHelper;

FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of Activity type
 *
 * @since  __DEPLOY_VERSION__
 */
class JFormFieldActivitytype extends \JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	protected $type = 'activitytype';

	/**
	 * The form field load externally.
	 *
	 * @var   int
	 * @since 1.0.0
	 */
	protected $loadExternally = 0;

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return array An array of HTMLHelper options.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getOptions()
	{
		$user = Factory::getUser();
		$activityTypes = array();

		if ($user->id)
		{
			$timelogModel = TimelogFactory::model('Activitytypes', array('ignore_request' => true));

			$activityTypes = $timelogModel->getItems();
		}

		// Initialize array to store dropdown options
		$options   = array();

		if (count($activityTypes) > 1)
		{
			$options[] = HTMLHelper::_('select.option', "", Text::_('COM_TIMELOG_ACTIVITIES_ACTIVITY_TYPE_ID_OPTION'));

			foreach ($activityTypes as $activity)
			{
				$options[] = HTMLHelper::_('select.option', $activity->id, $activity->title);
			}
		}

		if (!$this->loadExternally)
		{
			// Merge any additional options in the XML definition.
			$options = array_merge(parent::getOptions(), $options);
		}

		return $options;
	}

	/**
	 * Method to get a list of Activity type options for a list input externally and not from xml.
	 *
	 * @return array  An array of HTMLHelper options.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getOptionsExternally()
	{
		$this->loadExternally = 1;

		return $this->getOptions();
	}
}
