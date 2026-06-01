<?php
/**
 * @package     Joomla.API.Plugin
 * @subpackage  com_tjlms-API
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('joomla.plugin.plugin');
jimport('joomla.user.helper');

/**
 * API Plugin
 *
 * @package     Joomla_API_Plugin
 * @subpackage  com_tjlms-API-EnrollUser
 * @since       1.0
 */
class LmsApiResourceEnroll extends ApiResource
{
	/**
	 * API Plugin for post method
	 *
	 * @return  avoid.
	 */
	public function post()
	{
		$input = Factory::getApplication()->input;
		$data = array();
		$result = new stdClass;
		$result->result = new stdClass;

		$lmsparams     = ComponentHelper::getParams('com_tjlms');
		$adminApproval = $lmsparams->get('admin_approval', '0', 'INT');

		$data['course_id']   = $input->get('course_id', 0, 'int');
		$data['user_id']     = $input->get('user_id', (int) $this->plugin->get('user')->id, 'int');
		$data['state']       = !empty($adminApproval) ? '0' : '1';
		$data['due_date']    = $input->get('due_date', '0', 'string');
		$data['enrolled_by'] = $input->get('enrolled_by_id', $this->plugin->get('user')->id, 'INT');

		if (empty($data['course_id']))
		{
			ApiError::raiseError("400", Text::_('PLG_API_TJLMS_REQUIRED_COURSE_DATA_EMPTY_MESSAGE'), 'APIValidationException');
		}

		$db = Factory::getDBO();
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$courseTableObj = Table::getInstance('Course', 'TjlmsTable', array('dbo', $db));
		$courseTableObj->load(array('id' => $data['course_id']));

		if (!$courseTableObj->id || $courseTableObj->state != 1 || $courseTableObj->type)
		{
			ApiError::raiseError("400", Text::_('PLG_API_TJLMS_COURSE_IS_UNPUBLISH'), 'APIValidationException');
		}

		JLoader::import('components.com_tjlms.models.enrolment', JPATH_SITE);
		$model  = BaseDatabaseModel::getInstance('enrolment', 'TjlmsModel');

		if ($model->userEnrollment($data))
		{
			$enrollTableObj = Table::getInstance('Enrolledusers', 'TjlmsTable', array('dbo', $db));
			$enrollTableObj->load(array('user_id' => $data['user_id'], 'course_id' => $data['course_id']));

			$data['enrollment_id'] = $enrollTableObj->id;
			$data['user_id']       = $enrollTableObj->user_id;
			$data['course_id']     = $enrollTableObj->course_id;
			$data['state']         = $enrollTableObj->state;
			$data['enrolled_by']   = $enrollTableObj->enrolled_by;

			$result->result = $data;
			$this->plugin->setResponse($result);

			return $result;
		}

		ApiError::raiseError("400", $model->getError());
	}
}
