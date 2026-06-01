<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Controller\FormController;

/**
 * Course controller class.
 *
 * @since  _DEPLOY_VERSION_
 */
class TjlmsControllerCourse extends FormController
{
	/**
	 * Function to expire the certificates & archive the corresponding lesson attempts.
	 *
	 * @return  void
	 *
	 * @since   1.4.0
	 */
	public function expireCertificate()
	{
		$app      = Factory::getApplication();
		$input    = $app->input;
		$courseId = $input->get('course_id', '0', 'INT');
		$userId   = $input->get('user_id', '0', 'INT');
		$course   = TJLMS::course($courseId);

		echo new JsonResponse($course->expireCertificate($userId));

		jexit();
	}
}
