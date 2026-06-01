<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_ActivityStream
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2016 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;
jimport('joomla.plugin.plugin');

/**
 * Class for get ActivityStream
 *
 * @package     Com_ActivityStream
 * @subpackage  component
 * @since       0.0.1
 */
class TjactivityApiResourceActivities extends ApiResource
{
	/**
	 * Get ActivityStream Data
	 *
	 * @return  void
	 *
	 * @since   0.0.1
	 */
	public function get()
	{
		// Variable to store activity data fetched
		$result = array();

		// Variable to store request response
		$result_arr = array();

		$ActivityStreamModelActivities = JModelLegacy::getInstance('Activities', 'ActivityStreamModel');

		$jinput = JFactory::getApplication()->input;
		$type = $jinput->get("type", '', 'STRING');

		// Return result related to specified activity type
		if (empty($type))
		{
			$result_arr['success'] = false;
			$result_arr['message'] = JText::_("COM_ACTIVITYSTREAM_ERROR_ACTIVITY_TYPE");

			$this->plugin->setResponse($result_arr);

			return false;
		}

		$actor_id = $jinput->get('actor_id', '', 'CMD');
		$object_id = $jinput->get('object_id', '', 'CMD');
		$target_id = $jinput->get('target_id', '', 'CMD');
		$from_date = $jinput->get('from_date', '');

		// Set model state
		$ActivityStreamModelActivities->setState("type", $type);
		$ActivityStreamModelActivities->setState("actor_id", $actor_id);
		$ActivityStreamModelActivities->setState("object_id", $object_id);
		$ActivityStreamModelActivities->setState("target_id", $target_id);
		$ActivityStreamModelActivities->setState("from_date", $from_date);
		$ActivityStreamModelActivities->setState("access", "1");
		$ActivityStreamModelActivities->setState("state", '1');

		$result['results'] = $ActivityStreamModelActivities->getItems();

		// If no activities found then return the error message
		if (empty($result['results']))
		{
			$result_arr['success'] = false;
			$result_arr['message'] = JText::_("COM_ACTIVITYSTREAM_NO_ACTIVITY");
		}
		else
		{
			$result_arr['success'] = true;
			$result_arr['data'] = $result;
			$result_arr['data']['total'] = $ActivityStreamModelActivities->getTotal();
		}

		$this->plugin->setResponse($result_arr);
	}

	/**
	 * Get ActivityStream Data
	 *
	 * @return  json ActivityStream details
	 *
	 * @since   0.0.1
	 */
	public function post()
	{
		$result_arr = array();
		$jinput = JFactory::getApplication()->input;
		$post = $jinput->post->getArray();

		$ActivityStreamModelActivity = JModelLegacy::getInstance('Activity', 'ActivityStreamModel');

		$result = $ActivityStreamModelActivity->save($post);

		if ($result)
		{
			$result_arr['success'] = true;
			$result_arr['message'] = JText::_("COM_ACTIVITYSTREAM_ACTIVITY_ADDED");
		}
		else
		{
			$error = $ActivityStreamModelActivity->getError();

			$result_arr['success'] = false;
			$result_arr['message'] = $error;
		}

		$this->plugin->setResponse($result_arr);
	}

	/**
	 * Delete ActivityStream Data
	 *
	 * @return  boolean
	 *
	 * @since   0.0.1
	 */
	public function delete()
	{
		$jinput = JFactory::getApplication()->input;
		$result_arr = array();
		$id = $jinput->get('id', '', "CMD");

		$ActivityStreamModelActivity = JModelLegacy::getInstance('Activity', 'ActivityStreamModel');

		$result = $ActivityStreamModelActivity->delete($id);

		if ($result)
		{
			$result_arr['success'] = true;
			$result_arr['message'] = JText::_("COM_ACTIVITYSTREAM_ACTIVITY_DELETED");
		}
		else
		{
			$result_arr['success'] = false;
			$result_arr['message'] = JText::_("COM_ACTIVITYSTREAM_ACTIVITY_NOT_DELETED");
		}

		$this->plugin->setResponse($result_arr);
	}
}
