<?php
/**
 * @package     Shika
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

JLoader::import('components.com_tjlms.libraries.suggestcourses', JPATH_SITE);

/**
 * Suggest courses api.
 *
 * @package     Joomla.Administrator
 * @subpackage  com_api
 *
 * @since       1.3.22
 */
class LmsApiResourceSuggestCourses extends ApiResource
{
	/**
	 * API Plugin for get method
	 *
	 * @return  avoid.
	 */
	public function post()
	{
		$app   = Factory::getApplication();
		$input = $app->input;
		$user  = Factory::getUser();

		$limit      = $input->getInt('limit', 0);
		$limitstart = $input->getInt('limitstart', 0);

		$options               = array ();
		$options['limit']      = $limit;
		$options['limitstart'] = $limitstart;

		$result = new stdClass;
		$result->err_code = '';
		$result->err_message = '';
		$result->data = new stdClass;
		$this->items = '';

		$questions = $input->json->get('questions', array(), 'ARRAY');

		if (!empty($questions))
		{
			$this->items = TjSuggestCourses::suggestCourses($questions, $options);
		}

		if (empty($this->items) || !$user->id)
		{
			$result->err_code		= '400';
			$result->err_message	= Text::_('PLG_API_TJLMS_REQUIRED_DATA_EMPTY_MESSAGE');
			$this->plugin->setResponse($result);

			return;
		}

		$this->getLessons();

		$result->data->result = $this->items;
		unset($this->items);
		$this->plugin->setResponse($result);

		return;
	}

	/**
	 * Method to get course lessons.
	 *
	 * @return  null
	 *
	 * @since   1.0.0
	 */
	private function getLessons()
	{
		JLoader::import('components.com_tjlms.models.course', JPATH_SITE);
		$courseModel = BaseDatabaseModel::getInstance('course', 'TjlmsModel', array('ignore_request' => true));

		foreach ($this->items as $value)
		{
			$lessons = $courseModel->getCourseTocdetails($value->id);
			$value->lessons = $lessons['toc'][$value->id]->lessons;
		}
	}
}
