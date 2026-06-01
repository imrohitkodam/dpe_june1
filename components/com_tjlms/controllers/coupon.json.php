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
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;


/**
 * Coupon controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerCoupon extends FormController
{
	/**
	 * Function to load course subscription
	 * Ajax Call from backend coupon form view
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 */
	public function loadSubscription()
	{
		$app = Factory::getApplication();
		$input = $app->input;
		$courseIds = $input->get('course_id');

		JLoader::import('components.com_tjlms.helpers.courses', JPATH_SITE);
		$tjlmsCoursesHelper = new TjlmsCoursesHelper;

		$courseArray = array();

		foreach ($courseIds as $key => $courseId)
		{
			$subscriptions = $tjlmsCoursesHelper->getCourseSubplans($courseId);
			$courseName    = $tjlmsCoursesHelper->courseName($courseId);

			$courseArray[$key]['id'] = $courseId;
			$courseArray[$key]['title'] = $courseName;

			$subscriptionList = array();
			$subscriptionArray = array();

			foreach ($subscriptions as $key1 => $subscription)
			{
				$subscriptionList['id'] = $subscription->id;
				$subscriptionList['name'] = $subscription->duration . " " . $subscription->time_measure;

				$subscriptionArray[] = $subscriptionList;
			}

			$courseArray[$key]['subscription'] = $subscriptionArray;
		}

		echo new JsonResponse($courseArray);
		jexit();
	}
}
