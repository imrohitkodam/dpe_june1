<?php
/**
 * @package    dpe
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2021. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of sla
 *
 * @since  __DEPLOY_VERSION__
 */
class JFormFieldRelatedrole extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	__DEPLOY_VERSION__
	 */
	protected $type = 'relatedrole';

	/**
	 * Fiedd to decide if options are being loaded externally and from xml
	 *
	 * @var		integer
	 * @since	__DEPLOY_VERSION__
	 */
	protected $loadExternally = 0;

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of HTMLHelper options.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getOptions()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$app   = Factory::getApplication();
		$user  = Factory::getUser();

		if (! class_exists('MultiagencyFrontendHelpers'))
		{
			// Require_once $path;
			JLoader::register('MultiagencyFrontendHelpers', JPATH_COMPONENT_SITE . '/helpers/multiagency.php');
			JLoader::load('MultiagencyFrontendHelpers');
		}

		$helperObject = new MultiagencyFrontendHelpers;

		$dpeParams    = ComponentHelper::getParams('com_dpe');
		$relatedRoles = new Registry($dpeParams->get('related_roles'));
		$userId       = $app->input->getInt('id', 0);

		if ($user->authorise('core.manageall', 'com_cluster')
			&& $app->input->get('view') == 'userform' && $userId)
		{
			$userAgencies = $helperObject->getRoleClient($userId, 'com_multiagency', null, 1);

			foreach ($userAgencies as $k => $v)
			{
				$sortedAgencies[] = $v['client_id'];
			}

			$recordNumber = preg_replace('/[^0-9]/', '', $this->name);

			foreach ($sortedAgencies as $key => $agency)
			{
				if ($recordNumber == $key)
				{
					$agencyId = $agency;
					break;
				}
			}
		}

		if ($agencyId)
		{
			// Get Licence Assigned role ids
			$licenceAssignedRoleIds = $helperObject->getLicenceAssignedRoleIds($agencyId);
		}

		// Select the required fields from the table.
		$query->select('tr.id, tr.name');
		$query->from($db->qn('#__tjsu_roles', 'tr'));
		$query->where($db->quoteName('tr.id') . 'IN (' . implode(',', $db->quote($relatedRoles->get('related_roles'))) . ')');
		$query->where($db->qn('tr.state') . '=' . 0);

		$db->setQuery($query);

		// Get all countries.
		$allRoles = $db->loadObjectList();

		$options = array();

		foreach ($allRoles as $role)
		{
			if (count((array)$licenceAssignedRoleIds))
			{
				if (in_array($role->id, $licenceAssignedRoleIds))
				{
					$options[] = HTMLHelper::_('select.option', $role->id, $role->name);
				}
			}
			else
			{
				$options[] = HTMLHelper::_('select.option', $role->id, $role->name);
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
	 * Method to get a list of Related roles
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
