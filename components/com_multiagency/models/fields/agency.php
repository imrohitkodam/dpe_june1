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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

jimport('joomla.form.helper');
\JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of allocated agencies
 *
 * @since  __DEPLOY_VERSION__
 */
class JFormFieldAgency extends \JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	protected $type = 'agency';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return array An array of HTMLHelper options.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getOptions()
	{
		$app                  = Factory::getApplication();
		$user                 = Factory::getUser();
		$userId               = $app->input->get('id', '', 'INT');
		$params               = ComponentHelper::getParams('com_multiagency');
		$memberRole           = $params->get('member_role_id', '0', 'INT');
		$leadConsultantRoleId = $params->get('organization_lead_consultant_role_id', '0', 'INT');

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$MultiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel', array('ignore_request' => true));

		if ($userId && $user->id == $userId )
		{
			$agencies = $MultiagencyModel->getAllocatedAgencies($user->id);
		}
		elseif ($app->input->get('view') == 'submit' || $app->input->get('view') == 'rsticketspro')
		{
			$agencies = $MultiagencyModel->getAllocatedAgencies($user->id);
		}
		else
		{
			$manageAll = RBACL::check($user->id, 'com_multiagency', 'core.manage.all', 'com_multiagency');

			// DPE Hack to check user have permission or user is DPE admin
			if ($manageAll || $user->authorise('core.manageall', 'com_cluster'))
			{
				$agencyOptions = array("schools_without_license" => true);
			}

			$agencies = $MultiagencyModel->getAllocatedAgencies($user->id, array($memberRole, $leadConsultantRoleId), $agencyOptions);
		}

		// Initialize array to store dropdown options
		$options   = array();

		if ($app->input->get('view') == 'items' || ($app->input->get('view') == 'rsticketspro' && count($agencies) > 1))
		{
			// Initialize array option
			$options[] = HTMLHelper::_('select.option', 'all', Text::_('COM_MULTIAGENCY_SELECT_ALL_AGENCY')
			);

			if ($user->authorise('core.admin'))
			{
				$options[] = HTMLHelper::_(
				'select.option', 'none', Text::_('COM_MULTIAGENCY_SELECT_NONE')
				);
			}
		}
		elseif ($app->input->get('view') == 'users' && $app->input->get('layout') != 'import')
		{
			// This is used for stafflist and check user having manage all permission
			if (count($agencies) > 1 && $user->authorise('core.manageall', 'com_cluster'))
			{
				$options[] = HTMLHelper::_('select.option', 'all', Text::_('COM_MULTIAGENCY_SELECT_ALL_AGENCY')
				);

				$options[] = HTMLHelper::_(
					'select.option', 'none', Text::_('COM_MULTIAGENCY_SELECT_NONE')
				);
			}
		}
		else
		{
			$options[] = HTMLHelper::_('select.option', "", Text::sprintf('COM_MULTIAGENCY_SELECT_AGENCY_ID', Text::_('COM_MULTIAGENCY_ORGANISATION')));
		}

		foreach ($agencies as $agency)
		{
			$options[] = HTMLHelper::_('select.option', $agency->id, $agency->title);
		}

		if (!$this->loadExternally)
		{
			// Merge any additional options in the XML definition.
			$options = array_merge(parent::getOptions(), $options);
		}

		return $options;
	}

	/**
	 * Method to get a list of Agency options for a list input externally and not from xml.
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
