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
use Joomla\CMS\Date\Date;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');
jimport('joomla.application.component.controller');

if (!defined('DS'))
{
	define('DS', '/');
}

/**
 * Tjlms Installer
 *
 * @since  1.0.0
 */
class Pkg_ShikaInstallerScript
{
	/** @var array The list of extra modules and plugins to install */
	private $oldversion = "";

	private $removeFilesAndFolders = array(
		'files'	=> array(
			/* Removed since 1.2 */

			'/components/com_tjlms/libraries/scorm/api.php',
			'/components/com_tjlms/views/coupon/default.xml',
			'/components/com_tjlms/views/coupon/tmpl/default.xml',
			'/components/com_tjlms/views/enrolment/tmpl/default.php',
			'/components/com_tjlms/views/enrolment/view.html.php'
		),
		'folders' => array(
			/* Removed since 1.2 */
			'components/com_tjlms/views/enrolment/tmpl',
			'components/com_tjlms/views/enrolment',
			'administrator/components/com_tjlms/sql/updates/mysql'
		)
	);

	private $installation_queue = array(
										'modules' => array('admin' => array(),
															'site' => array(
																			'lms_course_blocks' => array('tjlms_course_blocks', 1, 0, '{"moduleclass_sfx":"","progress":"1","info":"1","assign_user":"1","taught_by":"1","recommend":"1","group_info":"1","enrolled":"1","fields":"0"}'),
																			'lms_categorylist' => array('tjlms_category', 1, 0, ''),
																			'lms_filter' => array('tjlms_filters', 1, 0, ''),
																			'lms_course_display' => array('right', 0, 0, '{"limit":"10","displayLimit":"5","module_mode":"lms_notEnrolled","include_enrolled_courses":"0","pin_width":"180","pin_padding":"3","title_height":"40","course_images_size":"S_","pin_view_config_set":"0","pin_view_likes":"1","pin_view_enrollments":"1","pin_view_category":"1","pin_view_tags":"0","layout":"_:default","moduleclass_sfx":"","module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"0"}'),
																			'lms_taglessons' => array('', 0, 0, ''))
															),
										'plugins' => array(
															'api' => array(
																			'lms' => 0
																		),
															'system' => array(
																			'tjupdates' => 1,
																			'tjassetsloader' => 1,
																			'plg_system_tjlms' => 1,
																			'lmsacymailing' => 0,
																			'mailchimp' => 0,
																			'tjanalytics' => 0,
																			'plg_system_sendemail' => 0),
															'user' => array(
																			'tjlmsuserlog' => 1,
																			'tjlmsuserredirection' => 0,
																			'tjnotificationsmobilenumber' => 0
																		),
															'content' => array(
																				'courseinfo' => 1,
																				'jlike_tjlms' => 1,
																				'jlike_tjlmslesson' => 1,
																				'tjintegration' => 1),
															'tjvideo' => array(
																				'jwplayer' => 0,
																				'vimeo' => 1,
																				'youtube' => 1,
																				'kpoint' => 0),
															'tjexternaltool' => array(
																					'ltiresource' => 0),
															'tjhtmlzips' => array(
																				'htmlzip' => 1),
															'tjscorm' => array(
																				'nativescorm' => 1),
															'tjdocument' => array(
																					'boxapi' => 0,
																					'boxapi2' => 1,
																					'pdfviewer' => 1
																				),
															'tjtextmedia' => array(
																					'htmlcreator' => 1,
																					'nativeeditor' => 1,
																					'joomlacontent' => 1,
																					'tjlmslessonlink' => 1),
															'tjevent' => array(
																				'jtevents' => 1,
																				'jtcategory' => 1
																			),
															'tjquiz' => array(
																				'quiz' => 1),
															'tjexercise' => array(
																				'exercise' => 1),
															'tjfeedback' => array(
																				'feedback' => 1),

															'search' => array(
																			'tjlmscourse' => 1, 'tjlmslesson' => 0),
															'payment' => array(
																				'authorizenet' => 0,
																				'bycheck' => 1,
																				'byorder' => 1,
																				'paypal' => 0,
																				'2checkout' => 0,
																				'jomsocialpoints' => 0,
																				'easysocialpoints' => 0,
																				'alphauserpoints' => 0),
															'lmstax' => array(
																				'lms_tax_default' => 0),
															'community' => array(
																				'course' => 0),
															'tjlmsdashboard' => array(
																						'activitygraph' => 1,
																						'activitylist' => 1,
																						'completedcoursescount' => 1,
																						'enrolledcourses' => 1,
																						'enrolledcoursescount' => 1,
																						'pendindenrolledcount' => 1,
																						'groupdiscussion' => 0,
																						'inprogresscoursescount' => 1,
																						'likedcourses' => 1,
																						'likedlesson' => 1,
																						'mydiscussions' => 1,
																						'mygroups' => 0,
																						'totaltimespent' => 1,
																						'totalidealtime' => 1,
																						'recommendedcourses' => 1),
															'tjdashboardsource' => array(
																						'tjlms' => array(
																									'activitygraph' => 1,
																									'completedcoursescount' => 1,
																									'enrolledcourses' => 1,
																									'enrolledcoursescount' => 1,
																									'freecoursescount' => 1,
																									'inprogresscoursescount' => 1,
																									'latestcourses' => 1,
																									'likedcourses' => 1,
																									'likedlesson' => 1,
																									'myactivities' => 1,
																									'mydiscussions' => 1,
																									'paidcourses' => 1,
																									'pendingenrolledcount' => 1,
																									'recommendedcourses' => 1,
																									'topusersbycoursescompleted' => 1,
																									'topusersbytimespent' => 1,
																									'totalcoursescount' => 1,
																									'totalorderscount' => 1,
																									'totalrevenuecount' => 1,
																								)
																					),

															'tjreports' => array(
																					'attemptreport' => 1,
																					'usercoursecategoryreport' => 1,
																					'coursereport' => 1,
																					'lessonreport' => 1,
																					'studentcoursereport' => 1,
																					'singlecoursereport' => 1,
																					'courseecommerce' => 0,
																					'paidcoursesreport' => 1,
																					'userreport' => 1,
																					'activityreport' => 1,
																					'tagstime' => 0,
																					'coursecategoryreport' => 1,
																					'coursegradebookreport' => 1,
																					'studentscoursesgradebookreport' => 1,
																					'testreport' => 1,
																					'scormreport' => 1,
																					'scormsummaryreport' => 1
																						),
															'actionlog' => array(
																					'tjlms' => 1
																					),
															'privacy' => array(
																					'tjlms' => 1
																						),
															'tjlms' => array(
																					'joomlausergroup' => 0,
																					'esgroup' => 1,
																					'easydiscuss' => 0,
																					'seb' => 0,
																					'espoint' => 0,
																					'courseprerequisite' => 1
																						),
															'tjlmsthankyoupage' => array(
																					'summary' => 0
																					),
															'tjsms' => array(
																				'twilio' => 0,
																				'clickatell' => 0,
																				'mvaayoo' => 0,
																				'smshorizon' => 0
																		),
															'tjurlshortner' => array(
																'bitly' => 0
															)
														),
										'libraries' => array(
														'techjoomla' => 1
														)
										);

	private $uninstall_queue = array(
		'modules' => array('admin' => array(),
							'site' => array('lms_course_blocks' => array('tjlms_course_blocks', 1, 0),
											'lms_categorylist' => array('tjlms_category', 1, 0),
											'lms_filter' => array('tjlms_filters', 1, 0),
											'lms_course_display' => array('right', 0, 0),
											'lms_taglessons' => array('', 0, 0))),

		'plugins' => array('system' => array(
										'plg_system_tjlms' => 1,
										'mailchimp' => 0,
										'lmsacymailing' => 0,
										'tjlmsblockregs' => 0,
										'tjanalytics' => 0,
										'plg_system_sendemail' => 0),
						'user' => array(
										'tjlmsuserlog' => 1,
										'tjlmsuserredirection' => 1,
										'tjnotificationsmobilenumber' => 0
									),
						'content' => array(
											'courseinfo' => 1,
											'jlike_tjlms' => 1,
											'jlike_tjlmslesson' => 1),
						'tjvideo' => array(
											'jwplayer' => 0,
											'vimeo' => 1,
											'youtube' => 1,
											'kpoint' => 1),
						'tjexternaltool' => array(
												'ltiresource' => 0),
						'tjhtmlzips' => array(
											'htmlzip' => 1),
						'tjscorm' => array(
											'nativescorm' => 1),
						'tjdocument' => array(
												'boxapi' => 1,
												'boxapi2' => 1,
												'pdfviewer' => 1
												),
						'tjtextmedia' => array(
												'htmlcreator' => 1,
												'nativeeditor' => 1,
												'tjlmslessonlink' => 1,
												'joomlacontent' => 1),
						'tjevent' => array('jtevents' => 1),
						'search' => array(
										'tjlmscourse' => 1, 'tjlmslesson' => 1),
						'lmstax' => array(
											'lms_tax_default' => 0),
						'community' => array(
											'course' => 1),
						'tjlmsdashboard' => array(
													'activitygraph' => 1,
													'activitylist' => 1,
													'completedcoursescount' => 1,
													'enrolledcourses' => 1,
													'enrolledcoursescount' => 1,
													'pendindenrolledcount' => 1,
													'groupdiscussion' => 0,
													'inprogresscoursescount' => 1,
													'likedcourses' => 1,
													'likedlesson' => 1,
													'mydiscussions' => 1,
													'mygroups' => 1,
													'totaltimespent' => 1,
													'totalidealtime' => 1,
													'recommendedcourses' => 1),
						'tjdashboardsource' => array(
												'tjlms' => array(
												'activitygraph' => 1,
												'completedcoursescount' => 1,
												'enrolledcourses' => 1,
												'enrolledcoursescount' => 1,
												'freecoursescount' => 1,
												'inprogresscoursescount' => 1,
												'latestcourses' => 1,
												'likedcourses' => 1,
												'likedlesson' => 1,
												'myactivities' => 1,
												'mydiscussions' => 1,
												'paidcourses' => 1,
												'pendingenrolledcount' => 1,
												'recommendedcourses' => 1,
												'topusersbycoursescompleted' => 1,
												'topusersbytimespent' => 1,
												'totalcoursescount' => 1,
												'totalorderscount' => 1,
												'totalrevenuecount' => 1,
											)
									),

												'coursereport' => 1,
												'lessonreport' => 1,
												'studentcoursereport' => 1,
												'userreport' => 1),
						'tjreports' => array(
												'attemptreport' => 1,
												'categoryreport' => 1,
												'usercoursecategoryreport' => 1,
												'coursereport' => 1,
												'lessonreport' => 1,
												'studentcoursereport' => 1,
												'singlecoursereport' => 1,
												'userreport' => 1,
												'activityreport' => 1,
												'coursecategoryreport' => 1,
												'coursegradebookreport' => 1,
												'studentscoursesgradebookreport' => 1),
						'actionlog' => array(
												'tjlms' => 1
											),
						'privacy'   => array(
												'tjlms' => 1
											),
						'tjlmsthankyoupage' => array(
												'summary' => 0
												),
						'tjsms' => array(
											'twilio' => 0,
											'clickatell' => 0,
											'mvaayoo' => 0,
											'smshorizon' => 0
									),
						'tjurlshortner' => array(
													'bitly' => 0
											)
				);

	/**
	 * method to run before an install/update/uninstall method
	 *
	 * @param   JInstaller  $type    type
	 * @param   JInstaller  $parent  parent
	 *
	 * @return void
	 */
	public function preflight($type, $parent)
	{
		$removeFilesAndFolders = $this->removeFilesAndFolders;
		$this->_removeObsoleteFilesAndFolders($removeFilesAndFolders);

		if ($type == 'update')
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('manifest_cache')))
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('element') . ' = ' . $db->quote('pkg_shika'))
				->where($db->quoteName('type') . ' = ' . $db->quote('package'));

			$db->setQuery($query);
			$result = $db->loadObject();
			$decode = json_decode($result->manifest_cache);
			$this->oldversion = $decode->version;
		}
	}

	/**
	 * method to install the component
	 *
	 * @param   JInstaller  $parent  parent
	 *
	 * @return  void
	 */
	public function install($parent)
	{
		// $parent is the class calling this method
	}

	/**
	 * Runs after install, update or discover_update
	 *
	 * @param   string      $type    install, update or discover_update
	 * @param   JInstaller  $parent  parent
	 *
	 * @return  void
	 */
	public function postflight($type, $parent)
	{
		// Install subextensions
		$status = $this->_installSubextensions($parent);

		// Install Techjoomla Straper
		$straperStatus = $this->_installStraper($parent);

		$document = Factory::getDocument();
		$document->addStyleSheet(JURI::root() . '/media/techjoomla_strapper/css/bootstrap.min.css');

		$this->timezoneMigration();
		$this->addTjReportsPlugins();

		$this->addTjDashboards();
		$this->installNotificationsTemplates();

		// Show the post-installation page
		$this->_renderPostInstallation($status, $straperStatus, $parent);
	}

	/**
	 * Execute the tj reports plugin queries
	 *
	 * @return  1
	 */
	public function addTjReportsPlugins()
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjreports/models');
		$model     = BaseDatabaseModel::getInstance('Reports', 'TjreportsModel');
		$installed = 0;

		if ($model)
		{
			$installed = $model->addTjReportsPlugins();
		}

		return $installed;
	}

	/**
	 * Add default dashboards for admin and user
	 *
	 * @return  1
	 */
	public function addTjDashboards()
	{
		$dashboards_json = File::read(JPATH_SITE . '/components/com_tjlms/dashboard.json');
		$dashboard       = json_decode($dashboards_json);

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjdashboard/models');

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select("dashboard_id");
		$query->from($db->quoteName('#__tj_dashboards'));
		$db->setQuery($query);
		$result = $db->loadObject();
		$user   = Factory::getUser();

		if (!empty($result) && $this->oldversion < '1.3.0')
		{
			try
			{
				$query = $db->getQuery(true);

				$query->delete($db->quoteName('#__tj_dashboard_widgets'));
				$db->setQuery($query);
				$db->execute();

				$query = $db->getQuery(true);
				$query->delete($db->quoteName('#__tj_dashboards'));
				$db->setQuery($query);
				$db->execute();

				$db->getQuery(true);
				$db->setQuery("ALTER TABLE `#__tj_dashboard_widgets` AUTO_INCREMENT = 1");
				$db->execute();

				$db->getQuery(true);
				$db->setQuery("ALTER TABLE `#__tj_dashboards` AUTO_INCREMENT = 1");
				$db->execute();

				$path = JPATH_PLUGINS . '/tjdashboardsource/tjlms/tjlms/activitydonut.php';

				if (File::exists($path))
				{
					File::delete($path);
				}
			}
			catch (Exception $e)
			{
				// Get a handle to the Joomla! application object
				$application = Factory::getApplication();

				// Add a message to the message queue
				$application->enqueueMessage($e->getMessage(), 'error');

				return false;
			}
		}

		if (empty($result) || (!empty($result) && $this->oldversion < '1.3.0'))
		{
			try
			{
				foreach ($dashboard as $key => $value)
				{
					$model = BaseDatabaseModel::getInstance('Dashboard', 'TjdashboardModel');

					$widgets = $value->widgets;
					$value->created_by = $user->id;
					$model->save((array) $value);
					$dashboard_id = (int) $model->getState('dashboard.dashboard_id');

					BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjdashboard/models');
					$widgetModel = BaseDatabaseModel::getInstance('Widget', 'TjdashboardModel');

					foreach ($widgets as $k => $v)
					{
						$v->dashboard_id = $dashboard_id;
						$v->created_by   = $user->id;
						$widgetModel->save((array) $v);
					}
				}

				return true;
			}
			catch (Exception $e)
			{
				// Get a handle to the Joomla! application object
				$application = Factory::getApplication();

				// Add a message to the message queue
				$application->enqueueMessage($e->getMessage(), 'error');

				return false;
			}
		}
	}

	/**
	 * Update the UTC offset relative to the server timezone not the user's.
	 *
	 * @since   1.2
	 * @return  array | boolean
	 */
	private function timezoneMigration()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id');
		$query->from($db->quoteName('#__tjlms_migration'));
		$query->where($db->quoteName('client') . " = 'com_tjlms'");
		$query->where($db->quoteName('action') . " = 'activity'");
		$query->where($db->quoteName('flag') . " = 1");
		$db->setQuery($query);
		$result = $db->loadResult();

		if ($result)
		{
			return false;
		}

		// Take time difference between server and UTC
		$timeDifference = date('Z');

		if ($timeDifference != 0)
		{
			// If the time difference is in +Ve then substract OR if in -Ve than add into the current time
			$timeDifference *= -1;

			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$fields = array($db->quoteName('added_time') . ' = DATE_ADD(added_time, INTERVAL ' . $timeDifference . ' SECOND)');
			$query->update($db->quoteName('#__tjlms_activities'))->set($fields);
			$db->setQuery($query);
			$db->execute();
		}

		$migrationObj                 = new stdClass;
		$migrationObj->id             = '';
		$migrationObj->client         = 'com_tjlms';
		$migrationObj->action         = 'activity';
		$migrationObj->flag           = 1;
		$migrationObj->params         = json_encode($timeDifference);
		$migrationObj->migration_date = Factory::getdate()->toSql();

		// Insert the object into the Migration table.
		Factory::getDbo()->insertObject('#__tjlms_migration', $migrationObj);

		return false;
	}

	/**
	 * Removes obsolete files and folders
	 *
	 * @param   array  $removeFilesAndFolders  Array of the files and folders to be removed
	 *
	 * @return  void
	 */
	private function _removeObsoleteFilesAndFolders($removeFilesAndFolders)
	{
		// Remove files
		jimport('joomla.filesystem.file');

		if (!empty($removeFilesAndFolders['files']))
		{
			foreach ($removeFilesAndFolders['files'] as $file)
			{
				$f = JPATH_ROOT . '/' . $file;

				if (!File::exists($f))
				{
					continue;
				}

				File::delete($f);
			}
		}

		// Remove folders
		jimport('joomla.filesystem.file');

		if (!empty($removeFilesAndFolders['folders']))
		{
			foreach ($removeFilesAndFolders['folders'] as $folder)
			{
				$f = JPATH_ROOT . '/' . $folder;

				if (!JFolder::exists($f))
				{
					continue;
				}

				JFolder::delete($f);
			}
		}
	}

	/**
	 * Install strappers
	 *
	 * @param   JInstaller  $parent  parent
	 *
	 * @return  void
	 */
	private function _installStraper($parent)
	{
		$src = $parent->getParent()->getPath('source');

		// Install the FOF framework
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');
		jimport('joomla.utilities.date');
		$source = $src . DS . 'tj_strapper';
		$target = JPATH_ROOT . DS . 'media' . DS . 'techjoomla_strapper';

		$haveToInstallStraper = false;

		if (!JFolder::exists($target))
		{
			$haveToInstallStraper = true;
		}
		else
		{
			$straperVersion = array();

			if (File::exists($target . DS . 'version.txt'))
			{
				$rawData                     = File::read($target . DS . 'version.txt');
				$info                        = explode("\n", $rawData);
				$straperVersion['installed'] = array(
					'version' => trim($info[0]),
					'date' => new Date(trim($info[1]))
				);
			}
			else
			{
				$straperVersion['installed'] = array(
					'version' => '0.0',
					'date' => new Date('2011-01-01')
				);
			}

			$rawData                   = File::read($source . DS . 'version.txt');
			$info                      = explode("\n", $rawData);
			$straperVersion['package'] = array(
				'version' => trim($info[0]),
				'date' => new Date(trim($info[1]))
			);

			$haveToInstallStraper = $straperVersion['package']['date']->toUNIX() > $straperVersion['installed']['date']->toUNIX();
		}

		$installedStraper = false;

		if ($haveToInstallStraper)
		{
			$versionSource    = 'package';
			$installer        = new JInstaller;
			$installedStraper = $installer->install($source);
		}
		else
		{
			$versionSource = 'installed';
		}

		if (!isset($straperVersion))
		{
			$straperVersion = array();

			if (File::exists($target . DS . 'version.txt'))
			{
				$rawData                     = File::read($target . DS . 'version.txt');
				$info                        = explode("\n", $rawData);
				$straperVersion['installed'] = array(
					'version' => trim($info[0]),
					'date' => new Date(trim($info[1]))
				);
			}
			else
			{
				$straperVersion['installed'] = array(
					'version' => '0.0',
					'date' => new Date('2011-01-01')
				);
			}

			$rawData                   = File::read($source . DS . 'version.txt');
			$info                      = explode("\n", $rawData);
			$straperVersion['package'] = array(
				'version' => trim($info[0]),
				'date' => new Date(trim($info[1]))
			);
			$versionSource             = 'installed';
		}

		if (!($straperVersion[$versionSource]['date'] instanceof Date))
		{
			$straperVersion[$versionSource]['date'] = new Date;
		}

		return array(
			'required' => $haveToInstallStraper,
			'installed' => $installedStraper,
			'version' => $straperVersion[$versionSource]['version'],
			'date' => $straperVersion[$versionSource]['date']->format('Y-m-d')
		);
	}

	/**
	 * Renders the post-installation message
	 *
	 * @param   JInstaller  $status         parent
	 * @param   JInstaller  $straperStatus  parent
	 * @param   JInstaller  $parent         parent
	 *
	 * @return  void
	 */
	private function _renderPostInstallation($status, $straperStatus, $parent)
	{
		$document = Factory::getDocument();
		Factory::getLanguage()->load('com_tjlms', JPATH_ADMINISTRATOR, null, true);
?>
	   <?php
		$rows = 1;
?>
	   <link rel="stylesheet" type="text/css" href="<?php
		echo JURI::root() . 'media/techjoomla_strapper/css/bootstrap.min.css';
?>"/>
		<div class="techjoomla-bootstrap" >
		<div class="alert alert-info">
			<div class="row-fluid">
				<strong>In order to complete the Update please make sure you do ALL the following steps.</strong>
			</div>
			<div class="row-fluid">
				1.Go to the Shika Dashboard and look for the <strong>Migrate Tables</strong> button in the top left corner.
				Click that. If all goes well you should get various success messages.
			</div>
			<div class="row-fluid">
				2.Go to the Shika Dashboard and look for the <strong>Add Indexes</strong> button in the top left corner.
				This is for improving performance of the database.
				Click on the same. You should see a success message.
			</div>
			<div class="row-fluid">
				3.Go to Components > jLike. On that dashboard as well you will see a <strong>Fix Database</strong> button in the top left.
				Shika uses jLike for the various content interactions so this fix is also needed.
			</div>
		</div>
		<table class="table-condensed table" width="100%">
			<thead>
				<tr class="row1">
					<th class="title" colspan="2">Extension</th>
					<th>Status</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="3"></td>
				</tr>
			</tfoot>
			<tbody>
				<tr class="row2">
					<td class="key" colspan="2"><strong>Shika component</strong></td>
					<td><strong style="color: green">Installed</strong></td>
				</tr>

				<tr class="row2">
					<td class="key" colspan="2">
						<strong>TechJoomla Strapper <?php
		echo $straperStatus['version'];
?></strong> [<?php
		echo $straperStatus['date'];
?>]
					</td>
					<td>
						<strong>
							<span style="color: <?php
		echo $straperStatus['required'] ? ($straperStatus['installed'] ? 'green' : 'red') : '#660';
?>; font-weight: bold;">
								<?php
		echo $straperStatus['required'] ? ($straperStatus['installed'] ? 'Installed' : 'Not Installed') : 'Already up-to-date';
?>
							</span>
						</strong>
					</td>
				</tr>

				<?php
		if (count($status->modules))
		{
?>
			   <tr class="row1">
					<th>Module</th>
					<th>Client</th>
					<th></th>
					</tr>
			<?php
			foreach ($status->modules as $module)
			{
?>
			   <tr class="row2">
					<td class="key"><?php
				echo ucfirst($module['name']);
?></td>
					<td class="key"><?php
				echo ucfirst($module['client']);
?></td>
					<td><strong style="color: <?php
				echo $res = ($module['result']) ? "green" : "red";
?>"><?php
				echo $res = ($module['result']) ? 'Installed' : 'Not installed';
?></strong>
				<?php
				// If installed then only show msg
				if (!empty($module['result']))
				{
					echo $mstat = ($module['status']?"<span class=\"label label-success\">Enabled</span>":"<span class=\"label label-important\">Disabled</span>");
				}
?>

					</td>
				</tr>
				<?php
			}
?>
			   <?php
		}
?>
			   <?php
		if (count($status->plugins))
		{
?>
			   <tr class="row1">
					<th colspan="2">Plugin</th>
			<!--        <th>Group</th> -->
					<th></th>
				</tr>
				<?php
			$oldplugingroup = "";

			foreach ($status->plugins as $plugin)
			{
				if ($oldplugingroup != $plugin['group'])
				{
					$oldplugingroup = $plugin['group'];
?>
				   <tr class="row0">
						<th colspan="2"><strong><?php
					echo ucfirst($oldplugingroup) . " Plugins";
?></strong></th>
						<th></th>
				<!--        <td></td> -->
					</tr>
				<?php
				}

?>
			   <tr class="row2">
					<td colspan="2" class="key"><?php
				echo ucfirst($plugin['name']);
?></td>

				<td><strong style="color: <?php
				echo $tdcolor = ($plugin['result']) ? "green" : "red";
?>"><?php
				echo $tdresult = ($plugin['result']) ? 'Installed' : 'Not installed';
?></strong>
					<?php
				if (!empty($plugin['result']))
				{
					echo $pstat = ($plugin['status']?"<span class=\"label label-success\">Enabled</span>":"<span class=\"label label-important\">Disabled</span>");
				}
?>
				   </td>
				</tr>
				<?php
			}
?>
			   <?php
		}
?>

				<!-- LIB INSTALL-->
				<?php
		if (count($status->libraries))
		{
?>
			   <tr class="row1">
					<th>Library</th>
					<th></th>
					<th></th>
					</tr>
				<?php
			foreach ($status->libraries as $libraries)
			{
?>
			   <tr class="row2">
					<td class="key"><?php
				echo ucfirst($libraries['name']);
?></td>
					<td class="key"></td>
					<td><strong style="color: <?php
				echo $libraries['result'] ? "green" : "red";
?>"><?php
				echo $libraries['result'] ? 'Installed' : 'Not installed';
?></strong>
					<?php

				// If installed then only show msg
				if (!empty($libraries['result']))
				{
					/*echo $mstat=($libraries['status']?
					"<span class=\"label label-success\">Enabled</span>" :
					"<span class=\"label label-important\">Disabled</span>");*/
				}
?>

					</td>
				</tr>
				<?php
			}
		}

		if (!empty($status->app_install))
		{
			if (count($status->app_install))
			{
				?>
				<tr class="row1">
					<th><?php echo JText::_('Easysocial APP');	?></th>
					<th></th>
					<th></th>
					</tr>
				<?php
				foreach ($status->app_install as $app_install)
				{
					?>
				<tr class="row2">
					<td class="key"><?php
					echo ucfirst($app_install['name']);?></td>
					<td class="key"></td>
					<td><strong style="color: <?php
					echo $app_install['result'] ? "green" : "red";?>"><?php
					echo $app_install['result'] ? 'Installed' : 'Not installed';?></strong>
					<?php
					if (!empty($app_install['result']))
					{
						$apps = $app_install['status'] ? "<span class=\"label label-success\">Enabled</span>" : "<span class=\"label label-important\">Disabled</span>";

						echo $apps;
					}?>

					</td>
				</tr>
				<?php
				}?>
				<?php
			}
		}?>

			</tbody>
		</table>
		</div> <!-- end akeeba bootstrap -->

		<?php
	}

	/**
	 * Installs subextensions (modules, plugins) bundled with the main extension
	 *
	 * @param   JInstaller  $parent  parent
	 *
	 * @return  JObject The subextension installation status
	 */
	private function _installSubextensions($parent)
	{
		$src = $parent->getParent()->getPath('source');
		$db  = Factory::getDbo();

		$status          = new JObject;
		$status->modules = array();
		$status->plugins = array();

		// Modules installation

		if (count($this->installation_queue['modules']))
		{
			foreach ($this->installation_queue['modules'] as $folder => $modules)
			{
				if (count($modules))
				{
					foreach ($modules as $module => $modulePreferences)
					{
						// Install the module
						if (empty($folder))
						{
							$folder = 'site';
						}

						$path = "$src/modules/$folder/$module";

						// If not dir
						if (!is_dir($path))
						{
							$path = "$src/modules/$folder/mod_$module";
						}

						if (!is_dir($path))
						{
							$path = "$src/modules/$module";
						}

						if (!is_dir($path))
						{
							$path = "$src/modules/mod_$module";
						}

						if (!is_dir($path))
						{
							$fortest = '';

							// Continue;
						}

						// Was the module already installed?
						$sql = $db->getQuery(true)->select('COUNT(*)')->from('#__modules')->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));
						$db->setQuery($sql);

						$count = $db->loadResult();

						$installer         = new JInstaller;
						$result            = $installer->install($path);

						if ($count)
						{
							$query = $db->getQuery(true);
							$query->select('published')->from($db->qn('#__modules'))->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));
							$db->setQuery($query);
							$checkifpublished = $db->loadColumn();

							$mod_published  = 0;

							if (in_array('1', $checkifpublished))
							{
								$mod_published = 1;
							}

							$status->modules[] = array(
							'name' => $module,
							'client' => $folder,
							'result' => $result,
							'status' => $mod_published
							);
						}
						else
						{
							$status->modules[] = array(
							'name' => $module,
							'client' => $folder,
							'result' => $result,
							'status' => $modulePreferences[1]
							);
						}

						// Modify where it's published and its published state
						if (!$count)
						{
							// A. Position and state
							list($modulePosition, $modulePublished, $moduleshowtitle, $moduleParams) = $modulePreferences;

							if ($modulePosition == 'cpanel')
							{
								$modulePosition = 'icon';
							}

							$sql = $db->getQuery(true)->update($db->qn('#__modules'))
							->set($db->qn('position') . ' = ' . $db->q($modulePosition))
							->set($db->qn('showtitle') . ' = ' . $db->q($moduleshowtitle))
							->set($db->qn('params') . ' = ' . $db->q($moduleParams))
							->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));

							if ($modulePublished)
							{
								$sql->set($db->qn('published') . ' = ' . $db->q('1'));
							}

							$db->setQuery($sql);
							$db->query();

							// B. Change the ordering of back-end modules to 1 + max ordering
							if ($folder == 'admin')
							{
								$query = $db->getQuery(true);
								$query->select('MAX(' . $db->qn('ordering') . ')')->from($db->qn('#__modules'))->where($db->qn('position') . '=' . $db->q($modulePosition));
								$db->setQuery($query);
								$position = $db->loadResult();
								$position++;

								$query = $db->getQuery(true);
								$query->update($db->qn('#__modules'))
								->set($db->qn('ordering') . ' = ' . $db->q($position))
								->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));
								$db->setQuery($query);
								$db->query();
							}

							// C. Link to all pages
							$query = $db->getQuery(true);
							$query->select('id')->from($db->qn('#__modules'))->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));
							$db->setQuery($query);
							$moduleid = $db->loadResult();

							$query = $db->getQuery(true);
							$query->select('*')->from($db->qn('#__modules_menu'))->where($db->qn('moduleid') . ' = ' . $db->q($moduleid));
							$db->setQuery($query);
							$assignments = $db->loadObjectList();
							$isAssigned  = !empty($assignments);

							if (!$isAssigned)
							{
								$o = (object) array(
									'moduleid' => $moduleid,
									'menuid' => 0
								);
								$db->insertObject('#__modules_menu', $o);
							}
						}
					}
				}
			}
		}

		// Plugins installation
		if (count($this->installation_queue['plugins']))
		{
			foreach ($this->installation_queue['plugins'] as $folder => $plugins)
			{
				if (count($plugins))
				{
					foreach ($plugins as $plugin => $published)
					{
						$path = "$src/plugins/$folder/$plugin";

						if (!is_dir($path))
						{
							$path = "$src/plugins/$folder/plg_$plugin";
						}

						if (!is_dir($path))
						{
							$path = "$src/plugins/$plugin";
						}

						if (!is_dir($path))
						{
							$path = "$src/plugins/plg_$plugin";
						}

						if (!is_dir($path))
						{
							continue;
						}

						// Was the plugin already installed?
						$query = $db->getQuery(true)->select('COUNT(*)')
						->from($db->qn('#__extensions'))
						->where('( ' . ($db->qn('name') . ' = ' . $db->q($plugin)) . ' OR ' . ($db->qn('element') . ' = ' . $db->q($plugin)) . ' )')
						->where($db->qn('folder') . ' = ' . $db->q($folder));
						$db->setQuery($query);
						$count = $db->loadResult();

						$installer = new JInstaller;
						$result    = $installer->install($path);

						if ($count)
						{
							// Was the plugin already installed?
							$query = $db->getQuery(true)->select('enabled')
							->from($db->qn('#__extensions'))
							->where('( ' . ($db->qn('name') . ' = ' . $db->q($plugin)) . ' OR ' . ($db->qn('element') . ' = ' . $db->q($plugin)) . ' )')
							->where($db->qn('folder') . ' = ' . $db->q($folder));
							$db->setQuery($query);
							$enabled = $db->loadResult();

							$status->plugins[] = array(
								'name' => $plugin,
								'group' => $folder,
								'result' => $result,
								'status' => $enabled
							);
						}
						else
						{
							$status->plugins[] = array(
								'name' => $plugin,
								'group' => $folder,
								'result' => $result,
								'status' => $published
							);
						}

						if ($published && !$count)
						{
							$query = $db->getQuery(true)
							->update($db->qn('#__extensions'))
							->set($db->qn('enabled') . ' = ' . $db->q('1'))
							->where('( ' . ($db->qn('name') . ' = ' . $db->q($plugin)) . ' OR ' . ($db->qn('element') . ' = ' . $db->q($plugin)) . ' )')
							->where($db->qn('folder') . ' = ' . $db->q($folder));
							$db->setQuery($query);
							$db->query();
						}
					}
				}
			}
		}

		// Library installation
		if (count($this->installation_queue['libraries']))
		{
			foreach ($this->installation_queue['libraries'] as $folder => $status1)
			{
				$path = "$src/libraries/$folder";

				$query = $db->getQuery(true)->select('COUNT(*)')
				->from($db->qn('#__extensions'))
				->where('( ' . ($db->qn('name') . ' = ' . $db->q($folder)) . ' OR ' . ($db->qn('element') . ' = ' . $db->q($folder)) . ' )')
				->where($db->qn('folder') . ' = ' . $db->q($folder));
				$db->setQuery($query);
				$count = $db->loadResult();

				$installer = new JInstaller;
				$result    = $installer->install($path);

				$status->libraries[] = array(
					'name' => $folder,
					'group' => $folder,
					'result' => $result,
					'status' => $status1
				);

				if ($published && !$count)
				{
					$query = $db->getQuery(true)
					->update($db->qn('#__extensions'))
					->set($db->qn('enabled') . ' = ' . $db->q('1'))
					->where('( ' . ($db->qn('name') . ' = ' . $db->q($folder)) . ' OR ' . ($db->qn('element') . ' = ' . $db->q($folder)) . ' )')
					->where($db->qn('folder') . ' = ' . $db->q($folder));
					$db->setQuery($query);
					$db->query();
				}
			}
		}

		if (file_exists(JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php'))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php';

			$installer = Foundry::get('Installer');

			// The $path here refers to your application path
			$installer->load($src . "/plugins/app/course");

			$plg_install           = $installer->install();

			$status->app_install[] = array(
				'name' => 'course',
				'group' => 'user',
				'result' => $plg_install->id,
				'status' => '1'
			);
		}

		return $status;
	}

	/**
	 * Uninstalls subextensions (modules, plugins) bundled with the main extension
	 *
	 * @param   JInstaller  $parent  parent
	 *
	 * @return  JObject The subextension uninstallation status
	 */
	private function _uninstallSubextensions($parent)
	{
		jimport('joomla.installer.installer');

		$db = Factory::getDBO();

		$status          = new JObject;
		$status->modules = array();
		$status->plugins = array();

		// Modules uninstallation
		if (count($this->uninstall_queue['modules']))
		{
			foreach ($this->uninstall_queue['modules'] as $folder => $modules)
			{
				if (count($modules))
				{
					foreach ($modules as $module => $modulePreferences)
					{
						// Find the module ID
						$sql = $db->getQuery(true)->select($db->qn('extension_id'))
						->from($db->qn('#__extensions'))
						->where($db->qn('element') . ' = ' . $db->q('mod_' . $module))
						->where($db->qn('type') . ' = ' . $db->q('module'));
						$db->setQuery($sql);
						$id = $db->loadResult();

						// Uninstall the module
						if ($id)
						{
							$installer         = new JInstaller;
							$result            = $installer->uninstall('module', $id, 1);
							$status->modules[] = array(
								'name' => 'mod_' . $module,
								'client' => $folder,
								'result' => $result
							);
						}
					}
				}
			}
		}

		// Plugins uninstallation
		if (count($this->uninstall_queue['plugins']))
		{
			foreach ($this->uninstall_queue['plugins'] as $folder => $plugins)
			{
				if (count($plugins))
				{
					foreach ($plugins as $plugin => $published)
					{
						$sql = $db->getQuery(true)->select($db->qn('extension_id'))
						->from($db->qn('#__extensions'))
						->where($db->qn('type') . ' = ' . $db->q('plugin'))
						->where($db->qn('element') . ' = ' . $db->q($plugin))
						->where($db->qn('folder') . ' = ' . $db->q($folder));
						$db->setQuery($sql);

						$id = $db->loadResult();

						if ($id)
						{
							$installer         = new JInstaller;
							$result            = $installer->uninstall('plugin', $id);
							$status->plugins[] = array(
								'name' => 'plg_' . $plugin,
								'group' => $folder,
								'result' => $result
							);
						}
					}
				}
			}
		}

		return $status;
	}

	/**
	 * _renderPostUninstallation
	 *
	 * @param   STRING  $status  status of installed extensions
	 * @param   ARRAY   $parent  parent item
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	private function _renderPostUninstallation($status, $parent)
	{
?>
	   <?php
		$rows = 0;
?>
	   <h2><?php
		echo JText::_('TjLMS Uninstallation Status');
?></h2>
		<table class="adminlist">
			<thead>
				<tr>
					<th class="title" colspan="2"><?php
		echo JText::_('Extension');
?></th>
					<th width="30%"><?php
		echo JText::_('Status');
?></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="3"></td>
				</tr>
			</tfoot>
			<tbody>
				<tr class="row0">
					<td class="key" colspan="2"><?php
		echo 'TjLMS ' . JText::_('Component');
?></td>
					<td><strong style="color: green"><?php
		echo JText::_('Removed');
?></strong></td>
				</tr>
				<?php
		if (count($status->modules))
		{
?>
			   <tr>
					<th><?php
			echo JText::_('Module');
?></th>
					<th><?php
			echo JText::_('Client');
?></th>
					<th></th>
				</tr>
				<?php
			foreach ($status->modules as $module)
			{
				$rows++;
?>
			   <tr class="row<?php echo $rows % 2;?>">
					<td class="key"><?php echo $module['name'];?></td>
					<td class="key"><?php echo ucfirst($module['client']);?></td>
					<td>
						<strong style="color: <?php echo $module['result'] ? "green" : "red";?>">
							<?php echo $module['result'] ? JText::_('Removed') : JText::_('Not removed');?>
						</strong>
					</td>
				</tr>
				<?php
			}
?>
			   <?php
		}
?>
			   <?php
		if (count($status->plugins))
		{
?>
			   <tr>
					<th><?php
			echo JText::_('Plugin');
?></th>
					<th><?php
			echo JText::_('Group');
?></th>
					<th></th>
				</tr>
				<?php
			foreach ($status->plugins as $plugin)
			{
				$rows++;
?>
			   <tr class="row<?php
				echo $rows % 2;
?>">
					<td class="key"><?php
				echo ucfirst($plugin['name']);
?></td>
					<td class="key"><?php
				echo ucfirst($plugin['group']);
?></td>
					<td><strong style="color: <?php
				echo $plugin['result'] ? "green" : "red";
?>"><?php
				echo $plugin['result'] ? JText::_('Removed') : JText::_('Not removed');
?></strong></td>
				</tr>

	<?php
			}
		}

		?>
		   </tbody>
		</table>
		<?php
	}

	/**
	 * Runs on uninstallation
	 *
	 * @param   JInstaller  $parent  Parent
	 *
	 * @return void
	 *
	 * @since   1.0.0
	 */
	public function uninstall($parent)
	{
		// Uninstall subextensions
		$status = $this->_uninstallSubextensions($parent);

		// Show the post-uninstallation page
		$this->_renderPostUninstallation($status, $parent);
	}

	/**
	 * method to update the component
	 *
	 * @param   JInstaller  $parent  Parent
	 *
	 * @return void
	 */
	public function update($parent)
	{
		$db     = Factory::getDBO();
		$config = Factory::getConfig();

		if (JVERSION >= 3.0)
		{
			$configdb = $config->get('db');
		}
		else
		{
			$configdb = $config->getValue('config.db');
		}
		// Get dbprefix
		if (JVERSION >= 3.0)
		{
			$dbprefix = $config->get('dbprefix');
		}
		else
		{
			$dbprefix = $config->getValue('config.dbprefix');
		}

		// Update LmsCourseblocks module params.
		$this->updateModLmsCourseBlocksParams();

		// Update LmsCourseDisplay module params.
		$this->updateModLmsCourseDisplayParams_139();

		// Update Courses manu params.
		$this->updateMenuParams_139();

	}

	/**
	 * Installed Notifications Templates
	 *
	 * @return  void
	 */
	public function installNotificationsTemplates()
	{
		$client = 'com_tjlms';
		jimport('joomla.application.component.model');
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjnotifications/tables');
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjnotifications/models');
		$notificationsModel = BaseDatabaseModel::getInstance('Notification', 'TJNotificationsModel');

		$filePath = JPATH_ADMINISTRATOR . '/components/com_tjlms/shikaTemplate.json';
		$str      = file_get_contents($filePath);
		$json     = json_decode($str, true);

		$existingKeys = $notificationsModel->getKeys($client);

		$replacementTagCount = array ();

		if (count($json) != 0)
		{
			foreach ($json as $template => $array)
			{
				$replacementTagCount = $notificationsModel->getReplacementTagsCount($array['key'], 'com_tjlms');

				// If template doesn't exist then we add notification template.
				if (!in_array($array['key'], $existingKeys))
				{
					$notificationsModel->createTemplates($array);
				}
				else
				{
					$notificationsModel->updateTemplates($array, $client);
				}

				// If the number of replacement tags changed then update. Please note that this will not update if tag name is updated in the template JSON
				if (in_array($array['key'], $existingKeys) && isset($array['replacement_tags']) && count($array['replacement_tags']) != $replacementTagCount)
				{
					$notificationsModel->updateReplacementTags($array);
				}
			}
		}
	}

	/**
	 * Update module params.
	 *
	 * @return  void
	 */
	public function updateModLmsCourseBlocksParams()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('mod.params');
		$query->from($db->quoteName('#__modules', 'mod'));
		$query->where($db->quoteName('mod.module') . " = 'mod_lms_course_blocks'");
		$query->where($db->quoteName('mod.params') . " = ' '");
		$db->setQuery($query);
		$result = $db->loadResult();

		if (empty($result))
		{
			$query = $db->getQuery(true);

			// Update the params for one or multiple moduels .
			$fields = array(
				$db->quoteName('params') . ' = ' .
					$db->quote(
					'{"moduleclass_sfx":"","progress":"1","info":"1","assign_user":"1","taught_by":"1","recommend":"1","group_info":"1","enrolled":"1","fields":"0"}'
					)
			);

			$conditions = array(
				$db->quoteName('module') . ' = ' . $db->quote('mod_lms_course_blocks')
			);
			$query->update($db->quoteName('#__modules'))->set($fields)->where($conditions);
			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Update module params.
	 *
	 * @return  void
	 */
	public function updateModLmsCourseDisplayParams_139()
	{
		if ($this->oldversion < '1.3.39')
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('mod.params');
			$query->from($db->quoteName('#__modules', 'mod'));
			$query->where($db->quoteName('mod.module') . " = 'mod_lms_course_display'");
			$db->setQuery($query);
			$result       = $db->loadResult();

			$params = '{"limit":"10","displayLimit":"5","module_mode":"lms_notEnrolled","include_enrolled_courses":"0","pin_width":"180","pin_padding":"3","title_height":"40","course_images_size":"S_","pin_view_config_set":"0","pin_view_likes":"1","pin_view_enrollments":"1","pin_view_category":"1","pin_view_tags":"0","layout":"_:default","moduleclass_sfx":"","module_tag":"div","bootstrap_size":"0","header_tag":"h3","header_class":"","style":"0"}';

			if (!empty($result))
			{
				$decodeResult = json_decode($result);
				$resultCount  = count(get_object_vars($decodeResult));

				$decodeParam = json_decode($params);
				$paramsCount = count(get_object_vars($decodeParam));

				$newParams      = '{"pin_view_config_set":"0","pin_view_likes":"1","pin_view_enrollments":"1","pin_view_category":"1","pin_view_tags":"0"}';
				$decodeNewParam = json_decode($newParams);

				if ($resultCount < $paramsCount)
				{
					$query    = $db->getQuery(true);
					$paramObj = (object) array_merge((array) $decodeResult, (array) $decodeNewParam);
					$param    = json_encode($paramObj);

					// Update the params for one or multiple moduels .
					$fields = array(
						$db->quoteName('params') . ' = ' . $db->quote($param)
					);

					$conditions = array(
						$db->quoteName('module') . ' = ' . $db->quote('mod_lms_course_display')
					);
					$query->update($db->quoteName('#__modules'))->set($fields)->where($conditions);
					$db->setQuery($query);
					$db->execute();
				}
			}
			else
			{
				$query    = $db->getQuery(true);

				// Update the params for one or multiple moduels .
				$fields = array(
					$db->quoteName('params') . ' = ' . $db->quote($params)
				);

				$conditions = array(
					$db->quoteName('module') . ' = ' . $db->quote('mod_lms_course_display')
				);
				$query->update($db->quoteName('#__modules'))->set($fields)->where($conditions);
				$db->setQuery($query);
				$db->execute();
			}
		}
	}

	/**
	 * Update module params.
	 *
	 * @return  void
	 */
	public function updateMenuParams_139()
	{
		if ($this->oldversion < '1.3.39')
		{
			$value = 0;

			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('id, params');
			$query->from($db->quoteName('#__menu'));
			$query->where($db->quoteName('link') . "LIKE" . $db->quote('%index.php?option=com_tjlms&view=courses%'));
			$db->setQuery($query);
			$results       = $db->loadObjectList();

			foreach ($results as  $result)
			{
				$decodeResult = json_decode($result->params);

				$arrayDecodeResult = (array) $decodeResult;

				$arrayNewParams = array("pin_view_config_set","pin_view_type","pin_view_likes","pin_view_comments","pin_view_enrollments","pin_view_category","pin_view_tags");

				$defaultOneParam = array("pin_view_likes","pin_view_enrollments","pin_view_category");

				foreach ($arrayNewParams as $newParam)
				{
					if (in_array($newParam, $defaultOneParam))
					{
						$value = 1;
					}

					if (!array_key_exists($newParam, $arrayDecodeResult))
					{
						$arrayDecodeResult[$newParam] = $value;
					}

					$value = 0;
				}

				$query    = $db->getQuery(true);
				$paramObj = (object) $arrayDecodeResult;
				$param    = json_encode($paramObj);

				// Update the params.
				$fields = array(
					$db->quoteName('params') . ' = ' . $db->quote($param)
				);

				$conditions = array(
					$db->quoteName('id') . ' = ' . $db->quote($result->id)
				);
				$query->update($db->quoteName('#__menu'))->set($fields)->where($conditions);
				$db->setQuery($query);
				$db->execute();
			}
		}
	}
}
