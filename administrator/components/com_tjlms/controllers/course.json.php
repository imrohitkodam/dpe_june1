<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;

/**
 * Tools controller class.
 *
 * @since  _DEPLOY_VERSION_
 */
class TjlmsControllerCourse extends FormController
{
	/**
	 * Method to get total enrolled users count.
	 *
	 * @return  INT
	 *
	 * @since  1.3.25
	 */
	public function enrolledUsersCount()
	{

		// Get the input
		$input = Factory::getApplication()->input;
		$courseId   = $input->post->get('cid',  '', 'INTEGER');

		// Get total enrolled users count

		if (!empty($courseId))
		{
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models', 'TjlmsModel');
			$model = BaseDatabaseModel::getInstance('Manageenrollments', 'TjlmsModel', array('ignore_request' => true));
			$model->setState('filter.coursefilter', $courseId);

		    //DPE HACK - Can go in Shika core MR link : https://git.tekdi.net/tekdi/dpe/-/merge_requests/3064
			$enrolledUserIds = $model->getEnrolledUserByCourseId($courseId);
			$userDetails=[];

			foreach ($enrolledUserIds as $key => $enrolledUserId) 
			{

				$userDetails[] = array('name'=>Factory::getUser($enrolledUserId['user_id'])->name, 'value'=>$enrolledUserId['user_id']);
			}

			echo new JsonResponse(array('total'=>count((array)$userDetails), 'enrolledUserDetails'=>$userDetails));
			//<!--DPE HACK END-->
			
			jexit();
		}
	}
}
