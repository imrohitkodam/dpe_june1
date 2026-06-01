<?php
/**
 * @package    CourseInfo
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2020 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die();
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;

/**
 * Plug-in
 *
 * @since  1.3.34
 */
class PlgContentCourseInfo extends CMSPlugin
{
	/**
	 * Plugin that retrieves Course information
	 *
	 * @param   integer  $courseId  course id
	 * @param   string   $client    client
	 *
	 * @return  string
	 */
	public function onContentPrepareTjHtml($courseId, $client)
	{
		if ($client === 'com_tjlms.course')
		{
			JLoader::import('components.com_tjlms.includes.tjlms', JPATH_ADMINISTRATOR);

			$courseObj = TjLms::course($courseId);

			if (!$courseObj->state)
			{
				return '';
			}

			$app = Factory::getApplication();
			$comtjlmsHelper     = new comtjlmsHelper;
			JLoader::import('components.com_tjlms.models.course', JPATH_SITE);
			$courseModel = BaseDatabaseModel::getInstance('Course', 'TjlmsModel', array('ignore_request' => true));
			$courseData = $courseModel->getItem($courseId);
			$data = (array) $courseData;
			$template = $app->getTemplate(true)->template;
			$pluginLayout = $this->params->get('layout');
			$basePath = JPATH_SITE . '/templates/' . $template . '/html/plg_content_courseinfo/layouts';

			if ($pluginLayout === "shika")
			{
				// If override available for Shika layout then use override files
				$basePath = JPATH_SITE . '/components/com_tjlms/layouts';
				$override = JPATH_SITE . '/templates/' . $template . '/html/layouts/com_tjlms/';

				if (File::exists($override . 'courselist.php'))
				{
					$basePath = $override;
				}
			}

			$layout = new FileLayout('courselist', $basePath);

			$itemLikeDislike = $comtjlmsHelper->getItemJlikes($courseId, $client);

			$comparams = ComponentHelper::getParams('com_tjlms');
			$currency  = $comparams->get('currency', '', 'STRING');

			$tjlmsCoursesHelper = new TjlmsCoursesHelper;
			$comtjlmsHelper     = new comtjlmsHelper;

			// To get course price
			$data['price'] = $data['formattedPrice'] = Text::_("COM_TJLMS_COURSE_FREE");

			if ($courseData->type != 0)
			{
				$priceRange = $courseModel->coursePriceRange($courseId);

				$data['price'] = $priceRange->lowestPrice;
				$data['formattedPrice'] = $comtjlmsHelper->getFromattedPrice($data['price'], $currency);

				$formattedHighestPrice = 0;

				if (isset($priceRange->highestPrice))
				{
					$formattedHighestPrice = $comtjlmsHelper->getFromattedPrice($priceRange->highestPrice, $currency);
				}

				$data['displayPrice'] = $courseModel->displayCoursePrice($data['formattedPrice'], $formattedHighestPrice);
			}

			$data['likesforCourse'] = isset($itemLikeDislike['likes']) ? (int) $itemLikeDislike['likes'] : 0;
			$data['enrolled_users_cnt'] = count($comtjlmsHelper->getCourseEnrolledUsers($courseId));
			$data['url'] = $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $courseId, false);
			$data['cat'] = $tjlmsCoursesHelper->getCourseCat($courseData, 'title');

			return $layout->render($data);
		}
	}

	/**
	 * Plugin trigger that provides certificate provider data
	 *
	 * @param   integer  $clientId  clientId
	 * @param   string   $client    client
	 *
	 * @return  object|false
	 */
	public function onGetCertificateClientData($clientId, $client)
	{
		if ($client === 'com_tjlms.course')
		{
			JLoader::import('components.com_tjlms.includes.tjlms', JPATH_ADMINISTRATOR);

			return TjLms::course($clientId);
		}
	}
}
