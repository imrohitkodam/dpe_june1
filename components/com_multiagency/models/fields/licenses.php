<?php
/**
 * @package     Multiagency
 * @subpackage  Subusers
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormHelper;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of allocated agencies
 *
 * @since  __DEPLOY_VERSION__
 */
class JFormFieldLicenses extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since    1.0.0
	 */
	protected $type = 'licenses';

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getOptions()
	{
		$app = Factory::getApplication();
		$input = $app->input;

		$user = Factory::getUser();

		$licenseId = $input->getInt('license_id', 0);

		$options[] = HTMLHelper::_('select.option', "",
Text::sprintf('COM_SLA_SLA_ACTIVITY_SELECT_SCHOOL_LICENSE', Text::_('COM_MULTIAGENCY_ORGANISATION'))
);

		JLoader::register('MultiagencyFrontendHelpers', JPATH_SITE . '/components/com_multiagency/helpers/multiagency.php');
		$multiAgencyHelper = new MultiagencyFrontendHelpers;

		// Check permission to manage activities
		$canManageAllActivity = $user->authorise('core.manageall', 'com_cluster');

		if ($canManageAllActivity)
		{
			$licenceList = $multiAgencyHelper->getLicensesByDpeConsultant();
		}
		elseif (!$user->authorise('core.manageall', 'com_cluster'))
		{
			// For DPE Lead Consultant and School Admin
			$licenceList = $multiAgencyHelper->getLicensesByDpeConsultant($user->id);
		}

		if (!empty($licenceList))
		{
			$manageLessons = true;

			foreach ($licenceList as $license)
			{
				$startDate = HTMLHelper::date($license->start_date, 'm-y');
				$endDate   = HTMLHelper::date($license->end_date, 'm-y');

				// If user is staff of school then do not show the license in dropdown except
				if (!$user->authorise('core.admin') && !$user->authorise('core.manageall', 'com_cluster'))
				{
					$manageLessons = RBACL::check($user->id, 'com_multiagency', 'core.manage.lessons', 'com_tjlms', $license->client_id);
				}

				if ($manageLessons)
				{
					$options[] = HTMLHelper::_('select.option', $license->id, $license->title . ' (' . $startDate . ' to ' . $endDate . ')');
				}
			}
		}

		return $options;
	}
}
