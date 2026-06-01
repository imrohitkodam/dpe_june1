<?php
/**
 * @package     Shika.Plugin
 * @subpackage  tjlmsdashboard,Enrolledcourses
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filesystem\File;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('techjoomla.common');

$lang = Factory::getLanguage();
$lang->load('plg_tjlmsdashboard_enrolledcourses', JPATH_ADMINISTRATOR);

/**
 * Vimeo plugin from techjoomla
 *
 * @since  1.0.0
 */

class PlgTjlmsdashboardEnrolledcourses extends CMSPlugin
{
	/**
	 * Function to render the whole block
	 *
	 * @param   ARRAY  $plg_data  data to be used to create whole block
	 * @param   ARRAY  $layout    Layout to be used
	 *
	 * @return  complete html.
	 *
	 * @since 1.0.0
	 */
	public function onenrolledcoursesRenderPluginHTML($plg_data, $layout = 'default')
	{
		$userCourseData = $this->getData($plg_data);
		$userCourseinfo = array();
		$totalRows      = 0;

		if (isset($userCourseData['courseData']) && !empty($userCourseData['courseData']))
		{
			$userCourseinfo = $userCourseData['courseData'];
		}

		if (isset($userCourseData['totalRows']) && !empty($userCourseData['totalRows']))
		{
			$totalRows = $userCourseData['totalRows'];
		}

		$path = JPATH_SITE . '/components/com_tjlms/helpers/main.php';

		if (!class_exists('comtjlmsHelper'))
		{
			JLoader::register('comtjlmsHelper', $path);
			JLoader::load('comtjlmsHelper');
		}

		$comtjlmsHelper = new comtjlmsHelper;

		$this->dash_icons_path = Uri::root(true) . '/media/com_tjlms/images/default/icons/';

		// Get plugin params
		$no_of_courses = $this->params->get('no_of_courses');

		// Load the layout & push variables
		ob_start();
		$layout = PluginHelper::getLayoutPath($this->_type, $this->_name, $this->params->get('layout', 'default'));
		include $layout;

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Function to get data of the whole block
	 *
	 * @param   ARRAY  $plg_data  data to be used to create whole block
	 *
	 * @return  data.
	 *
	 * @since 1.0.0
	 */
	public function getData($plg_data)
	{
		$this->techjoomlacommon = new TechjoomlaCommon;
		$lmsparams              = ComponentHelper::getParams('com_tjlms');
		$date_format_show       = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');
		$no_of_courses          = $this->params->get('no_of_courses');

		$db     = Factory::getDBO();
		$utcNow = $db->quote(Factory::getDate('now', 'UTC')->format('Y-m-d'));
		$query  = $db->getQuery(true);
		$query->select('c.id as id, c.title, c.image, c.certificate_term, eu.user_id');
		$query->select('IF(cert.expired_on < ' . $utcNow . ' AND cert.expired_on <> "0000-00-00 00:00:00", 1, 0) as cert_expired');
		$query->from('`#__tjlms_courses` as c');
		$query->join('LEFT', '#__tjlms_enrolled_users as eu ON eu.course_id=c.id');
		$query->join('LEFT', '#__tj_certificate_issue as cert ON (cert.client_id=c.id AND cert.user_id=eu.user_id)');
		$query->join('LEFT', '#__categories as cat ON cat.id = c.catid');
		$query->where('eu.user_id=' . $plg_data->user_id . ' AND c.state=1 AND eu.state=1 AND cat.published=1');
		$query->order('eu.id DESC');
		$query->group($db->quoteName('c.id'));

		$db->setQuery($query);
		$total_rows = $db->execute();

		// Get total number of rows
		$total_rows = $db->getNumRows();

		$query->setLimit($no_of_courses);

		// Set the query for execution.
		$db->setQuery($query);
		$userCourseinfo = $db->loadObjectList();

		require_once JPATH_SITE . '/components/com_tjlms/helpers/courses.php';
		$tjlmsCoursesHelper = new tjlmsCoursesHelper;
		$record             = array();

		// Include helper file to get todoid and contentid
		$path           = JPATH_SITE . '/components/com_jlike/helper.php';
		$ComjlikeHelper = "";

		if (File::exists($path))
		{
			if (!class_exists('ComjlikeHelper'))
			{
				JLoader::register('ComjlikeHelper', $path);
				JLoader::load('ComjlikeHelper');
			}

			$ComjlikeHelper = new ComjlikeHelper;
		}

		JLoader::import('components.com_tjlms.models.course', JPATH_SITE);
		$tjlmsModelcourse = BaseDatabaseModel::getInstance('Course', 'TjlmsModel', array('ignore_request' => true));

		JLoader::import('components.com_tjcertificate.includes.tjcertificate', JPATH_ADMINISTRATOR);

		foreach ($userCourseinfo as $onecourseinfo)
		{
			$content_id = $ComjlikeHelper->getContentId($onecourseinfo->id, 'com_tjlms.course');

			if (!empty($content_id) && !empty($onecourseinfo->user_id))
			{
				$query = $db->getQuery(true);

				$query->select($db->quoteName(array('td.id', 'td.start_date', 'td.due_date', 'u.name', 'td.assigned_to')));
				$query->from($db->quoteName('#__jlike_todos', 'td'));
				$query->join('INNER', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('u.id') . ' = ' . $db->quoteName('td.assigned_by') . ')');
				$query->where($db->quoteName('td.assigned_to') . " = " . $db->quote($onecourseinfo->user_id));
				$query->where($db->quoteName('td.content_id') . " = " . $db->quote($content_id));
				$query->where($db->quoteName('td.type') . " = " . $db->quote('assign'));

				$db->setQuery($query);
				$todo = $db->loadObject();
			}

			$userId                            = Factory::getUser()->id;
			$record_data                       = new stdclass;
			$record_data->id                   = $onecourseinfo->id;
			$record_data->title                = $onecourseinfo->title;
			$record_data->certificate_term     = $onecourseinfo->certificate_term;
			$record_data->cert_expired         = $onecourseinfo->cert_expired;
			$record_data->image                = $onecourseinfo->image;
			$record_data->last_accessed_lesson = $tjlmsCoursesHelper->getLessonBycondition($onecourseinfo->id, 'last_accessed_on', 'DESC', $userId);
			$record_data->module_data          = $tjlmsCoursesHelper->getCourseProgress($onecourseinfo->id, $plg_data->user_id);
			$certificateData                   = $tjlmsModelcourse->checkCertificateIssued($onecourseinfo->id, $userId);
			$record_data->certificateId        = isset($certificateData[0]->id) ? $certificateData[0]->id : '';

			// Assignment dates
			if (!empty($todo))
			{
				$record_data->assign_start_date = $this->techjoomlacommon->getDateInLocal($todo->start_date, 0, $date_format_show);
				$record_data->assign_due_date   = $this->techjoomlacommon->getDateInLocal($todo->due_date, 0, $date_format_show);
				$record_data->assigned_by       = $todo->name;
			}

			$record[] = $record_data;
			unset($todo);
		}

		$data['totalRows']  = $total_rows;
		$data['courseData'] = $record;

		return $data;
	}
}
