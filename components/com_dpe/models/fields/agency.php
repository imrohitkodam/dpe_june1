<?php
/**
 * @package    Subusers
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

FormHelper::loadFieldClass('list');

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
	 * @var        string
	 * @since    1.0.0
	 */
	protected $type = 'agency';

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
		$user = Factory::getUser();
		$params = $app->getParams('com_multiagency');

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$MultiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel', array('ignore_request' => true));

		$params = ComponentHelper::getParams('com_multiagency');
		$memberRole = $params->get('member_role_id', '0', 'INT');
		$userId = $app->input->get('id', '', 'INT');

		$agencies = $MultiagencyModel->getAllocatedAgencies($user->id);

		// Initialize array to store dropdown options
		$options = array();

		$options[] = HTMLHelper::_('select.option', "", Text::sprintf('COM_MULTIAGENCY_SELECT_AGENCY_ID', Text::_('COM_MULTIAGENCY_ORGANISATION')));

		foreach ($agencies as $agency)
		{
			$options[] = JHTML::_('select.option', trim($agency->id), $agency->title);
		}

		return $options;
	}
}
