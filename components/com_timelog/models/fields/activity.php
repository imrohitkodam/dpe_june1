<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of Activity type
 *
 * @since  __DEPLOY_VERSION__
 */
class JFormFieldActivity extends \JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	protected $type = 'activity';

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
		$app  = Factory::getApplication();

		// Initialize array to store dropdown options
		$options   = $activities = array();

		JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);

		/* This code "array('ignore_request' => true)" is removed because don't want to ignore
		filters of activities list view */

		$slaModel = SlaFactory::model('SlaActivities', array('ignore_request' => true));

		$licenseId = $app->input->getInt('licence_id', 0);
		$state     = $app->input->getInt('state', 0);

		$slaModel->setState('filter.license_id', $licenseId);
		$slaModel->setState('filter.state', $state);

		if ($user->id)
		{
			$activities = $slaModel->getItems();
		}

		if (count($activities) > 0)
		{
			$options[] = HTMLHelper::_('select.option', "", Text::_('COM_TIMELOG_FORM_LBL_ACTIVITY_OPTION'));

			foreach ($activities as $activity)
			{
				$options[] = HTMLHelper::_('select.option', $activity->id, $activity->sla_service_title);
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
