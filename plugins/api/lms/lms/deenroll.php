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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('joomla.plugin.plugin');
jimport('joomla.user.helper');

/**
 * API Plugin
 *
 * @package     Joomla_API_Plugin
 * @subpackage  com_tjlms-API-Deenroll User
 * @since       1.0
 */
class LmsApiResourceDeenroll extends ApiResource
{
	/**
	 * This method used to delete the user enrollment
	 *
	 * @return  void.
	 */
	public function post()
	{
		$input = Factory::getApplication()->input;
		$result = new stdClass;
		$result->result = new stdClass;
		$data = array();
		$data['user_id'] = $input->get('user_id', (int) $this->plugin->get('user')->id, 'INT');
		$data['course_id'] = $input->get('course_id', '', 'INT');

		if (empty($data['course_id']))
		{
			ApiError::raiseError("400", Text::_('PLG_API_TJLMS_REQUIRED_COURSE_DATA_EMPTY_MESSAGE'), 'APIValidationException');
		}

		$db = Factory::getDBO();
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$courseTableObj = Table::getInstance('Course', 'TjlmsTable', array('dbo', $db));
		$courseTableObj->load(array('id' => $data['course_id']));

		if (!$courseTableObj->id)
		{
			ApiError::raiseError("400", Text::_('PLG_API_TJLMS_INVALID_COURSE'), 'APIValidationException');
		}

		if (isset($courseTableObj->type) && $courseTableObj->type == 1)
		{
			ApiError::raiseError("400", Text::_('PLG_API_TJLMS_PAID_COURSE_NOT_ALLOW_TO_DEENROLL'), 'APIValidationException');
		}

		JLoader::import('components.com_tjlms.models.enrolment', JPATH_SITE);
		$model  = BaseDatabaseModel::getInstance('enrolment', 'TjlmsModel');
		$deEnroll = $model->ItemState($data['user_id'], $data['course_id'], 0);

		if ($deEnroll->id)
		{
			$data['enrollment_id'] = $deEnroll->id;
			$data['user_id'] = $deEnroll->user_id;
			$data['course_id'] = $deEnroll->course_id;
			$data['state'] = $deEnroll->state;

			$result->result = $data;
			$this->plugin->setResponse($result);

			return $result;
		}

		ApiError::raiseError("400", $model->getError());
	}
}
