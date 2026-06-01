<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;

jimport('joomla.application.component.controller');

/**
 * Tjmodules list controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerCourses extends TjlmsController
{
	/**
	 * function to get course filters result
	 *
	 * @return redirect
	 *
	 * @since  1.0.0
	 */
	public function setModelVariables()
	{
		$comtjlmsHelper = new comtjlmsHelper;
		$app = Factory::getApplication('site');
		$input = Factory::getApplication()->input;

		// List state information
		$value = $app->input->get('limit', $app->get('list_limit', 0), 'uint');
		$app->setUserState('list.limit', $value);

		$value = $app->input->get('limitstart', 0, 'uint');
		$app->setUserState('list.start', $value);

		// Filter search
		$filterSearch = $input->get('filter_search', '', 'STRING');
		$app->setUserState('com_tjlms' . '.filter.filter_search', $filterSearch);

		// Filter mod category.
		$courseCat = $input->get('course_cat', 0, 'INTEGER');
		$app->setUserState('com_tjlms' . '.filter.category_filter', $courseCat);

		// Filter course type
		$courseType = $input->get('course_type', -1, 'INTEGER');
		$app->setUserState('com_tjlms' . '.filter.course_type', $courseType);

		// Filter course author
		$courseCreator = $input->get('creator_filter', 0, 'INTEGER');
		$app->setUserState('com_tjlms' . '.filter.creator_filter', $courseCreator);

		$courseStatus = $input->get('course_status', '', 'STRING');
		$app->setUserState('com_tjlms' . '.filter.course_status', $courseStatus);

		$link = $comtjlmsHelper->tjlmsroute('index.php?option=com_tjlms&view=courses', false);
		$app->redirect($link);
	}
}
