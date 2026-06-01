<?php
/**
 * @package     Shika.Plugin
 * @subpackage  tjdashboardsourse,enrolledcourses
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

$lang = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_tjlms', JPATH_ADMINISTRATOR);

/**
 * TjLms plugin for shika
 *
 * @since  1.0.0
 */

class TjlmsEnrolledcoursesDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_TJLMS_ENROLLED_COURSES";
	/**
	 * Function to get data of the whole block
	 *
	 * @return Array data.
	 *
	 * @since 1.0.0
	 */
	public function getData()
	{
		try
		{
			// @Todo This can be come throught plugins params
			$noOfCourses = 5;
			$user        = Factory::getUser();
			$userId      = $user->id;

			$db     = Factory::getDBO();
			$utcNow = $db->quote(Factory::getDate('now', 'UTC')->format('Y-m-d'));
			$query  = $db->getQuery(true);

			$query->select(array('c.id as id', 'c.title', 'c.image', 'c.certificate_term', 'eu.user_id'));
			$query->select('IF(cert.expired_on < ' . $utcNow . ' AND cert.expired_on <> "0000-00-00 00:00:00", 1, 0) as cert_expired');
			$query->from($db->qn('#__tjlms_courses', 'c'));
			$query->join('LEFT',
								$db->qn('#__tjlms_enrolled_users', 'eu') . 'ON ( ' . $db->qn('eu.course_id') . '=' . $db->qn('c.id') . ')'
						);
			$joinCondition  = $db->qn('cert.client_id') . '=' . $db->qn('c.id');
			$joinCondition .= ' AND ' . $db->qn('cert.user_id') . ' = ' . $db->qn('eu.user_id');
			$query->join('LEFT',
								$db->qn('#__tj_certificate_issue', 'cert') . 'ON ( ' . $joinCondition . ')'
						);
			$query->join('LEFT',
								$db->qn('#__categories', 'cat') . 'ON ( ' . $db->qn('cat.id') . ' = ' . $db->qn('c.catid') . ')'
						);
			$userCondition = $db->qn('eu.user_id') . ' = ' . (int) $userId;
			$query->where(
							$userCondition . ' AND ' . $db->qn('c.state') . '=1 AND' . $db->qn('eu.state') . '=1 AND ' . $db->qn('cat.published') . '=1'
							);
			$query->order('eu.id DESC');
			$query->group($db->qn('c.id'));

			$db->setQuery($query);
			$db->execute();

			// Get total number of rows
			$totalRows = $db->getNumRows();

			$query->setLimit($noOfCourses);

			// Set the query for execution.
			$db->setQuery($query);
			$userCourseinfo = $db->loadObjectList();

			JLoader::register('TjlmsCoursesHelper', JPATH_SITE . '/components/com_tjlms/helpers/courses.php');
			$tjlmsCoursesHelper = new TjlmsCoursesHelper;
			
			JLoader::register('ComtjlmstrackingHelper', JPATH_SITE . '/components/com_tjlms/helpers/tracking.php');
			$comtjlmstrackingHelper = new comtjlmstrackingHelper;

			$record = array();

			// Include helper file to get todoid and contentid
			$path = JPATH_SITE . '/components/com_jlike/helper.php';
			$comJlikeHelper = "";

			if (!class_exists('ComjlikeHelper'))
			{
				JLoader::register('ComjlikeHelper', $path);
				JLoader::load('ComjlikeHelper');
			}

			$comJlikeHelper = new ComjlikeHelper;

			$comtjlmsHelperPath = JPATH_ROOT . '/components/com_tjlms/helpers/main.php';

			if (!class_exists('comtjlmsHelper'))
			{
				JLoader::register('comtjlmsHelper', $comtjlmsHelperPath);
				JLoader::load('comtjlmsHelper');
			}

			$comtjlmsHelper = new comtjlmsHelper;

			$record['link'] = '';

			if ($totalRows > $noOfCourses)
			{
				$coursesLink = 'index.php?option=com_tjlms&view=courses&courses_to_show=enrolled';
				$record['link'] = $comtjlmsHelper->tjlmsRoute($coursesLink, false);
			}

			JLoader::import('components.com_tjlms.models.course', JPATH_SITE);
			$tjlmsModelcourse = BaseDatabaseModel::getInstance('Course', 'TjlmsModel', array('ignore_request' => true));

			JLoader::import('components.com_tjcertificate.includes.tjcertificate', JPATH_ADMINISTRATOR);

			foreach ($userCourseinfo as $onecourseinfo)
			{
				$content_id = $comJlikeHelper->getContentId($onecourseinfo->id, 'com_tjlms.course');

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

				$record_data                    = new stdclass;
				$record_data->id                = $onecourseinfo->id;
				$record_data->title             = "<a href='" .
				$comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $onecourseinfo->id) . "'>" . $onecourseinfo->title . "</a>";
				$record_data->certificate_term  = $onecourseinfo->certificate_term;
				$record_data->cert_expired  	= $onecourseinfo->cert_expired;
				$record_data->image             = $onecourseinfo->image;
				$record_data->last_accessed_lesson = $tjlmsCoursesHelper->getLessonBycondition($onecourseinfo->id, 'last_accessed_on', 'DESC', $userId);
				$record_data->module_data       = $tjlmsCoursesHelper->getCourseProgress($onecourseinfo->id, $userId);
				$record_data->percent 			= $record_data->module_data['completionPercent'];
				$record_data->certificate 		= ' - ';
				$record_data->completionPercent = round($record_data->module_data['completionPercent']);

				// Get issued certificate data.
				$certificateData            = $tjlmsModelcourse->checkCertificateIssued($onecourseinfo->id, $userId);
				$record_data->certificateId = $certificateData[0]->id;

				// Assignment dates
				if (!empty($todo))
				{
					$lmsparams  = ComponentHelper::getParams('com_tjlms');
					$dateFormat = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

					$record_data->assign_start_date = HTMLHelper::date($todo->start_date, $dateFormat, true);
					$record_data->assign_due_date   = HTMLHelper::date($todo->due_date, $dateFormat, true);
					$record_data->assigned_by       = $todo->name;
				}
				
				// $courseTrack = $comtjlmstrackingHelper->getCourseTrackEntry($onecourseinfo->id, $onecourseinfo->user_id);
				// $record_data->course_status = '';

				// if (!empty($courseTrack['status']))
				// {
				// 	// Display course status, if course status updated through backend the display status as 'Manually Completed'
				// 	if (($courseTrack['totalLessons'] != $courseTrack['completedLessons']) && $courseTrack['status'] == 'C')
				// 	{
				// 		$record_data->course_status .= Text::_('PLG_TJDASHBOARDSOURCE_TJLMS_COURSE_STATUS_MANUALLY');
				// 	}

				// 	$record_data->course_status .= Text::_('PLG_TJDASHBOARDSOURCE_TJLMS_COURSE_STATUS_' . $courseTrack['status']);
				// }

				if (($record_data->module_data['completionPercent'] == 100 || !empty($record_data->certificateId)) && $record_data->certificate_term != 0)
				{
					if (!$record_data->cert_expired && $record_data->certificateId)
					{
						$urlOpts          = array ();
						$urlOpts['popup'] = true;
						$certificateLink  = TJCERT::Certificate($record_data->certificateId)->getUrl($urlOpts, false);
						$linkName = Text::_('PLG_TJDASHBOARDSOURCE_TJLMS_GET_CERTIFICATE_LINK');
						$link = $comtjlmsHelper->
						tjlmsRoute($certificateLink);
						$certificate = "<a class=\"tjmodal certificate-link\" href=\"" . $link . "\" style=\"cursor:pointer\" >
											<span class=\"fa fa-certificate\" aria-hidden=\"true\"></span>
											<i>" . $linkName . "</i>
										</a>";
						$record_data->certificate = $certificate;
					}
					elseif ($record_data->module_data['completionPercent'] == 100 && $record_data->cert_expired)
					{
						$linkName = Text::_('PLG_TJDASHBOARDSOURCE_TJLMS_COURSE_CERTIFICATES_EXPIRED');
						$certificate = "<span class=\"text-danger\">"
											. $linkName .
										"</span>";
						$record_data->certificate = $certificate;
					}
				}

				$record['data'][] = $record_data;
				unset($todo);
			}
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		return $record;
	}

	/**
	 * Get Data for Plain Html bar
	 *
	 * @return string dataArray
	 *
	 * @since   1.0
	 * */
	public function getDataTabulatorTjdashtable()
	{
		$items = [];

		$data  = $this->getData();

		$items['data'] = (!empty($data['data'])?$data['data']:'');
		$items['columns'] = [
								["title" => 'Course Title', "field" => 'title',"formatter" => 'html', "headerSort" => false],
								["title" => 'Course Progress (%)',"field" => 'completionPercent',"align" => 'left',"formatter" => 'progress',
									"formatterParams" => ["color" => '#288ecf',"legend" => true, "legendColor" => 'black',"legendAlign" => 'left'], "headerSort" => false],
								["title" => 'Assigned By', "field" => 'assigned_by',"headerSort" => false],
								["title" => 'Start Date', "field" => 'assign_start_date',"headerSort" => false],
								["title" => 'Due Date', "field" => 'assign_due_date',"headerSort" => false],
								["title" => 'Certificate', "field" => 'certificate',"formatter" => 'html',"headerSort" => false],
								// ["title" => 'Course Status', "field" => 'course_status',"formatter" => 'html',"headerSort" => false],
							];

		if (!empty($data['link']))
		{
			$items['links'][] = ["title" => 'View All', "link" => $data['link']];
		}

		return json_encode($items);
	}

	/**
	 * Get supported Renderers List
	 *
	 * @return array supported renderes for this data source
	 *
	 * @since   1.0
	 * */
	public function getSupportedRenderers()
	{
		return array('tabulator.tjdashtable' => "PLG_TJDASHBOARDRENDERER_TABULATOR");
	}
}
